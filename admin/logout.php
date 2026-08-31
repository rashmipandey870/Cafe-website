<?php
/**
 * admin/logout.php
 * Destroys administrative session and redirects to login portal
 */

require_once __DIR__ . '/../config/config.php';

// Unset all session variables
$_SESSION = [];

// If session cookie is active, clear it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy session
session_destroy();

// Redirect back to login
header("Location: login.php");
exit;
