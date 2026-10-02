<?php
require_once 'bootstrap.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_user() && !is_admin()) {
    http_response_code(401);
    echo json_encode([]);
    exit;
}

$query = trim((string) ($_GET['q'] ?? ''));
if (mb_strlen($query) < 3) {
    echo json_encode([]);
    exit;
}

// Escape LIKE wildcards so user input is matched literally.
$pattern = '%' . addcslashes($query, '%_\\') . '%';
$stmt = $conn->prepare("SELECT DISTINCT product_name FROM inventory WHERE product_name LIKE ? ORDER BY product_name LIMIT 5");
$stmt->bind_param("s", $pattern);
$stmt->execute();

echo json_encode(array_column($stmt->get_result()->fetch_all(MYSQLI_ASSOC), 'product_name'));
