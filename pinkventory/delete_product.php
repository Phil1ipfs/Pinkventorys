<?php
require_once 'bootstrap.php';
require_login();

$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$id) {
    http_response_code(400);
    exit('Invalid request. <a href="inventory.php">Back to inventory</a>');
}

$stmt = $conn->prepare("DELETE FROM inventory WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();

redirect('inventory.php?deleted=success');
