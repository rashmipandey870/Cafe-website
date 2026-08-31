<?php
/**
 * admin/index.php
 * Default Admin entry point - Routes to Login or Dashboard
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/auth.php';

if (is_admin_logged_in()) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
} else {
    header("Location: " . BASE_URL . "/admin/login.php");
}
exit;
