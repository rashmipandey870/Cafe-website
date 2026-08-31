<?php
/**
 * includes/functions.php
 * Core Sanitization, Validation, Image Uploading, and UI Helpers
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Escapes output to prevent Cross-Site Scripting (XSS) attacks.
 * 
 * @param string|null $string
 * @return string
 */
function escape($string) {
    if ($string === null) {
        return '';
    }
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Formats a decimal price to currency format (Indian Rupee ₹).
 * 
 * @param float|string $price
 * @return string
 */
function format_price($price) {
    return '₹' . number_format((float)$price, 2);
}

/**
 * Sets a flash message to be displayed on the next page load.
 * 
 * @param string $type ('success', 'danger', 'warning', 'info')
 * @param string $message
 */
function set_flash_message($type, $message) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION['flash_message'] = [
        'type' => $type,
        'message' => $message
    ];
}

/**
 * Displays and clears the flash message if set.
 * 
 * @return string|void
 */
function display_flash_message() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        
        $type = escape($flash['type']);
        $message = escape($flash['message']);
        
        echo "<div class='alert alert-{$type} alert-dismissible fade show' role='alert'>
                {$message}
                <button type='button' class='btn-close' data-bs-dismiss='alert' aria-label='Close'></button>
              </div>";
    }
}

/**
 * Validates and uploads a file to the server.
 * Restricts extensions, file size, and verifies MIME type.
 * 
 * @param array $file_post The $_FILES['input_name'] array
 * @param string $target_dir Target folder relative to website root (e.g. 'uploads/menu/')
 * @param int $max_size Max file size in bytes (default 2MB)
 * @return string|false Filename on success, false on failure
 */
function upload_image($file_post, $target_dir, $max_size = 2097152) {
    // 1. Check for basic errors
    if (!isset($file_post) || $file_post['error'] !== UPLOAD_ERR_OK) {
        return false;
    }
    
    // 2. Validate File Size
    if ($file_post['size'] > $max_size) {
        return false;
    }
    
    // 3. Validate File Extension
    $filename = basename($file_post['name']);
    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($extension, $allowed_extensions)) {
        return false;
    }
    
    // 4. Validate MIME Type (using getimagesize to verify it's a real image and not executable)
    $image_info = @getimagesize($file_post['tmp_name']);
    if ($image_info === false) {
        return false;
    }
    
    $allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
    if (!in_array($image_info['mime'], $allowed_mimes)) {
        return false;
    }
    
    // 5. Generate secure, unique filename to prevent overwrites and directory traversal
    $new_filename = uniqid('img_', true) . '.' . $extension;
    
    // Ensure root path prefix
    $absolute_dir = __DIR__ . '/../' . $target_dir;
    if (!is_dir($absolute_dir)) {
        mkdir($absolute_dir, 0755, true);
    }
    
    $target_file = $absolute_dir . $new_filename;
    
    // Move uploaded file to final destination
    if (move_uploaded_file($file_post['tmp_name'], $target_file)) {
        return $target_dir . $new_filename;
    }
    
    return false;
}

/**
 * Returns HTML badge for Order Status.
 * 
 * @param string $status
 * @return string
 */
function get_order_status_badge($status) {
    $class = 'bg-secondary';
    switch ($status) {
        case 'pending':
            $class = 'bg-warning text-dark';
            break;
        case 'confirmed':
            $class = 'bg-info text-dark';
            break;
        case 'preparing':
            $class = 'bg-primary';
            break;
        case 'ready':
            $class = 'bg-teal text-white'; // custom color handled by style.css or standard green
            $class = 'bg-info text-white';
            break;
        case 'out_for_delivery':
            $class = 'bg-info text-white';
            break;
        case 'completed':
            $class = 'bg-success';
            break;
        case 'cancelled':
            $class = 'bg-danger';
            break;
    }
    return "<span class='badge {$class} px-3 py-2 rounded-pill text-capitalize'>" . escape($status) . "</span>";
}

/**
 * Returns HTML badge for Reservation Status.
 * 
 * @param string $status
 * @return string
 */
function get_reservation_status_badge($status) {
    $class = 'bg-secondary';
    switch ($status) {
        case 'pending':
            $class = 'bg-warning text-dark';
            break;
        case 'confirmed':
            $class = 'bg-success';
            break;
        case 'cancelled':
            $class = 'bg-danger';
            break;
        case 'completed':
            $class = 'bg-secondary';
            break;
    }
    return "<span class='badge {$class} px-3 py-2 rounded-pill text-capitalize'>" . escape($status) . "</span>";
}

/**
 * Generates an alphanumeric order number.
 * 
 * @return string
 */
function generate_order_number() {
    return 'ORD-' . date('Ymd') . '-' . mt_rand(1000, 9999);
}
