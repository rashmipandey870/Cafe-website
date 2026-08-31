<?php
/**
 * admin/categories/index.php
 * Administrative Category Management (CRUD operations)
 */

$page_title = 'Manage Categories';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();
$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$errors = [];
$category_data = null;

// Retrieve existing category if edit action requested
if ($category_id > 0 && ($action === 'edit' || $action === 'toggle' || $action === 'delete')) {
    try {
        $stmt = $db->prepare("SELECT * FROM categories WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $category_id]);
        $category_data = $stmt->fetch();
        if (!$category_data) {
            set_flash_message('danger', 'Category not found.');
            header("Location: index.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Category Fetch Error: " . $e->getMessage());
    }
}

// Handle Form Submissions (Add / Edit / Toggle)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF
    enforce_csrf();
    
    // 2. Add or Edit Processing
    if ($action === 'add' || $action === 'edit') {
        $name = trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
        $description = trim(filter_input(INPUT_POST, 'description', FILTER_DEFAULT));
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        
        if (empty($name)) {
            $errors[] = "Category name is required.";
        }
        
        // 3. Image Upload Processing
        $image_path = '';
        if (isset($_FILES['category_image']) && $_FILES['category_image']['error'] === UPLOAD_ERR_OK) {
            $uploaded = upload_image($_FILES['category_image'], 'uploads/menu/');
            if ($uploaded) {
                $image_path = $uploaded;
            } else {
                $errors[] = "Failed to upload image. Must be a valid JPG/PNG/WEBP under 2MB.";
            }
        }
        
        // 4. Database Insert / Update
        if (empty($errors)) {
            try {
                if ($action === 'add') {
                    $stmt = $db->prepare("INSERT INTO categories (name, description, image, is_active) 
                                          VALUES (:name, :description, :image, :is_active)");
                    $stmt->execute([
                        ':name' => $name,
                        ':description' => $description,
                        ':image' => $image_path ? $image_path : 'assets/images/cat_placeholder.jpg',
                        ':is_active' => $is_active
                    ]);
                    set_flash_message('success', 'Category added successfully!');
                } else { // edit
                    // If no new image was uploaded, retain old image
                    if (empty($image_path)) {
                        $image_path = $category_data['image'];
                    }
                    
                    $stmt = $db->prepare("UPDATE categories 
                                          SET name = :name, description = :description, image = :image, is_active = :is_active 
                                          WHERE id = :id");
                    $stmt->execute([
                        ':name' => $name,
                        ':description' => $description,
                        ':image' => $image_path,
                        ':is_active' => $is_active,
                        ':id' => $category_id
                    ]);
                    set_flash_message('success', 'Category updated successfully!');
                }
                
                header("Location: index.php");
                exit;
            } catch (PDOException $e) {
                error_log("Category CRUD Write Error: " . $e->getMessage());
                $errors[] = "A system error occurred. Please try again.";
            }
        }
    }
}

// 5. Toggle & Delete CSRF Check
if (($action === 'toggle' || $action === 'delete') && $category_id > 0) {
    $get_token = isset($_GET['token']) ? trim($_GET['token']) : '';
    if (!verify_csrf_get($get_token)) {
        set_flash_message('danger', 'Security token invalid or expired. Action blocked.');
        header("Location: index.php");
        exit;
    }
}

// 6. Handle Toggle Status via query param (Non-destructive toggle)
if ($action === 'toggle' && $category_id > 0) {
    try {
        $new_status = $category_data['is_active'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE categories SET is_active = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $category_id]);
        
        set_flash_message('success', 'Category visibility toggled successfully.');
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        error_log("Category Toggle Error: " . $e->getMessage());
        set_flash_message('danger', 'Failed to toggle category status.');
    }
}

// 6. Handle Delete
if ($action === 'delete' && $category_id > 0) {
    try {
        $stmt = $db->prepare("DELETE FROM categories WHERE id = :id");
        $stmt->execute([':id' => $category_id]);
        
        set_flash_message('success', 'Category deleted successfully.');
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        error_log("Category Delete Error: " . $e->getMessage());
        set_flash_message('danger', 'Failed to delete category (it may be linked to menu items).');
        header("Location: index.php");
        exit;
    }
}
?>

<!-- Action Toggles -->
<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- CREATE OR EDIT FORM VIEW -->
    <div class="row">
        <div class="col-lg-8">
            <div class="card admin-card p-4 shadow-sm bg-white">
                <h3 class="display-font text-dark fs-4 mb-4 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">
                    <?php echo ($action === 'add') ? 'Create New Category' : 'Edit Category: ' . escape($category_data['name']); ?>
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
                
                <form action="index.php?action=<?php echo $action; ?><?php echo ($action === 'edit') ? '&id=' . $category_id : ''; ?>" method="POST" enctype="multipart/form-data">
                    <!-- CSRF Protection Field -->
                    <?php echo csrf_field(); ?>
                    
                    <div class="row g-3">
                        <!-- Category Name -->
                        <div class="col-12">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. Cold Brews, Artisanal Bakes" value="<?php echo isset($_POST['name']) ? escape($_POST['name']) : ($action === 'edit' ? escape($category_data['name']) : ''); ?>">
                        </div>
                        
                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief summary of category items..."><?php echo isset($_POST['description']) ? escape($_POST['description']) : ($action === 'edit' ? escape($category_data['description']) : ''); ?></textarea>
                        </div>
                        
                        <!-- Image Upload -->
                        <div class="col-12">
                            <label for="category_image" class="form-label">Category Image (JPG/PNG/WEBP, Max 2MB)</label>
                            <input type="file" class="form-control" id="category_image" name="category_image" accept="image/*">
                            <?php if ($action === 'edit' && !empty($category_data['image'])): ?>
                                <small class="text-muted d-block mt-1">Current Image: <code><?php echo escape($category_data['image']); ?></code></small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Active / Visibility Toggle -->
                        <div class="col-12">
                            <div class="form-check form-switch mt-2">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?php echo ($action === 'add' || (isset($category_data['is_active']) && (int)$category_data['is_active'] === 1)) ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-medium text-dark" for="is_active">Make category active and visible publicly</label>
                            </div>
                        </div>
                        
                        <!-- Action buttons -->
                        <div class="col-12 mt-4 pt-2">
                            <button type="submit" class="btn btn-sage px-4"><?php echo ($action === 'add') ? 'Add Category' : 'Save Changes'; ?></button>
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
            <a href="index.php?action=add" class="btn btn-sage"><i class="bi bi-plus-lg me-1"></i> Add New Category</a>
        </div>
        
        <div class="col-12">
            <div class="card admin-card p-4 shadow-sm bg-white">
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr class="text-muted small uppercase">
                                <th>Image</th>
                                <th>Category Name</th>
                                <th>Description</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $stmt = $db->query("SELECT * FROM categories ORDER BY id ASC");
                            $cats = $stmt->fetchAll();
                            
                            if (!empty($cats)):
                                foreach ($cats as $cat):
                            ?>
                                <tr>
                                    <td style="width: 80px;">
                                        <img src="<?php echo BASE_URL . '/' . (file_exists(__DIR__ . '/../../' . $cat['image']) && !empty($cat['image']) ? escape($cat['image']) : 'assets/images/cat_placeholder.jpg'); ?>" alt="Category Cover" class="img-fluid rounded" style="max-height: 50px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?php echo escape($cat['name']); ?></strong>
                                    </td>
                                    <td class="text-muted small" style="max-width: 300px;">
                                        <?php echo escape($cat['description']); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $cat['is_active'] ? 'bg-success' : 'bg-secondary'; ?> px-3 py-2 rounded-pill">
                                            <?php echo $cat['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="index.php?action=toggle&id=<?php echo $cat['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-secondary" title="Toggle Status">
                                                <i class="bi bi-power"></i>
                                            </a>
                                            <a href="index.php?action=edit&id=<?php echo $cat['id']; ?>" class="btn btn-sm btn-outline-sage" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="index.php?action=delete&id=<?php echo $cat['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this category? All items linked to it will also be affected.');">
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
                                    <td colspan="5" class="text-center py-4 text-muted">No categories created yet. Click "Add New Category" to create one.</td>
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
