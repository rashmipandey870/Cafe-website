<?php
/**
 * includes/navbar.php
 * Upgraded sticky header navigation layout with customer authentication checks
 */

$current_page = basename($_SERVER['SCRIPT_NAME']);
?>
<nav class="navbar navbar-expand-lg sticky-top custom-navbar py-3">
    <div class="container">
        <!-- Logo / Cafe Brand Name -->
        <a class="navbar-brand d-flex align-items-center" href="<?php echo BASE_URL; ?>/index.php">
            <?php if (!empty($settings['cafe_logo']) && file_exists(__DIR__ . '/../' . $settings['cafe_logo'])): ?>
                <img src="<?php echo BASE_URL; ?>/<?php echo escape($settings['cafe_logo']); ?>" alt="<?php echo escape($settings['cafe_name']); ?>" height="40" class="me-2">
            <?php endif; ?>
            <span><?php echo escape($settings['cafe_name']); ?></span>
        </a>
        
        <!-- Mobile Toggle Button -->
        <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#cafeNavbar" aria-controls="cafeNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <!-- Navigation Links -->
        <div class="collapse navbar-collapse" id="cafeNavbar">
            <ul class="navbar-nav mx-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'menu.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/menu.php">Menu</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'about.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'offers.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/offers.php">Offers</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'gallery.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/gallery.php">Gallery</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'track-order.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/track-order.php">Track Order</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?php echo ($current_page == 'contact.php') ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/contact.php">Contact</a>
                </li>
            </ul>
            
            <div class="d-flex align-items-center flex-wrap gap-2 justify-content-start mt-3 mt-lg-0">
                <!-- Customer Login / Profile State -->
                <?php if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true): ?>
                    <div class="dropdown">
                        <button class="btn btn-outline-sage btn-sm dropdown-toggle py-2 px-3 fw-bold" type="button" id="customerMenu" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-fill me-1"></i> Account
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="customerMenu">
                            <li><a class="dropdown-menu-item dropdown-item fw-medium py-2 text-dark" href="<?php echo BASE_URL; ?>/customer-dashboard.php"><i class="bi bi-speedometer me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-menu-item dropdown-item fw-medium py-2 text-danger" href="<?php echo BASE_URL; ?>/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Sign Out</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="<?php echo BASE_URL; ?>/login.php" class="btn btn-outline-sage btn-sm py-2 px-3 fw-bold">Sign In</a>
                <?php endif; ?>
                
                <!-- Shopping Cart Pill -->
                <a href="<?php echo BASE_URL; ?>/cart.php" class="cart-badge-btn text-decoration-none">
                    <i class="bi bi-cart3 me-1"></i>
                    <span class="d-none d-md-inline me-1">Cart</span>
                    <span class="badge bg-sage rounded-pill cart-count d-none">0</span>
                </a>
                
                <!-- Book a Table Call to action -->
                <a href="<?php echo BASE_URL; ?>/reservation.php" class="btn btn-sage ms-2 d-none d-lg-inline-block">Book a Table</a>
            </div>
        </div>
    </div>
</nav>
