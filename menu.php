<?php
/**
 * menu.php
 * Mobile-First Artisanal Menu with Live Search, Horizontal Category Scroller & Table Ordering
 */

$page_title = 'Our Menu';
$page_description = 'Discover the Mellow & Meadow menu—crafted with organic, fresh ingredients. Specialty coffees, teas, sourdough toasts, brunch bowls, and pastries.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = get_db_connection();
$table_num = isset($_SESSION['table_number']) ? $_SESSION['table_number'] : null;

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

// 3. Fetch Available Menu Items
$menu_items = [];
try {
    if ($selected_category_id > 0) {
        $stmt = $db->prepare("SELECT m.id, m.name, m.description, m.price, m.image, m.is_vegetarian, m.category_id, c.name as category_name 
                              FROM menu_items m 
                              JOIN categories c ON m.category_id = c.id 
                              WHERE m.is_available = 1 AND c.is_active = 1 AND m.category_id = :category_id 
                              ORDER BY m.id ASC");
        $stmt->bindParam(':category_id', $selected_category_id, PDO::PARAM_INT);
    } else {
        $stmt = $db->prepare("SELECT m.id, m.name, m.description, m.price, m.image, m.is_vegetarian, m.category_id, c.name as category_name 
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

<!-- Table Ordering Top Banner (if scanned from QR) -->
<?php if (!empty($table_num)): ?>
    <div class="bg-sage text-white py-2 px-3 text-center shadow-sm">
        <div class="container d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span class="small fw-bold">
                <i class="bi bi-qr-code-scan me-2"></i>Contactless Table Service: <strong>TABLE <?php echo escape($table_num); ?></strong>
            </span>
            <span class="badge bg-white text-dark small">Dine-In Active</span>
        </div>
    </div>
<?php endif; ?>

<!-- Menu Header -->
<section class="py-4 py-md-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <span class="section-tagline mb-1 d-inline-block">Organic & Handcrafted</span>
        <h1 class="display-5 display-font mb-2">Our Menu</h1>
        <p class="text-muted mx-auto mb-3" style="max-width: 500px; font-size: 0.95rem;">
            Every cup pulled fresh. Every plate prepared to order with organic, local produce.
        </p>

        <!-- Live Menu Search Box (Swiggy / Zomato style) -->
        <div class="mx-auto" style="max-width: 480px;">
            <div class="input-group shadow-sm rounded-pill overflow-hidden bg-white border">
                <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="bi bi-search"></i></span>
                <input type="text" id="menu-search-input" class="form-control border-0 shadow-none ps-2" placeholder="Search coffee, bowls, toasts, desserts..." aria-label="Search menu">
                <button class="btn btn-white border-0 pe-3 text-muted d-none" id="clear-search-btn" type="button"><i class="bi bi-x-circle-fill"></i></button>
            </div>
        </div>
    </div>
</section>

<!-- Sticky Horizontal Category Scroll Bar -->
<div class="sticky-top bg-white border-bottom shadow-xs py-2" style="top: 0px; z-index: 1020;">
    <div class="container">
        <div class="category-scroll-bar">
            <a href="menu.php" class="category-pill-item <?php echo ($selected_category_id === 0) ? 'active' : ''; ?>">
                <i class="bi bi-grid-fill me-1"></i> All Items
            </a>
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $cat): ?>
                    <a href="menu.php?category=<?php echo $cat['id']; ?>" 
                       class="category-pill-item <?php echo ($selected_category_id === (int)$cat['id']) ? 'active' : ''; ?>">
                        <?php echo escape($cat['name']); ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Menu Items Grid -->
<section class="section-padding py-4 py-md-5">
    <div class="container">
        
        <!-- Search Results Notice -->
        <div id="search-results-count" class="text-muted mb-3 small d-none"></div>

        <div class="row g-3 g-md-4" id="menu-items-grid">
            <?php if (!empty($menu_items)): ?>
                <?php foreach ($menu_items as $item): ?>
                    <?php 
                        // Image fallback resolution
                        $img_src = 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=400';
                        if ($item['name'] == 'Heirloom Avocado Toast') $img_src = 'https://images.unsplash.com/photo-1541532713592-79a0317b6b77?auto=format&fit=crop&q=80&w=400';
                        if ($item['name'] == 'Ceremonial Iced Matcha Latte') $img_src = 'https://images.unsplash.com/photo-1536256263959-770b48d82b0a?auto=format&fit=crop&q=80&w=400';
                        if ($item['name'] == 'Vanilla Bean Latte') $img_src = 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&q=80&w=400';
                        if ($item['name'] == 'Almond Sourdough Croissant') $img_src = 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?auto=format&fit=crop&q=80&w=400';
                        if ($item['name'] == 'Terracotta Brunch Bowl') $img_src = 'https://images.unsplash.com/photo-1540420773420-3366772f4999?auto=format&fit=crop&q=80&w=400';
                        if ($item['name'] == 'Sage Honey Cortado') $img_src = 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&q=80&w=400';
                        if ($item['name'] == 'Meyer Lemon Tart') $img_src = 'https://images.unsplash.com/photo-1519869325930-281384150729?auto=format&fit=crop&q=80&w=400';
                        if ($item['name'] == 'Lavender Cold Brew') $img_src = 'https://images.unsplash.com/photo-1517701604599-bb29b565090c?auto=format&fit=crop&q=80&w=400';
                        
                        if (!empty($item['image']) && file_exists(__DIR__ . '/' . $item['image'])) {
                            $img_src = BASE_URL . '/' . escape($item['image']);
                        }
                    ?>
                    <div class="col-12 col-md-6 col-lg-4 menu-item-col" 
                         data-name="<?php echo strtolower(escape($item['name'])); ?>"
                         data-desc="<?php echo strtolower(escape($item['description'])); ?>"
                         data-category="<?php echo (int)$item['category_id']; ?>">
                        
                        <div class="food-card-modern shadow-xs">
                            <div class="d-flex justify-content-between align-items-start gap-3">
                                <!-- Food Info Column -->
                                <div class="flex-grow-1">
                                    <div class="mb-1">
                                        <?php if ($item['is_vegetarian']): ?>
                                            <span class="food-badge-veg me-1" title="Vegetarian"></span>
                                        <?php else: ?>
                                            <span class="food-badge-nonveg me-1" title="Non-Vegetarian"></span>
                                        <?php endif; ?>
                                        <span class="badge bg-light text-muted fw-normal small px-2 py-0"><?php echo escape($item['category_name']); ?></span>
                                    </div>
                                    
                                    <h5 class="fw-bold mb-1 text-dark fs-6" style="letter-spacing: -0.2px;"><?php echo escape($item['name']); ?></h5>
                                    
                                    <div class="fw-bold text-sage mb-2" style="font-size: 1.05rem;">
                                        <?php echo format_price($item['price']); ?>
                                    </div>
                                    
                                    <p class="text-muted small mb-0 line-clamp-2" style="font-size: 0.82rem; line-height: 1.35;">
                                        <?php echo escape($item['description']); ?>
                                    </p>
                                </div>

                                <!-- Food Image & Add Button Column -->
                                <div class="d-flex flex-column align-items-center flex-shrink-0" style="width: 105px;">
                                    <img src="<?php echo escape($img_src); ?>" 
                                         alt="<?php echo escape($item['name']); ?>" 
                                         class="rounded-3 mb-2 shadow-xs object-fit-cover" 
                                         style="width: 100px; height: 90px;"
                                         loading="lazy">
                                    
                                    <button onclick="addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes(escape($item['name'])); ?>', <?php echo $item['price']; ?>, '<?php echo addslashes(escape($img_src)); ?>')" 
                                            class="btn add-btn-pill btn-sm text-uppercase" 
                                            aria-label="Add <?php echo escape($item['name']); ?> to order">
                                        <i class="bi bi-plus-lg me-1"></i>ADD
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-5">
                    <i class="bi bi-cup-straw display-1 text-muted"></i>
                    <h3 class="display-font mt-3">No Items Found</h3>
                    <p class="text-muted">There are currently no items available in this category.</p>
                    <a href="menu.php" class="btn btn-sage text-white mt-2">View All Items</a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Dynamic No Search Match Alert -->
        <div id="no-search-results" class="text-center py-5 d-none">
            <i class="bi bi-search display-3 text-muted mb-3 d-block"></i>
            <h4 class="display-font">No matching dishes found</h4>
            <p class="text-muted small">Try searching for other keywords like "latte", "toast", "matcha", or "croissant".</p>
            <button class="btn btn-outline-sage btn-sm mt-2" onclick="document.getElementById('menu-search-input').value=''; document.getElementById('menu-search-input').dispatchEvent(new Event('input'));">Clear Search</button>
        </div>

    </div>
</section>

<!-- Client-side Realtime Menu Search Script -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('menu-search-input');
    const clearBtn = document.getElementById('clear-search-btn');
    const menuCols = document.querySelectorAll('.menu-item-col');
    const noResults = document.getElementById('no-search-results');
    const countEl = document.getElementById('search-results-count');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const query = this.value.trim().toLowerCase();
            let visibleCount = 0;

            if (query.length > 0) {
                clearBtn.classList.remove('d-none');
            } else {
                clearBtn.classList.add('d-none');
            }

            menuCols.forEach(col => {
                const name = col.getAttribute('data-name') || '';
                const desc = col.getAttribute('data-desc') || '';

                if (name.includes(query) || desc.includes(query)) {
                    col.style.display = '';
                    visibleCount++;
                } else {
                    col.style.display = 'none';
                }
            });

            if (visibleCount === 0) {
                noResults.classList.remove('d-none');
                countEl.classList.add('d-none');
            } else {
                noResults.classList.add('d-none');
                if (query.length > 0) {
                    countEl.textContent = `Showing ${visibleCount} dish${visibleCount === 1 ? '' : 'es'} matching "${query}"`;
                    countEl.classList.remove('d-none');
                } else {
                    countEl.classList.add('d-none');
                }
            }
        });

        clearBtn.addEventListener('click', () => {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
            searchInput.focus();
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
