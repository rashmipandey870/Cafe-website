<?php
/**
 * admin/includes/sidebar.php
 * Upgraded Administrative Left Sidebar Navigation with Live Pending Order Badges
 */

$current_script = $_SERVER['SCRIPT_NAME'];
$current_dir = basename(dirname($current_script));
$current_file = basename($current_script);

// Fetch count of pending orders for live notifications
$pending_orders_count = 0;
try {
    $db_sidebar = get_db_connection();
    $pending_orders_count = (int)$db_sidebar->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
} catch (Exception $e) {
    error_log("Sidebar badge query fail: " . $e->getMessage());
}
?>
<div class="col-md-3 col-lg-2 px-0 admin-sidebar d-flex flex-column">
    <!-- Brand Title -->
    <div class="px-4 py-3 mb-3 border-bottom" style="border-color: #EFEAE0 !important;">
        <h4 class="display-font text-dark mb-0 fs-4">M&M Admin</h4>
        <small class="text-muted text-capitalize"><?php echo escape($_SESSION['admin_name']); ?></small>
    </div>
    
    <!-- Sidebar Links -->
    <ul class="nav flex-column px-2 flex-grow-1">
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_file === 'dashboard.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/dashboard.php">
                <i class="bi bi-speedometer2"></i> Dashboard
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'categories') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/categories/index.php">
                <i class="bi bi-grid-fill"></i> Categories
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'menu') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/menu/index.php">
                <i class="bi bi-cup-hot-fill"></i> Menu Items
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'promotions') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/promotions/index.php">
                <i class="bi bi-tag-fill"></i> Promotions
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'orders') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/orders/index.php">
                <i class="bi bi-receipt"></i> Orders
                <?php if ($pending_orders_count > 0): ?>
                    <span class="badge bg-warning text-dark ms-auto rounded-pill px-2 py-1" style="font-size: 0.75rem;">
                        <?php echo $pending_orders_count; ?>
                    </span>
                <?php endif; ?>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'reservations') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/reservations/index.php">
                <i class="bi bi-calendar2-event"></i> Reservations
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'customers') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/customers/index.php">
                <i class="bi bi-people-fill"></i> Customers
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'reviews') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/reviews/index.php">
                <i class="bi bi-star-fill"></i> Reviews
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'gallery') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/gallery/index.php">
                <i class="bi bi-images"></i> Gallery
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'messages') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/messages/index.php">
                <i class="bi bi-envelope-paper-fill"></i> Messages
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'reports') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/reports/index.php">
                <i class="bi bi-bar-chart-line-fill"></i> Reports
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'qrcode') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/qrcode/index.php">
                <i class="bi bi-qr-code"></i> Table QR Codes
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?php echo ($current_dir === 'settings') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/admin/settings/index.php">
                <i class="bi bi-gear-fill"></i> Settings
            </a>
        </li>
    </ul>
    
    <!-- Footer / Utility links -->
    <div class="px-2 mt-auto border-top pt-3" style="border-color: #EFEAE0 !important;">
        <a class="nav-link text-muted mb-2" href="<?php echo BASE_URL; ?>/index.php" target="_blank">
            <i class="bi bi-arrow-up-right-circle"></i> View Website
        </a>
        <a class="nav-link text-danger" href="<?php echo BASE_URL; ?>/admin/logout.php">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>

<!-- Start of Main Content Column (corresponds to closing layout tags in footer) -->
<div class="col-md-9 col-lg-10 py-4 px-md-4">
    <!-- Breadcrumb or page title bar -->
    <div class="d-flex justify-content-between align-items-center mb-4 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">
        <h2 class="display-font text-dark mb-0 fs-3"><?php echo isset($page_title) ? escape($page_title) : 'Dashboard'; ?></h2>
        <div class="text-muted small">
            Logged in as: <strong class="text-dark"><?php echo escape($_SESSION['admin_email']); ?></strong>
        </div>
    </div>
    
    <!-- Flash Messages -->
    <?php display_flash_message(); ?>
