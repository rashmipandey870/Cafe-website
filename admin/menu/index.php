<?php
/**
 * admin/menu/index.php
 * Administrative Menu Management (CRUD operations + Soft Deactivation fallbacks)
 */

$page_title = 'Manage Menu Items';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();
$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$menu_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$errors = [];
$menu_data = null;

// Fetch active categories for form dropdown selects
$categories = [];
try {
    $categories = $db->query("SELECT id, name FROM categories ORDER BY id ASC")->fetchAll();
} catch (PDOException $e) {
    error_log("Menu categories fetch error: " . $e->getMessage());
}

// Fetch menu item data if edit/toggle/delete action requested
if ($menu_id > 0 && ($action === 'edit' || $action === 'toggle' || $action === 'delete')) {
    try {
        $stmt = $db->prepare("SELECT * FROM menu_items WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $menu_id]);
        $menu_data = $stmt->fetch();
        if (!$menu_data) {
            set_flash_message('danger', 'Menu item not found.');
            header("Location: index.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Menu Item Fetch Error: " . $e->getMessage());
    }
}

// Handle Form Submissions (Add / Edit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    // Validate CSRF
    enforce_csrf();
    
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
    $category_id = (int)filter_input(INPUT_POST, 'category_id', FILTER_VALIDATE_INT);
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_DEFAULT));
    $price = (float)filter_input(INPUT_POST, 'price', FILTER_VALIDATE_FLOAT);
    
    $is_vegetarian = isset($_POST['is_vegetarian']) ? 1 : 0;
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $is_featured = isset($_POST['is_featured']) ? 1 : 0;
    
    // Validations
    if (empty($name)) {
        $errors[] = "Menu item name is required.";
    }
    if ($category_id <= 0) {
        $errors[] = "Please select a valid category.";
    }
    if ($price <= 0) {
        $errors[] = "Price must be a positive decimal number.";
    }
    
    // File Upload
    $image_path = '';
    if (isset($_FILES['menu_image']) && $_FILES['menu_image']['error'] === UPLOAD_ERR_OK) {
        $uploaded = upload_image($_FILES['menu_image'], 'uploads/menu/');
        if ($uploaded) {
            $image_path = $uploaded;
        } else {
            $errors[] = "Failed to upload image. Must be a valid JPG/PNG/WEBP image under 2MB.";
        }
    }
    
    // DB Processing
    if (empty($errors)) {
        try {
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO menu_items 
                    (category_id, name, description, price, image, is_vegetarian, is_available, is_featured) 
                    VALUES 
                    (:category_id, :name, :description, :price, :image, :is_vegetarian, :is_available, :is_featured)");
                $stmt->execute([
                    ':category_id' => $category_id,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':image' => $image_path ? $image_path : 'assets/images/menu_placeholder.jpg',
                    ':is_vegetarian' => $is_vegetarian,
                    ':is_available' => $is_available,
                    ':is_featured' => $is_featured
                ]);
                set_flash_message('success', 'Menu item added successfully!');
            } else { // edit
                if (empty($image_path)) {
                    $image_path = $menu_data['image'];
                }
                
                $stmt = $db->prepare("UPDATE menu_items 
                    SET category_id = :category_id, name = :name, description = :description, price = :price, 
                        image = :image, is_vegetarian = :is_vegetarian, is_available = :is_available, is_featured = :is_featured 
                    WHERE id = :id");
                $stmt->execute([
                    ':category_id' => $category_id,
                    ':name' => $name,
                    ':description' => $description,
                    ':price' => $price,
                    ':image' => $image_path,
                    ':is_vegetarian' => $is_vegetarian,
                    ':is_available' => $is_available,
                    ':is_featured' => $is_featured,
                    ':id' => $menu_id
                ]);
                set_flash_message('success', 'Menu item updated successfully!');
            }
            
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            error_log("Menu CRUD DB Write Exception: " . $e->getMessage());
            $errors[] = "A system error occurred. Please try again.";
        }
    }
}

// 7. Handle Status Toggles & Deletions CSRF Verification
if (($action === 'toggle' || $action === 'delete') && $menu_id > 0) {
    $get_token = isset($_GET['token']) ? trim($_GET['token']) : '';
    if (!verify_csrf_get($get_token)) {
        set_flash_message('danger', 'Security token invalid or expired. Action blocked.');
        header("Location: index.php");
        exit;
    }
}

