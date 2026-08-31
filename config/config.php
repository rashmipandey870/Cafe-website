<?php
/**
 * config/config.php
 * General Configurations, Secure Sessions, Timezones, and Settings Loader
 */

require_once __DIR__ . '/database.php';

// 1. Secure Session Initialization
if (session_status() === PHP_SESSION_NONE) {
    $secure = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on';
    
    if (!headers_sent()) {
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $secure,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);
        session_start();
    } else {
        session_start();
    }
}

// 2. Base Configuration Constants
if (!defined('BASE_URL')) {
    $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? "https://" : "http://";
    $host = $_SERVER['HTTP_HOST'];
    $script_name = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
    $base_path = rtrim($script_name, '/');
    define('BASE_URL', $protocol . $host . $base_path);
}

// 3. Load Dynamic Settings from Database
$settings = [];

try {
    $db = get_db_connection();
    $stmt = $db->query("SELECT `setting_key`, `setting_value` FROM `settings`");
    while ($row = $stmt->fetch()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    error_log("Settings Loader Error: " . $e->getMessage());
}

// 4. Default Settings (Fallbacks for initial setup)
$default_settings = [
    'cafe_name'              => 'Mellow & Meadow',
    'cafe_logo'              => 'assets/images/logo.png',
    'cafe_phone'             => '+91 98765 43210',
    'cafe_email'             => 'hello@mellowandmeadow.com',
    'cafe_address'           => '12, Sage Boulevard, Green Park, Delhi - 110016',
    'cafe_opening_hours'     => 'Mon - Sun: 8:00 AM - 10:00 PM',
    'cafe_whatsapp'          => '+919876543210',
    'cafe_social_facebook'   => 'https://facebook.com',
    'cafe_social_instagram'  => 'https://instagram.com',
    'cafe_social_twitter'    => 'https://twitter.com',
    'cafe_google_maps'       => 'https://www.google.com/maps/embed',
    'cafe_about_text'        => 'Mellow & Meadow is a sun-filled, plant-rich sanctuary designed for slow mornings, productive afternoons, and shared evening moments.',
    'cafe_timezone'          => 'Asia/Kolkata',
    'delivery_enabled'       => '1',
    'delivery_charge'        => '45.00',
    'free_delivery_above'    => '500.00',
    'minimum_delivery_order' => '200.00',
    'tax_enabled'            => '1',
    'tax_rate'               => '5.00'
];

// Fill in any missing settings with default fallbacks
foreach ($default_settings as $key => $fallback_value) {
    if (!isset($settings[$key]) || $settings[$key] === '') {
        $settings[$key] = $fallback_value;
    }
}

// 5. Enforce Configured Café Timezone
$configured_timezone = !empty($settings['cafe_timezone']) ? $settings['cafe_timezone'] : 'Asia/Kolkata';
if (!@date_default_timezone_set($configured_timezone)) {
    date_default_timezone_set('Asia/Kolkata'); // Fallback if DB holds invalid timezone name
}
