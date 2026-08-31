<?php
/**
 * includes/auth.php
 * Session Authentication and Administrator Gatekeeping
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Checks if the current session is an authenticated admin.
 * 
 * @return bool
 */
function is_admin_logged_in() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true && isset($_SESSION['admin_user_id']);
}

/**
 * Restricts access to authenticated administrators only.
 * Redirects unauthorized users to the admin login page.
 */
function require_admin_login() {
    if (!is_admin_logged_in()) {
        // Clear any half-formed sessions
        unset($_SESSION['admin_logged_in']);
        unset($_SESSION['admin_user_id']);
        unset($_SESSION['admin_email']);
        unset($_SESSION['admin_name']);
        
        // Find correct login path relative to root
        $login_path = BASE_URL . '/admin/login.php';
        header("Location: " . $login_path);
        exit;
    }
}
