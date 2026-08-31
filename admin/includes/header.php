<?php
/**
 * admin/includes/header.php
 * Administrative HTML Header and Session Protection Gates
 */

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/auth.php';

// Enforce admin login check for all administrative views
require_admin_login();

$site_name = isset($settings['cafe_name']) ? $settings['cafe_name'] : 'Mellow & Meadow';
$admin_title = isset($page_title) ? $page_title . ' - Admin Dashboard' : 'Admin Panel - ' . $site_name;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo escape($admin_title); ?></title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    
    <!-- Custom Style Links -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
</head>
<body class="admin-body">
<div class="container-fluid">
    <div class="row">