// 8. Handle Status Toggles (Availability quick toggles)
if ($action === 'toggle' && $menu_id > 0) {
    try {
        $new_status = $menu_data['is_available'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE menu_items SET is_available = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $menu_id]);
        
        set_flash_message('success', 'Menu item availability status updated.');
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        error_log("Menu item toggle exception: " . $e->getMessage());
        set_flash_message('danger', 'Failed to toggle availability status.');
    }
}

// 9. Handle Safe Deletions (Restrict Foreign Keys with Soft Deactivation)
if ($action === 'delete' && $menu_id > 0) {
    try {
        // Attempt hard delete
        $stmt = $db->prepare("DELETE FROM menu_items WHERE id = :id");
        $stmt->execute([':id' => $menu_id]);
        
        set_flash_message('success', 'Menu item deleted successfully.');
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        // Check if SQLSTATE matches integrity constraint violation (e.g. 23000: foreign key restrict)
        if ($e->getCode() === '23000' || strpos($e->getMessage(), '1451') !== false) {
            try {
                // FALLBACK: Soft Deactivation to satisfy integrity rules
                $stmt = $db->prepare("UPDATE menu_items SET is_available = 0, is_featured = 0 WHERE id = :id");
                $stmt->execute([':id' => $menu_id]);
                
                set_flash_message('warning', 'This item was ordered in the past and cannot be deleted. It has been deactivated and marked as unavailable instead to preserve order history.');
            } catch (PDOException $ex) {
                error_log("Menu Soft Deactivate Error: " . $ex->getMessage());
                set_flash_message('danger', 'Failed to deactivate menu item.');
            }
        } else {
            error_log("Menu Delete Exception: " . $e->getMessage());
            set_flash_message('danger', 'Failed to delete menu item.');
        }
        header("Location: index.php");
        exit;
    }
}
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- CREATE OR EDIT FORM VIEW -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card admin-card p-4 shadow-sm bg-white">
                <h3 class="display-font text-dark fs-4 mb-4 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">
                    <?php echo ($action === 'add') ? 'Create Menu Item' : 'Edit Menu Item: ' . escape($menu_data['name']); ?>
                </h3>
                
                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger" style="font-size: 0.9rem;">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo escape($err); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>
                
                <form action="index.php?action=<?php echo $action; ?><?php echo ($action === 'edit') ? '&id=' . $menu_id : ''; ?>" method="POST" enctype="multipart/form-data">
                    <!-- CSRF Protection Field -->
                    <?php echo csrf_field(); ?>
                    
                    <div class="row g-3">
                        <!-- Item Name -->
                        <div class="col-md-8">
                            <label for="name" class="form-label">Menu Item Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. Vanilla Bean Latte" value="<?php echo isset($_POST['name']) ? escape($_POST['name']) : ($action === 'edit' ? escape($menu_data['name']) : ''); ?>">
                        </div>
                        
                        <!-- Category Selection -->
                        <div class="col-md-4">
                            <label for="category_id" class="form-label">Category <span class="text-danger">*</span></label>
                            <select class="form-select" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php if (!empty($categories)): ?>
                                    <?php 
                                        $curr_cat = isset($_POST['category_id']) ? (int)$_POST['category_id'] : ($action === 'edit' ? (int)$menu_data['category_id'] : 0);
                                        foreach ($categories as $cat): 
                                    ?>
                                        <option value="<?php echo $cat['id']; ?>" <?php echo $cat['id'] == $curr_cat ? 'selected' : ''; ?>>
                                            <?php echo escape($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                        
                        <!-- Price -->
                        <div class="col-md-6">
                            <label for="price" class="form-label">Price (INR) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" id="price" name="price" required placeholder="0.00" value="<?php echo isset($_POST['price']) ? escape($_POST['price']) : ($action === 'edit' ? escape($menu_data['price']) : ''); ?>">
                            </div>
                        </div>
                        
                        <!-- Image Upload -->
                        <div class="col-md-6">
                            <label for="menu_image" class="form-label">Item Image (JPG/PNG/WEBP, Max 2MB)</label>
                            <input type="file" class="form-control" id="menu_image" name="menu_image" accept="image/*">
                            <?php if ($action === 'edit' && !empty($menu_data['image'])): ?>
                                <small class="text-muted d-block mt-1">Current Image: <code><?php echo escape($menu_data['image']); ?></code></small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Describe taste profile, preparation details, milk choices, allergies..."><?php echo isset($_POST['description']) ? escape($_POST['description']) : ($action === 'edit' ? escape($menu_data['description']) : ''); ?></textarea>
                        </div>
                        
                        <!-- Badges/Flags (Inline toggles) -->
                        <div class="col-md-4 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_vegetarian" name="is_vegetarian" value="1" <?php echo ($action === 'add' || (isset($menu_data['is_vegetarian']) && (int)$menu_data['is_vegetarian'] === 1)) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-medium text-dark" for="is_vegetarian">Vegetarian recipe</label>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_available" name="is_available" value="1" <?php echo ($action === 'add' || (isset($menu_data['is_available']) && (int)$menu_data['is_available'] === 1)) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-medium text-dark" for="is_available">Available for ordering</label>
                            </div>
                        </div>
                        
                        <div class="col-md-4 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_featured" name="is_featured" value="1" <?php echo (isset($menu_data['is_featured']) && (int)$menu_data['is_featured'] === 1) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-medium text-dark" for="is_featured">Mark as Featured favourite</label>
                            </div>
                        </div>
                        
                        <!-- Buttons -->
                        <div class="col-12 mt-4 pt-2">
                            <button type="submit" class="btn btn-sage px-4"><?php echo ($action === 'add') ? 'Create Item' : 'Save Changes'; ?></button>
                            <a href="index.php" class="btn btn-light px-4 ms-2">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- LIST VIEW -->
    <div class="row">
        <div class="col-12 text-end mb-3">
            <a href="index.php?action=add" class="btn btn-sage"><i class="bi bi-plus-lg me-1"></i> Add Menu Item</a>
        </div>
        
        <div class="col-12">
            <div class="card admin-card p-4 shadow-sm bg-white">
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr class="text-muted small uppercase">
                                <th>Image</th>
                                <th>Item Name</th>
                                <th>Category</th>
                                <th>Price</th>
                                <th>Veg/Non-Veg</th>
                                <th>Ordering</th>
                                <th>Featured</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $db->query("SELECT m.*, c.name as category_name 
                                                    FROM menu_items m 
                                                    LEFT JOIN categories c ON m.category_id = c.id 
                                                    ORDER BY m.category_id ASC, m.id ASC");
                                $items = $stmt->fetchAll();
                            } catch (PDOException $e) {
                                error_log("Menu items query fail: " . $e->getMessage());
                                $items = [];
                            }
                            
                            if (!empty($items)):
                                foreach ($items as $item):
                            ?>
                                <tr>
                                    <td style="width: 70px;">
                                        <img src="<?php echo BASE_URL . '/' . (file_exists(__DIR__ . '/../../' . $item['image']) && !empty($item['image']) ? escape($item['image']) : 'assets/images/menu_placeholder.jpg'); ?>" alt="Item Thumbnail" class="img-fluid rounded" style="max-height: 40px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo escape($item['name']); ?></strong>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;"><?php echo escape($item['description']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border px-2 py-1"><?php echo escape($item['category_name']); ?></span>
                                    </td>
                                    <td class="text-dark fw-semibold">
                                        <?php echo format_price($item['price']); ?>
                                    </td>
                                    <td>
                                        <span class="veg-indicator <?php echo $item['is_vegetarian'] ? 'veg' : 'non-veg'; ?>">
                                            <i class="bi bi-circle-fill me-1"></i><?php echo $item['is_vegetarian'] ? 'Veg' : 'Non-Veg'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $item['is_available'] ? 'bg-success' : 'bg-secondary'; ?> px-2 py-1 rounded">
                                            <?php echo $item['is_available'] ? 'Available' : 'Unavailable'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $item['is_featured'] ? 'bg-info text-white' : 'bg-light text-muted'; ?> px-2 py-1 rounded">
                                            <?php echo $item['is_featured'] ? 'Yes' : 'No'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="index.php?action=toggle&id=<?php echo $item['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-secondary" title="Toggle Availability">
                                                <i class="bi bi-power"></i>
                                            </a>
                                            <a href="index.php?action=edit&id=<?php echo $item['id']; ?>" class="btn btn-sm btn-outline-sage" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="index.php?action=delete&id=<?php echo $item['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this menu item? If it was ordered previously, it will be marked unavailable instead.');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php 
                                endforeach;
                            else:
                            ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">No menu items created yet. Click "Add Menu Item" to get started.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
