<?php
/**
 * config/db_config.example.php
 * Template database configuration definitions.
 * Copy this file to config/db_config.php and update the parameters for your hosting server.
 */

define('DB_HOST', '127.0.0.1');
define('DB_PORT', '3306');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
define('DB_NAME', 'your_database_name');

// Razorpay Server-Side Secrets (Never exposed to frontend or Git)
define('RAZORPAY_KEY_SECRET', 'your_razorpay_secret_key');
define('RAZORPAY_WEBHOOK_SECRET', 'your_razorpay_webhook_secret');
