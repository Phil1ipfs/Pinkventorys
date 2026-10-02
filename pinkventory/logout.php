<?php
require_once 'bootstrap.php';

// The token stops other sites from silently logging users out.
if (csrf_valid($_GET['token'] ?? null)) {
    $_SESSION = [];
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    session_destroy();
}

redirect('index.php');
