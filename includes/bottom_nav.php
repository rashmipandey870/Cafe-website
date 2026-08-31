<?php
/**
 * includes/bottom_nav.php
 * Fixed Bottom App-Like Navigation for Mobile Devices
 */

$current_page = basename($_SERVER['PHP_SELF']);
$table_num = isset($_SESSION['table_number']) ? $_SESSION['table_number'] : null;
?>

<!-- Floating Mobile Cart Pill (Swiggy / Zomato Style) -->
<div id="mobile-floating-cart" class="mobile-floating-cart-bar shadow-lg d-md-none" style="display: none;">
    <div class="d-flex justify-content-between align-items-center w-100 px-3 py-2">
        <div class="d-flex align-items-center">
            <span class="badge bg-white text-dark fw-bold me-2 px-2 py-1" id="m-float-cart-count">0 Items</span>
            <span class="fw-bold text-white fs-6" id="m-float-cart-total">₹0.00</span>
        </div>
        <a href="<?php echo BASE_URL; ?>/cart.php" class="btn btn-sm btn-light fw-bold text-sage px-3 py-1 rounded-pill">
            View Cart <i class="bi bi-arrow-right ms-1"></i>
        </a>
    </div>
</div>

<!-- Mobile App Bottom Navigation Bar -->
<nav class="mobile-bottom-nav d-md-none border-top bg-white fixed-bottom shadow-sm">
    <div class="container-fluid px-1">
        <div class="row g-0 text-center">
            <div class="col">
                <a href="<?php echo BASE_URL; ?>/index.php" class="nav-item-mobile <?php echo ($current_page === 'index.php') ? 'active' : ''; ?>">
                    <i class="bi bi-house-door<?php echo ($current_page === 'index.php') ? '-fill' : ''; ?>"></i>
                    <span>Home</span>
                </a>
            </div>
            <div class="col">
                <a href="<?php echo BASE_URL; ?>/menu.php" class="nav-item-mobile <?php echo ($current_page === 'menu.php') ? 'active' : ''; ?>">
                    <i class="bi bi-cup-hot<?php echo ($current_page === 'menu.php') ? '-fill' : ''; ?>"></i>
                    <span>Menu</span>
                </a>
            </div>
            <div class="col">
                <a href="<?php echo BASE_URL; ?>/offers.php" class="nav-item-mobile <?php echo ($current_page === 'offers.php') ? 'active' : ''; ?>">
                    <i class="bi bi-tag<?php echo ($current_page === 'offers.php') ? '-fill' : ''; ?>"></i>
                    <span>Offers</span>
                </a>
            </div>
            <div class="col position-relative">
                <a href="<?php echo BASE_URL; ?>/cart.php" class="nav-item-mobile <?php echo ($current_page === 'cart.php' || $current_page === 'checkout.php') ? 'active' : ''; ?>">
                    <div class="position-relative d-inline-block">
                        <i class="bi bi-bag<?php echo ($current_page === 'cart.php') ? '-fill' : ''; ?>"></i>
                        <span class="cart-badge-pill cart-count-badge d-none">0</span>
                    </div>
                    <span>Cart</span>
                </a>
            </div>
            <div class="col">
                <?php if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true): ?>
                    <a href="<?php echo BASE_URL; ?>/customer-dashboard.php" class="nav-item-mobile <?php echo ($current_page === 'customer-dashboard.php') ? 'active' : ''; ?>">
                        <i class="bi bi-person-circle"></i>
                        <span>Account</span>
                    </a>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/track-order.php" class="nav-item-mobile <?php echo ($current_page === 'track-order.php') ? 'active' : ''; ?>">
                        <i class="bi bi-clock-history"></i>
                        <span>Orders</span>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</nav>
