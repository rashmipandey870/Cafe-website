<?php
/**
 * includes/qrcode.php
 * QR Code Generator Utilities for Website Navigation, Table Stands, and UPI Payments
 */

/**
 * Returns a high-resolution QR code image URL for a given string/URL.
 * 
 * @param string $data The text or URL to encode
 * @param int $size Image dimensions in pixels (default: 300)
 * @return string
 */
function get_qr_image_url($data, $size = 300) {
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&margin=10&data=" . urlencode($data);
}

/**
 * Builds the URL for a specific table's QR Code.
 * 
 * @param string|int $table_number
 * @return string
 */
function get_table_qr_url($table_number = null) {
    global $settings;
    
    $base = isset($settings['website_qr_url']) && !empty($settings['website_qr_url']) 
          ? rtrim($settings['website_qr_url'], '/') 
          : BASE_URL . '/menu.php';
          
    if (!empty($table_number)) {
        $sep = (strpos($base, '?') !== false) ? '&' : '?';
        return $base . $sep . 'table=' . urlencode($table_number);
    }
    
    return $base;
}
