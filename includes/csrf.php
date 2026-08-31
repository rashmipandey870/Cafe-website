<?php
/**
 * includes/csrf.php
 * Cross-Site Request Forgery (CSRF) Protection
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generates a CSRF token if one does not exist, and returns it.
 * 
 * @return string
 */
function get_csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Outputs a hidden HTML input field containing the current CSRF token.
 * 
 * @return string
 */
function csrf_field() {
    $token = get_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Verifies that the submitted CSRF token matches the token stored in session.
 * Recommended for all POST requests.
 * 
 * @param array $request_data (defaults to $_POST)
 * @return bool
 */
function verify_csrf_token($request_data = null) {
    if ($request_data === null) {
        $request_data = $_POST;
    }
    
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    
    if (empty($request_data['csrf_token'])) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $request_data['csrf_token']);
}

/**
 * Enforces CSRF token check. Dies with a 403 response if invalid.
 */
function enforce_csrf() {
    if (!verify_csrf_token()) {
        http_response_code(403);
        die("CSRF Token validation failed. Please refresh the page and try again.");
    }
}

/**
 * Verifies a CSRF token provided via GET query parameter.
 * 
 * @param string $token
 * @return bool
 */
function verify_csrf_get($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

