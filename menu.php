<?php
/**
 * menu.php
 * Public Menu Page - Database-Driven Category Filters and Cards
 */

$page_title = 'Our Menu';
$page_description = 'Discover the Mellow & Meadow menu—crafted with organic, fresh ingredients. Browse specialty coffees, teas, sourdough toasts, brunch bowls, and pastries.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = get_db_connection();

// 1. Fetch All Active Categories
$categories = [];
try {
    $stmt = $db->prepare("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY id ASC");
    $stmt->execute();
    $categories = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Menu Categories Query Error: " . $e->getMessage());
}

// 2. Determine Selected Category Filter (Query Param)
$selected_category_id = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// 3. Fetch Available Menu Items based on Category Filter
$menu_items = [];
try {
    if ($selected_category_id > 0) {
        $stmt = $db->prepare("SELECT m.id, m.name, m.description, m.price, m.image, m.is_vegetarian, c.name as category_name 
                              FROM menu_items m 
                              JOIN categories c ON m.category_id = c.id 
                              WHERE m.is_available = 1 AND c.is_active = 1 AND m.category_id = :category_id 
                              ORDER BY m.id ASC");
        $stmt->bindParam(':category_id', $selected_category_id, PDO::PARAM_INT);
    } else {
        $stmt = $db->prepare("SELECT m.id, m.name, m.description, m.price, m.image, m.is_vegetarian, c.name as category_name 
                              FROM menu_items m 
                              JOIN categories c ON m.category_id = c.id 
                              WHERE m.is_available = 1 AND c.is_active = 1 
                              ORDER BY m.category_id ASC, m.id ASC");
    }
    $stmt->execute();
    $menu_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Menu Items Query Error: " . $e->getMessage());
}
?>

<!-- Menu Hero Header -->
<section class="section-padding" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <span class="section-tagline mb-2 d-inline-block">Organic & Handcrafted</span>
        <h1 class="display-3 display-font mb-3">Our Menu</h1>
        <p class="lead text-muted mx-auto" style="max-width: 600px;">
            Every dish is made to order with organic ingredients. Every cup of coffee is pulled with freshly roasted single-origin beans.
        </p>
    </div>
</section>

<!-- Menu Content Section -->
<section class="section-padding">
    <div class="container">
        
        <!-- Category Filter Pills -->
        <div class="row mb-5">
            <div class="col-12 text-center">
                <div class="category-filter-pills d-flex flex-wrap justify-content-center">
                    <!-- 'All' Pill -->
                    <a href="menu.php" class="btn <?php echo ($selected_category_id === 0) ? 'active' : ''; ?>">All Items</a>
                    
                    <!-- Dynamic Pills -->
                    <?php if (!empty($categories)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <a href="menu.php?category=<?php echo $cat['id']; ?>" 
                               class="btn <?php echo ($selected_category_id === (int)$cat['id']) ? 'active' : ''; ?>">
                                <?php echo escape($cat['name']); ?>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Menu Items Grid -->
        <div class="row g-4">
            <?php if (!empty($menu_items)): ?>
                <?php foreach ($menu_items as $item): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="menu-card">
                            <!-- Image Section -->
                            <div class="menu-card-img-wrapper">
                                <?php 
                                    // Assign beautiful Unsplash natural food placeholders if custom image path does not exist
                                    $img_src = 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Heirloom Avocado Toast') $img_src = 'https://images.unsplash.com/photo-1541532713592-79a0317b6b77?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Ceremonial Iced Matcha Latte') $img_src = 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Vanilla Bean Latte') $img_src = 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Sage Honey Cortado') $img_src = 'https://images.unsplash.com/photo-1534778101976-62847782c213?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Lavender Cold Brew') $img_src = 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Peach Hibiscus Iced Tea') $img_src = 'https://images.unsplash.com/photo-1556881286-fc6915169721?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Wild Mushroom Sourdough') $img_src = 'https://images.unsplash.com/photo-1603046891726-36bfd957e0bf?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Terracotta Brunch Bowl') $img_src = 'https://images.unsplash.com/photo-1512621776951-a57141f2eefd?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Almond Sourdough Croissant') $img_src = 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Meyer Lemon Tart') $img_src = 'https://images.unsplash.com/photo-1519869325930-281384150729?auto=format&fit=crop&q=80&w=500';
                                    if ($item['name'] == 'Chocolate Babka Slice') $img_src = 'https://images.unsplash.com/photo-1607958996333-41aef7caefaa?auto=format&fit=crop&q=80&w=500';
                                    
                                    // Use database uploaded image if set
                                    if (!empty($item['image']) && file_exists(__DIR__ . '/../' . $item['image'])) {
                                        $img_src = BASE_URL . '/' . $item['image'];
                                    }
                                ?>
                                <img src="<?php echo $img_src; ?>" alt="<?php echo escape($item['name']); ?>" class="menu-card-img" loading="lazy">
                            </div>
                            
                            <!-- Card Body Info -->
                            <div class="menu-card-body">
                                <div class="menu-card-title">
                                    <h4 class="mb-0 text-dark"><?php echo escape($item['name']); ?></h4>
                                    <span class="menu-card-price"><?php echo format_price($item['price']); ?></span>
                                </div>
                                
                                <span class="badge bg-secondary mb-2 align-self-start fw-normal px-2 py-1 text-capitalize" style="font-size: 0.75rem; background-color: var(--bg-secondary) !important; color: var(--color-muted) !important; border: 1px solid #EFEAE0;">
                                    <?php echo escape($item['category_name']); ?>
                                </span>
                                
                                <p class="menu-card-desc mt-2"><?php echo escape($item['description']); ?></p>
                                
                                <!-- Card Footer Actions -->
                                <div class="menu-card-footer">
                                    <span class="veg-indicator <?php echo $item['is_vegetarian'] ? 'veg' : 'non-veg'; ?>">
                                        <i class="bi bi-circle-fill me-1"></i><?php echo $item['is_vegetarian'] ? 'Vegetarian' : 'Non-Veg'; ?>
                                    </span>
                                    
                                    <!-- Dynamic JS cart call -->
                                    <button class="add-to-cart-btn" 
                                            onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes(escape($item['name'])); ?>', <?php echo $item['price']; ?>, '<?php echo $img_src; ?>')" 
                                            aria-label="Add to cart">
                                        <i class="bi bi-plus-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-search display-2 text-muted mb-3" style="color: var(--accent-gold) !important;"></i>
                    <h3 class="display-font">No items found</h3>
                    <p class="text-muted">No available items match the selected category right now. Please check back later.</p>
                    <a href="menu.php" class="btn btn-sage mt-2">View All Items</a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
