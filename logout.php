<?php
/**
 * logout.php
 * Customer Logout - Destroys customer session variables
 */

require_once __DIR__ . '/config/config.php';

// Clear customer session variables
unset($_SESSION['customer_logged_in']);
$_SESSION['customer_logged_in'] = false;
unset($_SESSION['customer_id']);
unset($_SESSION['customer_name']);
unset($_SESSION['customer_email']);
unset($_SESSION['customer_phone']);

// Redirect to customer login
header("Location: login.php");
exit;
