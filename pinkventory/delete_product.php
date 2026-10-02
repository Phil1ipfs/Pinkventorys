<?php
require_once 'bootstrap.php';
require_admin();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
    http_response_code(400);
    exit('Invalid request. <a href="inventory.php">Back to inventory</a>');
}

$stmt = $conn->prepare("SELECT product_name FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();

if ($product) {
    $stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    flash('success', '“' . $product['product_name'] . '” was deleted.');
} else {
    flash('error', 'That product no longer exists.');
}

// Go back to the same filtered list, but only ever to the inventory page.
$return = (string) ($_POST['return'] ?? '');
redirect(preg_match('#^inventory\.php(\?[^\s]*)?$#', $return) ? $return : 'inventory.php');
