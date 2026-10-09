<?php
/**
 * Grand Cafe - Logout
 * Destroys user session and redirects to index.php.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Unset all session variables
$_SESSION = [];

// Delete session cookie if configured
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Start fresh session to pass friendly logout notice
session_start();
require_once __DIR__ . '/includes/auth_helper.php';
setFlash('info', 'You have been successfully signed out. Have a wonderful day!');

header('Location: index.php');
exit;
