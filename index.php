<?php
/**
 * index.php
 * Homepage - Fresh, Sunlit Café Landing Page
 */

$page_title = 'Fresh Coffee & Good Moments';
$page_description = 'Welcome to Mellow & Meadow, a plant-rich Specialty Café serving single-origin coffees, organic teas, artisanal sourdough, and seasonal pastry selections.';

// Includes base layouts
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = get_db_connection();

// 1. Fetch Featured Menu Items
$featured_items = [];
try {
    $stmt = $db->prepare("SELECT id, name, description, price, image, is_vegetarian FROM menu_items WHERE is_featured = 1 AND is_available = 1 LIMIT 3");
    $stmt->execute();
    $featured_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Home Featured Query Error: " . $e->getMessage());
}

// 2. Fetch Active Offers (Valid within date range)
$active_offers = [];
try {
    $stmt = $db->prepare("SELECT name as title, description, discount_value, promotion_type, coupon_code FROM promotions WHERE is_active = 1 AND start_datetime <= NOW() AND end_datetime >= NOW() LIMIT 2");
    $stmt->execute();
    $active_offers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Home Offers Query Error: " . $e->getMessage());
}

// 3. Fetch Approved Customer Reviews
$approved_reviews = [];
try {
    $stmt = $db->prepare("SELECT customer_name, rating, comment FROM reviews WHERE is_approved = 1 ORDER BY id DESC LIMIT 5");
    $stmt->execute();
    $approved_reviews = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Home Reviews Query Error: " . $e->getMessage());
}
?>

<!-- 1. Hero Section -->
<section class="hero-section d-flex align-items-center">
    <div class="container">
        <div class="row align-items-center g-5">
            <!-- Hero Text -->
            <div class="col-lg-6 order-2 order-lg-1 fade-in-up">
                <span class="hero-subtitle mb-2 d-inline-block">Specialty Coffee & All-Day Brunch</span>
                <h1 class="hero-title display-3">Your daily dose of something delicious.</h1>
                <p class="hero-text text-muted">
                    Fresh coffee. Fresh organic ingredients. Good moments. Step into Mellow & Meadow—a bright, green haven designed for slow mornings and happy spaces.
                </p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="menu.php" class="btn btn-sage px-4 py-3">Explore Menu</a>
                    <a href="reservation.php" class="btn btn-outline-sage px-4 py-3">Book a Table</a>
                </div>
            </div>
            <!-- Hero Image -->
            <div class="col-lg-6 order-1 order-lg-2">
                <div class="hero-image-container">
                    <!-- Standard high-quality placeholder representing bright natural daylight cafe -->
                    <img src="https://images.unsplash.com/photo-1554118811-1e0d58224f24?auto=format&fit=crop&q=80&w=800" alt="Bright Specialty Cafe Interior" class="img-fluid hero-img">
                    <div class="hero-img-overlay-card d-none d-md-block">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-patch-check-fill text-success" style="color: var(--accent-sage) !important; font-size: 1.5rem;"></i>
                            <div>
                                <h6 class="mb-0 fw-bold">Freshly Brewed Daily</h6>
                                <small class="text-muted">From 8:00 AM - 10:00 PM</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 2. Editorial About Section -->
<section class="section-padding bg-cream">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5">
                <div class="position-relative">
                    <img src="https://images.unsplash.com/photo-1498804103079-a6351b050096?auto=format&fit=crop&q=80&w=600" alt="Coffee Brewing Closeup" class="img-fluid rounded shadow-sm" style="border-radius: var(--border-radius-md) !important; width: 100%; max-height: 450px; object-fit: cover;">
                </div>
            </div>
            <div class="col-lg-7">
                <span class="section-tagline mb-2 d-inline-block">About Our Café</span>
                <h2 class="section-title display-4">Made for slow mornings, quick catch-ups, and everything in between.</h2>
                <p class="lead mb-4" style="color: var(--accent-coffee); font-family: var(--font-heading); font-size: 1.5rem; font-style: italic;">
                    "We believe that a day started with fresh coffee and locally sourced wholesome food is a day built for joy."
                </p>
                <p class="text-muted mb-4">
                    Mellow & Meadow was born out of a love for high-quality specialty coffee and wholesome, seasonal bakes. We source single-origin arabica beans and roast them gently to lock in complex floral profiles. All our breads are slow-fermented organic sourdough, and our vegetables are harvested daily from small organic farms near the city.
                </p>
                <a href="about.php" class="btn btn-outline-sage">Discover Our Story</a>
            </div>
        </div>
    </div>
</section>

