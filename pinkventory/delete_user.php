<?php
require_once 'bootstrap.php';

header('Content-Type: text/plain; charset=utf-8');

if (!is_admin()) {
    http_response_code(403);
    exit('Not authorised.');
}

$userId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !$userId) {
    http_response_code(400);
    exit('Invalid request.');
}

$stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    http_response_code(404);
    exit('User not found.');
}

echo "User deleted successfully";
