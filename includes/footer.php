<?php
/**
 * includes/footer.php
 * Reusable Light-Themed Footer and JavaScript Scripts
 */
?>
<footer class="custom-footer py-5 mt-5">
    <div class="container">
        <div class="row g-4">
            <!-- Col 1: About & Identity -->
            <div class="col-md-4">
                <div class="footer-logo mb-3"><?php echo escape($settings['cafe_name']); ?></div>
                <p class="pe-md-4 text-muted" style="font-size: 0.9rem;">
                    <?php echo escape($settings['cafe_about_text']); ?>
                </p>
                <div class="mt-3">
                    <?php if (!empty($settings['cafe_social_facebook'])): ?>
                        <a href="<?php echo escape($settings['cafe_social_facebook']); ?>" class="footer-social-icon" target="_blank" aria-label="Facebook"><i class="bi bi-facebook"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['cafe_social_instagram'])): ?>
                        <a href="<?php echo escape($settings['cafe_social_instagram']); ?>" class="footer-social-icon" target="_blank" aria-label="Instagram"><i class="bi bi-instagram"></i></a>
                    <?php endif; ?>
                    <?php if (!empty($settings['cafe_social_twitter'])): ?>
                        <a href="<?php echo escape($settings['cafe_social_twitter']); ?>" class="footer-social-icon" target="_blank" aria-label="Twitter"><i class="bi bi-twitter-x"></i></a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Col 2: Navigation Links -->
            <div class="col-md-2 col-6">
                <h5 class="footer-heading">Explore</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/index.php">Home</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/menu.php">Our Menu</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/about.php">Our Story</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/offers.php">Special Offers</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/gallery.php">Gallery</a></li>
                </ul>
            </div>
            
            <!-- Col 3: Customer Services -->
            <div class="col-md-2 col-6">
                <h5 class="footer-heading">Services</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/reservation.php">Table Reservation</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/cart.php">Your Cart</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/contact.php">Contact & Help</a></li>
                    <li class="mb-2"><a href="<?php echo BASE_URL; ?>/admin/login.php" class="text-muted">Admin Portal</a></li>
                </ul>
            </div>
            
            <!-- Col 4: Contact details & Hours -->
            <div class="col-md-4">
                <h5 class="footer-heading">Location & Hours</h5>
                <p class="mb-2 text-muted" style="font-size: 0.9rem;">
                    <i class="bi bi-geo-alt-fill me-2 text-accent" style="color: var(--accent-terracotta);"></i>
                    <?php echo escape($settings['cafe_address']); ?>
                </p>
                <p class="mb-2 text-muted" style="font-size: 0.9rem;">
                    <i class="bi bi-telephone-fill me-2 text-accent" style="color: var(--accent-terracotta);"></i>
                    <?php echo escape($settings['cafe_phone']); ?>
                </p>
                <p class="mb-2 text-muted" style="font-size: 0.9rem;">
                    <i class="bi bi-clock-fill me-2 text-accent" style="color: var(--accent-terracotta);"></i>
                    <?php echo escape($settings['cafe_opening_hours']); ?>
                </p>
                
                <?php if (!empty($settings['cafe_whatsapp'])): ?>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['cafe_whatsapp']); ?>" class="btn btn-outline-sage btn-sm mt-3" target="_blank">
                        <i class="bi bi-whatsapp me-2"></i>Chat on WhatsApp
                    </a>
                <?php endif; ?>
            </div>
        </div>
        
        <hr class="my-4" style="border-color: #E8E0CE;">
        
        <div class="row align-items-center">
            <div class="col-md-6 text-center text-md-start mb-2 mb-md-0 text-muted" style="font-size: 0.85rem;">
                &copy; <?php echo date('Y'); ?> <?php echo escape($settings['cafe_name']); ?>. All rights reserved.
            </div>
            <div class="col-md-6 text-center text-md-end text-muted" style="font-size: 0.85rem;">
                Crafted with care for slow mornings.
            </div>
        </div>
    </div>
</footer>

<!-- Bootstrap 5 Bundle JS (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<!-- Shopping Cart Core JavaScript -->
<script src="<?php echo BASE_URL; ?>/assets/js/cart.js"></script>
</body>
</html>
