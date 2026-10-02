<?php
/**
 * Shared bootstrap for every Pinkventory page.
 *
 * Loads configuration, sets up error handling, opens the database connection,
 * starts a hardened session, enforces CSRF on every POST and exposes the
 * helper functions used by the pages (escaping, auth guards, redirects).
 *
 * Configuration is read from environment variables first, then from
 * config.php (copy config.example.php; never commit the real file).
 */

if (basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    http_response_code(404);
    exit;
}

const SESSION_IDLE_TIMEOUT = 1800; // seconds of inactivity before the server ends a session
const LOW_STOCK_THRESHOLD  = 10;
const INVENTORY_STATUSES   = [
    'in-stock'     => 'In Stock',
    'low-stock'    => 'Low Stock',
    'out-of-stock' => 'Out of Stock',
];

/* ---------------------------------------------------------------------------
 * Configuration
 * ------------------------------------------------------------------------- */

function config(string $key, $default = null)
{
    static $file = null;
    if ($file === null) {
        $path = getenv('PINKVENTORY_CONFIG') ?: __DIR__ . '/config.php';
        $file = is_file($path) ? (array) require $path : [];
    }
    $env = getenv($key);
    if ($env !== false && $env !== '') {
        return $env;
    }
    return array_key_exists($key, $file) ? $file[$key] : $default;
}

function is_production(): bool
{
    return config('APP_ENV', 'production') !== 'development';
}

/* ---------------------------------------------------------------------------
 * Error handling: never leak details to visitors in production
 * ------------------------------------------------------------------------- */

error_reporting(E_ALL);
ini_set('display_errors', is_production() ? '0' : '1');
ini_set('log_errors', '1');

set_exception_handler(function (Throwable $e) {
    error_log('[Pinkventory] ' . $e);
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo is_production()
        ? 'Something went wrong. Please try again later.'
        : '<pre>' . htmlspecialchars((string) $e, ENT_QUOTES, 'UTF-8') . '</pre>';
});

/* ---------------------------------------------------------------------------
 * HTTPS + security headers
 * ------------------------------------------------------------------------- */

function is_https(): bool
{
    return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (int) ($_SERVER['SERVER_PORT'] ?? 0) === 443
        || strtolower($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
}

if (filter_var(config('FORCE_HTTPS', false), FILTER_VALIDATE_BOOLEAN) && !is_https()) {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'], true, 301);
    exit;
}

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; "
    . "script-src 'self' 'unsafe-inline' https://unpkg.com https://cdnjs.cloudflare.com; "
    . "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; "
    . "img-src 'self' data:; connect-src 'self'; "
    . "object-src 'none'; base-uri 'self'; form-action 'self'; frame-ancestors 'none'");
if (is_https()) {
    header('Strict-Transport-Security: max-age=31536000');
}

/* ---------------------------------------------------------------------------
 * Database
 * ------------------------------------------------------------------------- */

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conn = new mysqli(
        config('DB_HOST', 'localhost'),
        config('DB_USER', ''),
        config('DB_PASS', ''),
        config('DB_NAME', ''),
        (int) config('DB_PORT', 3306)
    );
    $conn->set_charset('utf8mb4');
} catch (mysqli_sql_exception $e) {
    error_log('[Pinkventory] Database connection failed: ' . $e->getMessage());
    http_response_code(503);
    exit(is_production()
        ? 'The service is temporarily unavailable. Please try again later.'
        : 'Database connection failed: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8')
          . '<br>Check config.php (see config.example.php).');
}

/* ---------------------------------------------------------------------------
 * Session
 * ------------------------------------------------------------------------- */

ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
session_name('PINKVENTORYSESSID');
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => is_https(),
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

if (isset($_SESSION['last_activity']) && time() - $_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['last_activity'] = time();

/* ---------------------------------------------------------------------------
 * CSRF: every POST request must carry the session token
 * ------------------------------------------------------------------------- */

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_valid(?string $token): bool
{
    return is_string($token) && hash_equals(csrf_token(), $token);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_valid($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    exit('Your session has expired. Please go back, refresh the page and try again.');
}

/* ---------------------------------------------------------------------------
 * Helpers
 * ------------------------------------------------------------------------- */

function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Static asset URL with a cache-busting version that only changes when the file does. */
function asset(string $path): string
{
    $file = __DIR__ . '/' . $path;
    return e($path) . (is_file($file) ? '?v=' . filemtime($file) : '');
}

function redirect(string $location): void
{
    header('Location: ' . $location);
    exit;
}

function logout_url(): string
{
    return 'logout.php?token=' . urlencode(csrf_token());
}

function status_label(?string $status): string
{
    $slug = status_slug($status);
    return INVENTORY_STATUSES[$slug] ?? (string) $status;
}

/** Normalises legacy values such as "In Stock" to the stored slug "in-stock". */
function status_slug(?string $status): string
{
    return strtolower(str_replace(' ', '-', trim((string) $status)));
}

/* ---------------------------------------------------------------------------
 * Authentication
 * ------------------------------------------------------------------------- */

function is_admin(): bool
{
    return isset($_SESSION['admin_username']);
}

function is_user(): bool
{
    return isset($_SESSION['email']);
}

/** Starts a fresh authenticated session (prevents session fixation). */
function login_as(string $key, string $value): void
{
    session_regenerate_id(true);
    unset($_SESSION['email'], $_SESSION['admin_username']);
    $_SESSION[$key] = $value;
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

function require_login(): void
{
    if (!is_user() && !is_admin()) {
        redirect('login.php');
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        redirect('login.php');
    }
}

function current_display_name(mysqli $conn): string
{
    if (is_user()) {
        $stmt = $conn->prepare('SELECT fullname FROM users WHERE email = ?');
        $stmt->bind_param('s', $_SESSION['email']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return $row['fullname'] ?? 'Guest';
    }
    return $_SESSION['admin_username'] ?? 'Guest';
}

/* ---------------------------------------------------------------------------
 * Inventory
 * ------------------------------------------------------------------------- */

/**
 * Validates submitted product fields.
 * Returns [array $product, ?string $error].
 */
function validate_product(array $input): array
{
    $product = [
        'product_name' => trim((string) ($input['product_name'] ?? '')),
        'category'     => trim((string) ($input['category'] ?? '')),
        'stock'        => filter_var($input['stock'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]),
        'price'        => filter_var($input['price'] ?? null, FILTER_VALIDATE_FLOAT),
        'status'       => status_slug($input['status'] ?? ''),
    ];

    if ($product['product_name'] === '' || mb_strlen($product['product_name']) > 150) {
        return [$product, 'Product name is required (up to 150 characters).'];
    }
    if ($product['category'] === '' || mb_strlen($product['category']) > 50) {
        return [$product, 'Category is required (up to 50 characters).'];
    }
    if ($product['stock'] === false) {
        return [$product, 'Stock must be a whole number of 0 or more.'];
    }
    if ($product['price'] === false || $product['price'] < 0 || $product['price'] > 99999999.99) {
        return [$product, 'Price must be a positive amount.'];
    }
    if (!isset(INVENTORY_STATUSES[$product['status']])) {
        return [$product, 'Please choose a valid status.'];
    }
    return [$product, null];
}

/** Where a signed-in visitor belongs: the admin dashboard or the store dashboard. */
function home_url(): string
{
    return is_admin() ? 'admin_dashboard.php' : 'homepage.php';
}