<!-- 3. Customer Favorites Section (Featured Items) -->
<section class="section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tagline mb-2 d-inline-block">Customer Favourites</span>
            <h2 class="section-title">The items everyone comes back for</h2>
        </div>
        
        <div class="row g-4">
            <?php if (!empty($featured_items)): ?>
                <?php foreach ($featured_items as $item): ?>
                    <div class="col-md-4">
                        <div class="menu-card">
                            <div class="menu-card-img-wrapper">
                                <!-- Clean fallback image if custom image is missing -->
                                <?php 
                                    $img_src = 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Heirloom Avocado Toast') $img_src = 'https://images.unsplash.com/photo-1541532713592-79a0317b6b77?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Ceremonial Iced Matcha Latte') $img_src = 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Vanilla Bean Latte') $img_src = 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=500';
                                ?>
                                <img src="<?php echo $img_src; ?>" alt="<?php echo escape($item['name']); ?>" class="menu-card-img">
                            </div>
                            <div class="menu-card-body">
                                <div class="menu-card-title">
                                    <h4 class="mb-0 text-dark"><?php echo escape($item['name']); ?></h4>
                                    <span class="menu-card-price"><?php echo format_price($item['price']); ?></span>
                                </div>
                                <p class="menu-card-desc mt-2"><?php echo escape($item['description']); ?></p>
                                <div class="menu-card-footer">
                                    <span class="veg-indicator <?php echo $item['is_vegetarian'] ? 'veg' : 'non-veg'; ?>">
                                        <i class="bi bi-circle-fill me-1"></i><?php echo $item['is_vegetarian'] ? 'Vegetarian' : 'Non-Veg'; ?>
                                    </span>
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo escape($item['name']); ?>', <?php echo $item['price']; ?>, '<?php echo $img_src; ?>')" aria-label="Add to cart">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">Explore our full menu to see our fresh plates.</p>
                    <a href="menu.php" class="btn btn-sage mt-2">View Full Menu</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- 4. Promotions & Offers Section -->
<?php if (!empty($active_offers)): ?>
<section class="section-padding bg-cream">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tagline mb-2 d-inline-block">Special Promotions</span>
            <h2 class="section-title">Today's Sunshine Deals</h2>
        </div>
        <div class="row g-4 justify-content-center">
            <?php foreach ($active_offers as $offer): ?>
                <div class="col-md-6">
                    <div class="offer-card">
                        <span class="offer-discount-badge">
                            <?php 
                                if ($offer['promotion_type'] === 'percentage') {
                                    echo escape((float)$offer['discount_value']) . '%';
                                } else {
                                    echo '₹' . escape($offer['discount_value']);
                                }
                            ?> OFF
                        </span>
                        <h3 class="display-font h2 mb-3" style="color: var(--color-text);"><?php echo escape($offer['title']); ?></h3>
                        <p class="text-muted mb-4"><?php echo escape($offer['description']); ?></p>
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <?php if (!empty($offer['coupon_code'])): ?>
                                <div>
                                    <small class="text-muted d-block">Use Coupon Code</small>
                                    <span class="offer-code"><?php echo escape($offer['coupon_code']); ?></span>
                                </div>
                            <?php endif; ?>
                            <a href="menu.php" class="btn btn-sage mt-2">Order Now</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 5. Testimonials Section -->
<?php if (!empty($approved_reviews)): ?>
<section class="section-padding">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tagline mb-2 d-inline-block">What People Say</span>
            <h2 class="section-title">Happy Moments Shared</h2>
        </div>
        
        <div id="reviewCarousel" class="carousel slide" data-bs-ride="carousel">
            <div class="carousel-inner">
                <?php foreach ($approved_reviews as $index => $rev): ?>
                    <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                        <div class="review-card">
                            <div class="review-stars">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i class="bi <?php echo $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <blockquote class="review-quote">
                                "<?php echo escape($rev['comment']); ?>"
                            </blockquote>
                            <div class="review-author">— <?php echo escape($rev['customer_name']); ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Controls -->
            <button class="carousel-control-prev" type="button" data-bs-target="#reviewCarousel" data-bs-slide="prev" style="filter: invert(1); width: 5%;">
                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Previous</span>
            </button>
            <button class="carousel-control-next" type="button" data-bs-target="#reviewCarousel" data-bs-slide="next" style="filter: invert(1); width: 5%;">
                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                <span class="visually-hidden">Next</span>
            </button>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- 6. Gallery Section Preview -->
