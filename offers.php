<?php
/**
 * offers.php
 * Offers Page - Upgraded to query promotions/coupon engine database tables
 */

$page_title = 'Special Offers';
$page_description = 'Browse Mellow & Meadow promotions, seasonal discount coupons, and all-day brunch combos.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = get_db_connection();
$offers = [];

try {
    // Fetch active promotions whose validity datetime includes the current moment
    $stmt = $db->prepare("SELECT name, description, promotion_type, discount_value, coupon_code, minimum_order_amount, start_datetime, end_datetime 
                          FROM promotions 
                          WHERE is_active = 1 AND start_datetime <= NOW() AND end_datetime >= NOW() 
                          ORDER BY priority ASC, id DESC");
    $stmt->execute();
    $offers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Offers Page Promotions Query Error: " . $e->getMessage());
}
?>

<!-- Offers Header -->
<section class="section-padding py-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <span class="section-tagline mb-2 d-inline-block">Promotions</span>
        <h1 class="display-4 display-font mb-2">Sunshine Deals & Offers</h1>
        <p class="text-muted mb-0">Discover our current seasonal discounts, coupons, and all-day combo offerings.</p>
    </div>
</section>

<!-- Offers List -->
<section class="section-padding">
    <div class="container">
        
        <?php if (!empty($offers)): ?>
            <div class="row g-4 justify-content-center">
                <?php foreach ($offers as $index => $offer): ?>
                    <div class="col-md-6 col-lg-5">
                        <div class="offer-card" style="<?php echo ($index % 2 === 0) ? 'background-color: #FAF7F0;' : 'background-color: #FDF7F4;'; ?>">
                            <!-- Discount Badge -->
                            <span class="offer-discount-badge" style="<?php echo ($index % 2 === 0) ? 'background-color: var(--accent-sage);' : 'background-color: var(--accent-terracotta);'; ?>">
                                <?php 
                                    if ($offer['promotion_type'] === 'percentage') {
                                        echo escape((float)$offer['discount_value']) . '%';
                                    } else {
                                        echo '₹' . escape($offer['discount_value']);
                                    }
                                ?> OFF
                            </span>
                            
                            <h2 class="display-font h2 mb-3 text-dark"><?php echo escape($offer['name']); ?></h2>
                            <p class="text-muted mb-4"><?php echo escape($offer['description']); ?></p>
                            
                            <?php if ((float)$offer['minimum_order_amount'] > 0): ?>
                                <p class="text-muted small mb-3">
                                    <i class="bi bi-info-circle me-1"></i> Valid on orders of <strong><?php echo format_price($offer['minimum_order_amount']); ?></strong> or more.
                                </p>
                            <?php endif; ?>
                            
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <?php if (!empty($offer['coupon_code'])): ?>
                                    <div>
                                        <small class="text-muted d-block" style="font-size: 0.8rem;">Apply Coupon</small>
                                        <span class="offer-code" style="<?php echo ($index % 2 === 0) ? 'border-color: var(--accent-sage); color: var(--accent-sage);' : 'border-color: var(--accent-terracotta); color: var(--accent-terracotta);'; ?>">
                                            <?php echo escape($offer['coupon_code']); ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                                <a href="menu.php" class="btn btn-sage" style="<?php echo ($index % 2 === 0) ? 'background-color: var(--accent-sage); border-color: var(--accent-sage);' : 'background-color: var(--accent-terracotta); border-color: var(--accent-terracotta);'; ?>">
                                    Order Now
                                </a>
                            </div>
                            
                            <div class="mt-4 pt-3 border-top" style="border-color: rgba(0,0,0,0.05) !important;">
                                <small class="text-muted">
                                    <i class="bi bi-calendar3 me-1"></i> Ends: 
                                    <strong><?php echo date('M d, Y \a\t h:i A', strtotime($offer['end_datetime'])); ?></strong>
                                </small>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-brightness-high display-1 text-muted mb-3" style="color: var(--accent-gold) !important;"></i>
                <h3 class="display-font">No active offers today</h3>
                <p class="text-muted">We don't have any promotional campaigns running today. Explore our fresh menu for daily favorites!</p>
                <a href="menu.php" class="btn btn-sage mt-3">Explore Menu</a>
            </div>
        <?php endif; ?>
        
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
