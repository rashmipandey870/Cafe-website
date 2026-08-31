<?php
/**
 * gallery.php
 * Gallery Page - Responsive, editorial image grid for café interiors and plates
 */

$page_title = 'Our Gallery';
$page_description = 'Explore the visual story of Mellow & Meadow Café—our plant-filled seating areas, artisanal espresso pulls, and seasonal brunch preparations.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = get_db_connection();

// Fetch active images
$images = [];
try {
    $stmt = $db->prepare("SELECT title, image FROM gallery WHERE is_active = 1 ORDER BY id ASC");
    $stmt->execute();
    $images = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Gallery Page Query Error: " . $e->getMessage());
}
?>

<!-- Gallery Header -->
<section class="section-padding py-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <span class="section-tagline mb-2 d-inline-block">Visuals</span>
        <h1 class="display-4 display-font mb-2">Our Gallery</h1>
        <p class="text-muted mb-0">Step inside Mellow & Meadow—a bright sanctuary filled with plants, warmth, and fresh ingredients.</p>
    </div>
</section>

<!-- Gallery Grid -->
<section class="section-padding">
    <div class="container">
        
        <?php if (!empty($images)): ?>
            <div class="row g-4">
                <?php foreach ($images as $index => $img): ?>
                    <?php 
                        // Alternate between tall and wide ratios to achieve an editorial feel
                        $col_class = 'col-md-6 col-lg-4';
                        $card_class = 'gallery-wide';
                        
                        if ($index % 3 === 0) {
                            $col_class = 'col-md-6 col-lg-4';
                            $card_class = 'gallery-tall';
                        } elseif ($index % 3 === 2) {
                            $col_class = 'col-md-12 col-lg-4';
                            $card_class = 'gallery-wide';
                        }
                        
                        // Default high quality stock placeholders if paths are missing
                        $src = 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=500';
                        if ($img['title'] == 'Sunlit Seating Area') $src = 'https://images.unsplash.com/photo-1501339847302-ac426a4a7cbb?auto=format&fit=crop&q=80&w=600';
                        if ($img['title'] == 'Pouring the Matcha Latte') $src = 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&q=80&w=600';
                        if ($img['title'] == 'Our Freshly Baked Pastries') $src = 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&q=80&w=600';
                        if ($img['title'] == 'Artisanal Espresso Pull') $src = 'https://images.unsplash.com/photo-151097252790b-af4f982c78a2?auto=format&fit=crop&q=80&w=600';
                        if ($img['title'] == 'Rustic Sourdough Baking') $src = 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?auto=format&fit=crop&q=80&w=600';
                        if ($img['title'] == 'Slow Mornings at M&M') $src = 'https://images.unsplash.com/photo-1517248135467-4c7edcad34c4?auto=format&fit=crop&q=80&w=600';
                        
                        // Use database path if valid
                        if (!empty($img['image']) && file_exists(__DIR__ . '/../' . $img['image'])) {
                            $src = BASE_URL . '/' . $img['image'];
                        }
                    ?>
                    
                    <div class="<?php echo $col_class; ?>">
                        <div class="gallery-grid-item <?php echo $card_class; ?> shadow-none">
                            <img src="<?php echo $src; ?>" alt="<?php echo escape($img['title']); ?>" class="gallery-grid-img" loading="lazy">
                            <div class="gallery-grid-overlay">
                                <h3 class="gallery-grid-title"><?php echo escape($img['title']); ?></h3>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-images display-1 text-muted mb-3" style="color: var(--accent-gold) !important;"></i>
                <h3 class="display-font">No images uploaded</h3>
                <p class="text-muted">The gallery is currently empty. Check back soon for visual updates.</p>
                <a href="index.php" class="btn btn-sage mt-2">Back to Home</a>
            </div>
        <?php endif; ?>
        
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
