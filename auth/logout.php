<?php
// auth/logout.php
// Secure user logout and session cleanup

require_once __DIR__ . '/../inc/config.php';
require_once __DIR__ . '/../inc/helpers.php';

// Clear session variables
$_SESSION = [];

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

// Start a fresh session just to store the farewell flash message
session_start();
set_flash('You have been logged out safely.', 'info');

header('Location: ' . BASE_URL);
exit;