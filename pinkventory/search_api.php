<?php
require_once 'bootstrap.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

if (!is_user() && !is_admin()) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$query = trim((string) ($_GET['q'] ?? ''));
$full = isset($_GET['full']);

/** API shape for one product row. */
function product_json(array $row): array
{
    return [
        'id' => (int) $row['id'],
        'name' => $row['product_name'],
        'category' => ucfirst((string) $row['category']),
        'stock' => (int) $row['stock'],
        'price' => round((float) $row['price'], 2),
        'level' => stock_level((int) $row['stock'], $row['status']),
        'icon' => product_visual($row['category'])[0],
        'tone' => product_visual($row['category'])[1],
    ];
}

// Cart refresh: current price and stock for specific product IDs (?ids=1,4,7).
if (isset($_GET['ids'])) {
    $ids = array_values(array_unique(array_filter(array_map('intval', explode(',', (string) $_GET['ids'])), fn($id) => $id > 0)));
    $ids = array_slice($ids, 0, 100);
    if (!$ids) {
        echo json_encode([]);
        exit;
    }
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $conn->prepare("SELECT id, product_name, category, stock, price, status FROM inventory WHERE id IN ($placeholders)");
    $stmt->bind_param(str_repeat('i', count($ids)), ...$ids);
    $stmt->execute();
    echo json_encode(array_map('product_json', $stmt->get_result()->fetch_all(MYSQLI_ASSOC)));
    exit;
}

// Escape LIKE wildcards so user input is matched literally.
$pattern = '%' . addcslashes($query, '%_\\') . '%';

if ($full) {
    // Checkout lookup: product details, matching an exact product ID (barcode) or the name.
    if ($query === '') {
        echo json_encode([]);
        exit;
    }
    $id = ctype_digit($query) ? (int) $query : 0;
    $stmt = $conn->prepare("SELECT id, product_name, category, stock, price, status FROM inventory
        WHERE id = ? OR product_name LIKE ? ORDER BY (id = ?) DESC, product_name LIMIT 8");
    $stmt->bind_param("isi", $id, $pattern, $id);
    $stmt->execute();
    echo json_encode(array_map('product_json', $stmt->get_result()->fetch_all(MYSQLI_ASSOC)));
    exit;
}

// Navbar suggestions: product names only.
if (mb_strlen($query) < 3) {
    echo json_encode([]);
    exit;
}
$stmt = $conn->prepare("SELECT DISTINCT product_name FROM inventory WHERE product_name LIKE ? ORDER BY product_name LIMIT 6");
$stmt->bind_param("s", $pattern);
$stmt->execute();

echo json_encode(array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'product_name'));