<section class="section-padding bg-cream">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tagline mb-2 d-inline-block">Our Space</span>
            <h2 class="section-title">Bright moments captured in frame</h2>
        </div>
        <div class="row g-4">
            <!-- 3 Columns Asymmetric -->
            <div class="col-md-4">
                <div class="gallery-grid-item gallery-tall shadow-none">
                    <img src="https://images.unsplash.com/photo-1495474472287-4d71bcdd2085?auto=format&fit=crop&q=80&w=500" alt="Barista pulling coffee" class="gallery-grid-img" loading="lazy">
                    <div class="gallery-grid-overlay">
                        <h4 class="gallery-grid-title">Slow Mornings</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="gallery-grid-item gallery-wide shadow-none">
                    <img src="https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&q=80&w=500" alt="Espresso extraction" class="gallery-grid-img" loading="lazy">
                    <div class="gallery-grid-overlay">
                        <h4 class="gallery-grid-title">Our Espresso Pull</h4>
                    </div>
                </div>
                <div class="gallery-grid-item gallery-wide shadow-none mt-4">
                    <img src="https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=500" alt="Modern bright interior" class="gallery-grid-img" loading="lazy">
                    <div class="gallery-grid-overlay">
                        <h4 class="gallery-grid-title">Sunlit Seating</h4>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="gallery-grid-item gallery-tall shadow-none">
                    <img src="https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=500" alt="Sourdough toasts baking" class="gallery-grid-img" loading="lazy">
                    <div class="gallery-grid-overlay">
                        <h4 class="gallery-grid-title">Fresh Baked Pastries</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="text-center mt-4">
            <a href="gallery.php" class="btn btn-outline-sage">View Full Gallery</a>
        </div>
    </div>
</section>

<!-- 7. Opening Hours & Location Info -->
<section class="section-padding">
    <div class="container">
        <div class="row g-4 justify-content-center">
            <!-- Hours -->
            <div class="col-lg-6">
                <div class="contact-info-card h-100 shadow-xs border">
                    <h3 class="display-font h2 mb-4" style="color: var(--accent-coffee);">Opening Hours</h3>
                    <p class="mb-4 text-muted">We are open every day of the week, including holidays, to bring you fresh artisanal food and single-origin coffee.</p>
                    
                    <div class="d-flex justify-content-between border-bottom py-2" style="border-color: #E8E0CE !important;">
                        <span class="fw-bold">Monday - Friday</span>
                        <span>8:00 AM - 10:00 PM</span>
                    </div>
                    <div class="d-flex justify-content-between border-bottom py-2" style="border-color: #E8E0CE !important;">
                        <span class="fw-bold">Saturday - Sunday</span>
                        <span>8:00 AM - 10:00 PM</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span class="fw-bold text-success" style="color: var(--accent-sage) !important;">Kitchen Timings</span>
                        <span>Closes at 9:30 PM</span>
                    </div>
                </div>
            </div>
            
            <!-- Visit Us & Assistance -->
            <div class="col-lg-6">
                <div class="contact-info-card h-100 shadow-xs border">
                    <h3 class="display-font h2 mb-4" style="color: var(--accent-coffee);">Visit Mellow & Meadow</h3>
                    <p class="text-muted mb-3"><i class="bi bi-geo-alt-fill text-sage me-2"></i><?php echo escape($settings['cafe_address']); ?></p>
                    <p class="text-muted mb-3"><i class="bi bi-telephone-fill text-sage me-2"></i><?php echo escape($settings['cafe_phone']); ?></p>
                    <p class="text-muted mb-4"><i class="bi bi-envelope-fill text-sage me-2"></i><?php echo escape($settings['cafe_email']); ?></p>
                    
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="menu.php" class="btn btn-sage text-white px-4 py-2">Order Online</a>
                        <?php if (!empty($settings['cafe_whatsapp'])): ?>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['cafe_whatsapp']); ?>" class="btn btn-outline-sage px-4 py-2" target="_blank">
                                <i class="bi bi-whatsapp me-1"></i> WhatsApp
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- 8. CTA Section -->
<section class="section-padding text-center bg-cream shadow-none border-top" style="border-color: #EFEAE0 !important;">
    <div class="container py-4">
        <h2 class="display-font display-4 mb-3" style="color: var(--accent-coffee);">GOOD COFFEE IS BETTER TOGETHER.</h2>
        <p class="lead text-muted mb-4 mx-auto" style="max-width: 600px;">Come by, grab a seat, work, read, or catch up with friends. We have hot coffee, fresh food, and plenty of sunlight waiting for you.</p>
        <div class="d-flex justify-content-center gap-3">
            <a href="menu.php" class="btn btn-sage px-4 py-3">Explore Menu</a>
            <a href="contact.php" class="btn btn-outline-sage px-4 py-3">Find Us</a>
        </div>
    </div>
</section>

<?php 
// Include footer
require_once __DIR__ . '/includes/footer.php'; 
?>
