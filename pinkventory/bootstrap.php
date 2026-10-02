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
    . "script-src 'self'; "
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

/** Inline SVG icon from the local sprite (icons.svg). Decorative unless a label is given. */
function icon(string $name, string $class = '', ?string $label = null): string
{
    $a11y = $label === null ? 'aria-hidden="true"' : 'role="img" aria-label="' . e($label) . '"';
    return '<svg class="icon ' . e($class) . '" ' . $a11y . ' focusable="false"><use href="' . asset('assets/img/icons.svg') . '#' . e($name) . '"></use></svg>';
}

/** Queues a toast message to show on the next page view. $type: success | error | info. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
}

function take_flashes(): array
{
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

/** "3 hours ago" style label for a MySQL DATETIME string. */
function time_ago(?string $datetime): string
{
    $time = $datetime ? strtotime($datetime) : false;
    if (!$time) {
        return '—';
    }
    $diff = time() - $time;
    if ($diff < 60) {
        return 'just now';
    }
    foreach ([['year', 31536000], ['month', 2592000], ['week', 604800], ['day', 86400], ['hour', 3600], ['minute', 60]] as [$unit, $seconds]) {
        if ($diff >= $seconds) {
            $n = intdiv($diff, $seconds);
            return $n . ' ' . $unit . ($n === 1 ? '' : 's') . ' ago';
        }
    }
    return 'just now';
}

/** Up to two initials for an avatar, e.g. "Amy Pink" → "AP". */
function initials(string $name): string
{
    $parts = preg_split('/[\s_.@-]+/', trim($name), -1, PREG_SPLIT_NO_EMPTY);
    $letters = '';
    foreach (array_slice($parts, 0, 2) as $part) {
        $letters .= mb_strtoupper(mb_substr($part, 0, 1));
    }
    return $letters !== '' ? $letters : '?';
}

function money(float $amount): string
{
    return '$' . number_format($amount, 2);
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
    if (is_admin()) {
        $stmt = $conn->prepare('SELECT fullname FROM admin WHERE username = ?');
        $stmt->bind_param('s', $_SESSION['admin_username']);
        $stmt->execute();
        $row = $stmt->get_result()->fetch_assoc();
        return ($row['fullname'] ?? '') !== '' ? $row['fullname'] : $_SESSION['admin_username'];
    }
    return 'Guest';
}

/* ---------------------------------------------------------------------------
 * Inventory
 * ------------------------------------------------------------------------- */

/**
 * Validates submitted product fields.
 * Returns [array $product, array $errors] where $errors is keyed by field name
 * (empty when the input is valid).
 */
function validate_product(array $input): array
{
    $product = [
        'product_name' => trim((string) ($input['product_name'] ?? '')),
        'category'     => strtolower(trim((string) ($input['category'] ?? ''))),
        'stock'        => filter_var($input['stock'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]),
        'price'        => filter_var($input['price'] ?? null, FILTER_VALIDATE_FLOAT),
        'status'       => status_slug($input['status'] ?? ''),
    ];
    $errors = [];

    if ($product['product_name'] === '' || mb_strlen($product['product_name']) > 150) {
        $errors['product_name'] = 'Enter a product name (up to 150 characters).';
    }
    if ($product['category'] === '' || mb_strlen($product['category']) > 50) {
        $errors['category'] = 'Choose or type a category (up to 50 characters).';
    }
    if ($product['stock'] === false) {
        $errors['stock'] = 'Stock must be a whole number of 0 or more.';
    }
    if ($product['price'] === false || $product['price'] < 0 || $product['price'] > 99999999.99) {
        $errors['price'] = 'Enter a price of 0 or more, e.g. 19.99.';
    }
    if (!isset(INVENTORY_STATUSES[$product['status']])) {
        $errors['status'] = 'Choose a stock status.';
    }
    return [$product, $errors];
}

/** Categories offered in product forms: the defaults plus any already in use. */
function product_categories(mysqli $conn): array
{
    $categories = ['accessories', 'clothing', 'electronics'];
    $result = $conn->query("SELECT DISTINCT LOWER(category) AS category FROM inventory WHERE category <> '' ORDER BY category");
    foreach ($result->fetch_all(MYSQLI_ASSOC) as $row) {
        $categories[] = $row['category'];
    }
    $categories = array_values(array_unique($categories));
    sort($categories);
    return $categories;
}

/** How a product's stock should be presented: 'out', 'low' or 'ok'. */
function stock_level(int $stock, ?string $status = null): string
{
    $slug = status_slug($status);
    if ($stock <= 0 || $slug === 'out-of-stock') {
        return 'out';
    }
    if ($stock <= LOW_STOCK_THRESHOLD || $slug === 'low-stock') {
        return 'low';
    }
    return 'ok';
}

/** Where a signed-in visitor belongs: the admin dashboard or the store dashboard. */
function home_url(): string
{
    return is_admin() ? 'admin_dashboard.php' : 'homepage.php';
}

/**
 * SQL conditions for a product's effective stock level, matching stock_level():
 * out = no stock or marked out; low = at/below the threshold or marked low.
 */
function stock_level_sql(): array
{
    $out = "(stock <= 0 OR status IN ('out-of-stock', 'Out of Stock'))";
    $low = "(NOT $out AND (stock <= " . LOW_STOCK_THRESHOLD . " OR status IN ('low-stock', 'Low Stock')))";
    return ['out' => $out, 'low' => $low, 'ok' => "(NOT $out AND NOT $low)"];
}

function level_label(string $level): string
{
    return ['ok' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'][$level] ?? 'In stock';
}

/** Customer-only pages: admins are sent to their own dashboard. */
function require_user(): void
{
    if (is_admin()) {
        redirect('admin_dashboard.php');
    }
    if (!is_user()) {
        redirect('login.php');
    }
}

/**
 * Products have no image column, so each one gets a consistent visual:
 * an icon and colour tone chosen from its category.
 * Returns [icon name, tone].
 */
function product_visual(?string $category): array
{
    $known = [
        'electronics' => ['headphones', 'blue'],
        'clothing'    => ['shirt', 'pink'],
        'accessories' => ['watch', 'amber'],
    ];
    $key = strtolower(trim((string) $category));
    if (isset($known[$key])) {
        return $known[$key];
    }
    $tones = ['pink', 'blue', 'green', 'amber', 'violet'];
    return ['package', $tones[crc32($key) % count($tones)]];
}

/** The signed-in customer's row from the users table (or null). */
function current_user_row(mysqli $conn): ?array
{
    if (!is_user()) {
        return null;
    }
    $stmt = $conn->prepare('SELECT id, email, fullname, dob, gender FROM users WHERE email = ?');
    $stmt->bind_param('s', $_SESSION['email']);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc() ?: null;
}
