<?php
// User logging out — destroy the server session and clear cookies.
require_once __DIR__ . '/../config.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']);
}

// Remove the legacy identity cookie left over from older sessions.
setcookie('user_id', '', time() - 42000, '/');

session_destroy();

header('location: ../index.php');
exit();
