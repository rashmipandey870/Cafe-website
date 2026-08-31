<?php
/**
 * admin/promotions/index.php
 * Administrative Promotion Manager - CRUD operations, scoping (products/categories), and Duplication
 */

$page_title = 'Manage Promotions';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();
$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$promo_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$duplicate_id = isset($_GET['duplicate_id']) ? (int)$_GET['duplicate_id'] : 0;

$errors = [];
$promo_data = null;
$selected_cats = [];
$selected_prods = [];

// Fetch master options for dropdown scopes
$categories = [];
$menu_items = [];
try {
    $categories = $db->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
    $menu_items = $db->query("SELECT id, name FROM menu_items ORDER BY name ASC")->fetchAll();
} catch (PDOException $e) {
    error_log("Settings options fetch fail: " . $e->getMessage());
}

// 1. Resolve duplication/edit data
$source_id = ($action === 'edit') ? $promo_id : (($action === 'add' && $duplicate_id > 0) ? $duplicate_id : 0);
if ($source_id > 0) {
    try {
        $stmt = $db->prepare("SELECT * FROM promotions WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $source_id]);
        $promo_data = $stmt->fetch();
        if ($promo_data) {
            // Get restricted lists
            $c_st = $db->prepare("SELECT category_id FROM promotion_categories WHERE promotion_id = :pid");
            $c_st->execute([':pid' => $source_id]);
            $selected_cats = $c_st->fetchAll(PDO::FETCH_COLUMN);
            
            $p_st = $db->prepare("SELECT menu_item_id FROM promotion_products WHERE promotion_id = :pid");
            $p_st->execute([':pid' => $source_id]);
            $selected_prods = $p_st->fetchAll(PDO::FETCH_COLUMN);
        }
    } catch (PDOException $e) {
        error_log("Fetch promo source error: " . $e->getMessage());
    }
}

// 2. Handle Form Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($action === 'add' || $action === 'edit')) {
    enforce_csrf();
    
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
    $description = trim(filter_input(INPUT_POST, 'description', FILTER_DEFAULT));
    $promotion_type = trim(filter_input(INPUT_POST, 'promotion_type', FILTER_DEFAULT));
    $discount_value = (float)filter_input(INPUT_POST, 'discount_value', FILTER_VALIDATE_FLOAT);
    $minimum_order_amount = (float)filter_input(INPUT_POST, 'minimum_order_amount', FILTER_VALIDATE_FLOAT);
    $maximum_discount_amount = (float)filter_input(INPUT_POST, 'maximum_discount_amount', FILTER_VALIDATE_FLOAT);
    $coupon_code = strtoupper(trim(filter_input(INPUT_POST, 'coupon_code', FILTER_DEFAULT)));
    $start_datetime = trim(filter_input(INPUT_POST, 'start_datetime', FILTER_DEFAULT));
    $end_datetime = trim(filter_input(INPUT_POST, 'end_datetime', FILTER_DEFAULT));
    $priority = (int)filter_input(INPUT_POST, 'priority', FILTER_VALIDATE_INT);
    $usage_limit = trim(filter_input(INPUT_POST, 'usage_limit', FILTER_DEFAULT));
    
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $allow_stacking = isset($_POST['allow_stacking']) ? 1 : 0;
    $apply_to = trim(filter_input(INPUT_POST, 'apply_to', FILTER_DEFAULT));
    
    $post_categories = isset($_POST['apply_categories']) ? $_POST['apply_categories'] : [];
    $post_products = isset($_POST['apply_products']) ? $_POST['apply_products'] : [];
    
    // Validations
    if (empty($name)) $errors[] = "Promotion name is required.";
    if ($promotion_type !== 'percentage' && $promotion_type !== 'fixed') $errors[] = "Select valid promotion type.";
    if ($discount_value <= 0) $errors[] = "Discount value must be a positive number.";
    if (empty($start_datetime) || empty($end_datetime)) $errors[] = "Validity timeline datetimes are required.";
    if ($start_datetime > $end_datetime) $errors[] = "Start time cannot exceed end time.";
    if ($priority <= 0) $priority = 1;
    
    $parsed_limit = ($usage_limit === '') ? null : (int)$usage_limit;
    
    // Check coupon uniqueness
    if (!empty($coupon_code)) {
        try {
            $chk_stmt = $db->prepare("SELECT id FROM promotions WHERE coupon_code = :code AND id != :id LIMIT 1");
            $chk_stmt->execute([':code' => $coupon_code, ':id' => $promo_id]);
            if ($chk_stmt->fetch()) {
                $errors[] = "Coupon code '{$coupon_code}' is already assigned to another promotion.";
            }
        } catch (PDOException $e) {
            error_log("Coupon uniqueness query fail: " . $e->getMessage());
        }
    }
    
    // 3. Save to database
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            if ($action === 'add') {
                $stmt = $db->prepare("INSERT INTO promotions 
                    (name, description, promotion_type, discount_value, minimum_order_amount, maximum_discount_amount, coupon_code, start_datetime, end_datetime, priority, is_active, allow_stacking, usage_limit) 
                    VALUES 
                    (:name, :description, :promotion_type, :discount_value, :minimum_order_amount, :maximum_discount_amount, :coupon_code, :start_datetime, :end_datetime, :priority, :is_active, :allow_stacking, :usage_limit)");
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':promotion_type' => $promotion_type,
                    ':discount_value' => $discount_value,
                    ':minimum_order_amount' => $minimum_order_amount,
                    ':maximum_discount_amount' => $maximum_discount_amount ?: 99999.00,
                    ':coupon_code' => $coupon_code ?: null,
                    ':start_datetime' => $start_datetime,
                    ':end_datetime' => $end_datetime,
                    ':priority' => $priority,
                    ':is_active' => $is_active,
                    ':allow_stacking' => $allow_stacking,
                    ':usage_limit' => $parsed_limit
                ]);
                $promo_id = (int)$db->lastInsertId();
                set_flash_message('success', 'Promotion campaign created successfully.');
            } else { // edit
                $stmt = $db->prepare("UPDATE promotions 
                    SET name = :name, description = :description, promotion_type = :promotion_type, discount_value = :discount_value, 
                        minimum_order_amount = :minimum_order_amount, maximum_discount_amount = :maximum_discount_amount, 
                        coupon_code = :coupon_code, start_datetime = :start_datetime, end_datetime = :end_datetime, 
                        priority = :priority, is_active = :is_active, allow_stacking = :allow_stacking, usage_limit = :usage_limit 
                    WHERE id = :id");
                $stmt->execute([
                    ':name' => $name,
                    ':description' => $description,
                    ':promotion_type' => $promotion_type,
                    ':discount_value' => $discount_value,
                    ':minimum_order_amount' => $minimum_order_amount,
                    ':maximum_discount_amount' => $maximum_discount_amount ?: 99999.00,
                    ':coupon_code' => $coupon_code ?: null,
                    ':start_datetime' => $start_datetime,
                    ':end_datetime' => $end_datetime,
                    ':priority' => $priority,
                    ':is_active' => $is_active,
                    ':allow_stacking' => $allow_stacking,
                    ':usage_limit' => $parsed_limit,
                    ':id' => $promo_id
                ]);
                
                // Clear old scope links
                $db->prepare("DELETE FROM promotion_categories WHERE promotion_id = :pid")->execute([':pid' => $promo_id]);
                $db->prepare("DELETE FROM promotion_products WHERE promotion_id = :pid")->execute([':pid' => $promo_id]);
                
                set_flash_message('success', 'Promotion updated successfully.');
            }
            
            // Rebuild Scope bridges
            if ($apply_to === 'categories') {
                $ins = $db->prepare("INSERT INTO promotion_categories (promotion_id, category_id) VALUES (:pid, :cid)");
                foreach ($post_categories as $cat_id) {
                    $ins->execute([':pid' => $promo_id, ':cid' => (int)$cat_id]);
                }
            } elseif ($apply_to === 'products') {
                $ins = $db->prepare("INSERT INTO promotion_products (promotion_id, menu_item_id) VALUES (:pid, :mid)");
                foreach ($post_products as $mid_id) {
                    $ins->execute([':pid' => $promo_id, ':mid' => (int)$mid_id]);
                }
            }
            
            $db->commit();
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Promo Save Error: " . $e->getMessage());
            $errors[] = "A system database error occurred.";
        }
    }
}

// 4. Toggle & Delete CSRF Verification
if (($action === 'toggle' || $action === 'delete') && $promo_id > 0) {
    $get_token = isset($_GET['token']) ? trim($_GET['token']) : '';
    if (!verify_csrf_get($get_token)) {
        set_flash_message('danger', 'Security token invalid or expired. Action blocked.');
        header("Location: index.php");
        exit;
    }
}

// 5. Toggle visibility status
if ($action === 'toggle' && $promo_id > 0) {
    try {
        $new_val = $promo_data['is_active'] ? 0 : 1;
        $db->prepare("UPDATE promotions SET is_active = :val WHERE id = :id")->execute([':val' => $new_val, ':id' => $promo_id]);
        set_flash_message('success', 'Promotion status toggled.');
    } catch (PDOException $e) {
        error_log("Promo toggle fail: " . $e->getMessage());
        set_flash_message('danger', 'Failed to toggle status.');
    }
    header("Location: index.php");
    exit;
}

// 5. Delete Campaign
if ($action === 'delete' && $promo_id > 0) {
    try {
        $db->prepare("DELETE FROM promotions WHERE id = :id")->execute([':id' => $promo_id]);
        set_flash_message('success', 'Promotion deleted successfully.');
    } catch (PDOException $e) {
        error_log("Promo delete fail: " . $e->getMessage());
        set_flash_message('danger', 'Failed to delete promotion (it may be referenced by historical orders).');
    }
    header("Location: index.php");
    exit;
}
?>

<?php if ($action === 'add' || $action === 'edit'): ?>
    <!-- FORM INTERFACE -->
    <div class="row">
        <div class="col-lg-9">
            <div class="card admin-card p-4 p-md-5 shadow-sm bg-white">
                <h3 class="display-font text-dark fs-4 mb-4 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">
                    <?php 
                        if ($duplicate_id > 0) {
                            echo "Duplicate Promotion: " . escape($promo_data['name']);
                        } else {
                            echo ($action === 'add') ? 'Create Promotion Offer' : 'Edit Promotion: ' . escape($promo_data['name']); 
                        }
                    ?>
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
                
                <form action="index.php?action=<?php echo $action; ?><?php echo ($action === 'edit') ? '&id=' . $promo_id : ''; ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    
                    <div class="row g-3">
                        <!-- Name -->
                        <div class="col-md-8">
                            <label for="name" class="form-label">Promotion Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required placeholder="e.g. Diwali Festivities 2026" value="<?php echo isset($_POST['name']) ? escape($_POST['name']) : ($promo_data ? escape($promo_data['name']) : ''); ?>">
                        </div>
                        
                        <!-- Priority (Lower value = Higher Priority) -->
                        <div class="col-md-4">
                            <label for="priority" class="form-label">Priority Order <span class="text-danger">*</span></label>
                            <select class="form-select" id="priority" name="priority" required>
                                <option value="1" <?php echo ($promo_data && (int)$promo_data['priority'] === 1) ? 'selected' : ''; ?>>1 - Highest Priority</option>
                                <option value="2" <?php echo ($promo_data && (int)$promo_data['priority'] === 2) ? 'selected' : ''; ?>>2 - Medium Priority</option>
                                <option value="3" <?php echo ($promo_data && (int)$promo_data['priority'] === 3) ? 'selected' : ''; ?>>3 - Low Priority</option>
                            </select>
                        </div>
                        
                        <!-- Discount Type -->
                        <div class="col-md-4">
                            <label for="promotion_type" class="form-label">Discount Type <span class="text-danger">*</span></label>
                            <select class="form-select" id="promotion_type" name="promotion_type" required>
                                <option value="percentage" <?php echo ($promo_data && $promo_data['promotion_type'] === 'percentage') ? 'selected' : ''; ?>>Percentage Discount (%)</option>
                                <option value="fixed" <?php echo ($promo_data && $promo_data['promotion_type'] === 'fixed') ? 'selected' : ''; ?>>Fixed Amount Deduction (₹)</option>
                            </select>
                        </div>
                        
                        <!-- Discount value -->
                        <div class="col-md-4">
                            <label for="discount_value" class="form-label">Discount Value <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="discount_value" name="discount_value" required placeholder="e.g. 20 (for 20%)" value="<?php echo isset($_POST['discount_value']) ? escape($_POST['discount_value']) : ($promo_data ? escape($promo_data['discount_value']) : ''); ?>">
                        </div>
                        
                        <!-- Coupon Code -->
                        <div class="col-md-4">
                            <label for="coupon_code" class="form-label">Coupon Code (Leave empty for auto-apply)</label>
                            <input type="text" class="form-control" id="coupon_code" name="coupon_code" placeholder="e.g. DIWALI20" value="<?php echo isset($_POST['coupon_code']) ? escape($_POST['coupon_code']) : ($promo_data && $duplicate_id === 0 ? escape($promo_data['coupon_code']) : ''); ?>">
                        </div>
                        
                        <!-- Constraints -->
                        <div class="col-md-4">
                            <label for="minimum_order_amount" class="form-label">Minimum Order Requirement</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" id="minimum_order_amount" name="minimum_order_amount" value="<?php echo isset($_POST['minimum_order_amount']) ? escape($_POST['minimum_order_amount']) : ($promo_data ? escape($promo_data['minimum_order_amount']) : '0.00'); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="maximum_discount_amount" class="form-label">Maximum Discount Cap</label>
                            <div class="input-group">
                                <span class="input-group-text">₹</span>
                                <input type="number" step="0.01" class="form-control" id="maximum_discount_amount" name="maximum_discount_amount" value="<?php echo isset($_POST['maximum_discount_amount']) ? escape($_POST['maximum_discount_amount']) : ($promo_data ? escape($promo_data['maximum_discount_amount']) : '99999.00'); ?>">
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="usage_limit" class="form-label">Total Usage Limit</label>
                            <input type="number" class="form-control" id="usage_limit" name="usage_limit" placeholder="Unlimited" value="<?php echo isset($_POST['usage_limit']) ? escape($_POST['usage_limit']) : ($promo_data ? escape($promo_data['usage_limit']) : ''); ?>">
                        </div>
                        
                        <!-- Dates -->
                        <div class="col-md-6">
                            <label for="start_datetime" class="form-label">Start Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="start_datetime" name="start_datetime" required value="<?php echo isset($_POST['start_datetime']) ? escape($_POST['start_datetime']) : ($promo_data ? date('Y-m-d\TH:i', strtotime($promo_data['start_datetime'])) : date('Y-m-d\T00:00')); ?>">
                        </div>
                        
                        <div class="col-md-6">
                            <label for="end_datetime" class="form-label">End Time <span class="text-danger">*</span></label>
                            <input type="datetime-local" class="form-control" id="end_datetime" name="end_datetime" required value="<?php echo isset($_POST['end_datetime']) ? escape($_POST['end_datetime']) : ($promo_data ? date('Y-m-d\TH:i', strtotime($promo_data['end_datetime'])) : date('Y-m-d\T23:59', strtotime('+30 days'))); ?>">
                        </div>
                        
                        <!-- Description -->
                        <div class="col-12">
                            <label for="description" class="form-label">Public Campaign Description / Guidelines</label>
                            <textarea class="form-control" id="description" name="description" rows="3" placeholder="Explain the terms in plain text for customers..."><?php echo isset($_POST['description']) ? escape($_POST['description']) : ($promo_data ? escape($promo_data['description']) : ''); ?></textarea>
                        </div>
                        
                        <!-- Target Scoping selection -->
                        <div class="col-12 mt-4 bg-light p-4 rounded border">
                            <label class="form-label d-block text-dark fw-bold mb-3">Which items does this promotion apply to?</label>
                            
                            <div class="form-check form-check-inline mb-3">
                                <input class="form-check-input" type="radio" name="apply_to" id="scope_all" value="all" <?php echo (empty($selected_cats) && empty($selected_prods)) ? 'checked' : ''; ?> onclick="toggleScopeSections()">
                                <label class="form-check-label text-dark" for="scope_all">Entire Menu</label>
                            </div>
                            <div class="form-check form-check-inline mb-3">
                                <input class="form-check-input" type="radio" name="apply_to" id="scope_cats" value="categories" <?php echo !empty($selected_cats) ? 'checked' : ''; ?> onclick="toggleScopeSections()">
                                <label class="form-check-label text-dark" for="scope_cats">Selected Categories</label>
                            </div>
                            <div class="form-check form-check-inline mb-3">
                                <input class="form-check-input" type="radio" name="apply_to" id="scope_prods" value="products" <?php echo !empty($selected_prods) ? 'checked' : ''; ?> onclick="toggleScopeSections()">
                                <label class="form-check-label text-dark" for="scope_prods">Selected Products</label>
                            </div>
                            
                            <!-- Categories selection sub-section -->
                            <div id="category_checkboxes" class="mt-3 d-none">
                                <label class="form-label small fw-bold text-muted">Select Eligible Categories:</label>
                                <div class="row">
                                    <?php foreach ($categories as $cat): ?>
                                        <div class="col-md-4">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="apply_categories[]" id="cat_<?php echo $cat['id']; ?>" value="<?php echo $cat['id']; ?>" <?php echo in_array($cat['id'], $selected_cats) ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-muted small" for="cat_<?php echo $cat['id']; ?>"><?php echo escape($cat['name']); ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Products selection sub-section -->
                            <div id="product_checkboxes" class="mt-3 d-none">
                                <label class="form-label small fw-bold text-muted">Select Eligible Menu Items:</label>
                                <div class="row" style="max-height: 200px; overflow-y: auto;">
                                    <?php foreach ($menu_items as $item): ?>
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="apply_products[]" id="item_<?php echo $item['id']; ?>" value="<?php echo $item['id']; ?>" <?php echo in_array($item['id'], $selected_prods) ? 'checked' : ''; ?>>
                                                <label class="form-check-label text-muted small" for="item_<?php echo $item['id']; ?>"><?php echo escape($item['name']); ?></label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Toggle options (stacking and status) -->
                        <div class="col-md-6 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="allow_stacking" name="allow_stacking" value="1" <?php echo ($promo_data && $promo_data['allow_stacking']) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-dark fw-medium" for="allow_stacking">Allow Stacking with other promotions</label>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" <?php echo ($action === 'add' || ($promo_data && $promo_data['is_active'])) ? 'checked' : ''; ?>>
                                <label class="form-check-label text-dark fw-medium" for="is_active">Make campaign active</label>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="col-12 mt-4 pt-2">
                            <button type="submit" class="btn btn-sage px-4"><?php echo ($action === 'add') ? 'Create Promotion' : 'Save Changes'; ?></button>
                            <a href="index.php" class="btn btn-light px-4 ms-2">Cancel</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        function toggleScopeSections() {
            const scopeAll = document.getElementById('scope_all').checked;
            const scopeCats = document.getElementById('scope_cats').checked;
            const scopeProds = document.getElementById('scope_prods').checked;
            
            const catBox = document.getElementById('category_checkboxes');
            const prodBox = document.getElementById('product_checkboxes');
            
            if (scopeCats) {
                catBox.classList.remove('d-none');
                prodBox.classList.add('d-none');
            } else if (scopeProds) {
                prodBox.classList.remove('d-none');
                catBox.classList.add('d-none');
            } else {
                catBox.classList.add('d-none');
                prodBox.classList.add('d-none');
            }
        }
        document.addEventListener('DOMContentLoaded', () => {
            toggleScopeSections();
        });
    </script>

<?php else: ?>
    <!-- LIST TABLE -->
    <div class="row">
        <div class="col-12 text-end mb-3">
            <a href="index.php?action=add" class="btn btn-sage"><i class="bi bi-plus-lg me-1"></i> Create Promotion</a>
        </div>
        
        <div class="col-12">
            <div class="card admin-card p-4 shadow-sm bg-white">
                <div class="table-responsive">
                    <table class="table table-custom align-middle">
                        <thead>
                            <tr class="text-muted small uppercase">
                                <th>Offer Name</th>
                                <th>Discount Details</th>
                                <th>Coupon Key</th>
                                <th>Priority</th>
                                <th>Timeline</th>
                                <th>Stacking</th>
                                <th>Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            try {
                                $stmt = $db->query("SELECT * FROM promotions ORDER BY priority ASC, id DESC");
                                $list = $stmt->fetchAll();
                            } catch (PDOException $e) {
                                error_log("Promo listing error: " . $e->getMessage());
                                $list = [];
                            }
                            
                            if (!empty($list)):
                                foreach ($list as $promo):
                                    $today = date('Y-m-d H:i:s');
                                    
                                    // Determine campaign lifecycle statuses (Scheduled, Active, Expired)
                                    $lifecycle = 'disabled';
                                    if ((int)$promo['is_active'] === 0) {
                                        $lifecycle = 'disabled';
                                    } elseif ($today < $promo['start_datetime']) {
                                        $lifecycle = 'scheduled';
                                    } elseif ($today > $promo['end_datetime']) {
                                        $lifecycle = 'expired';
                                    } else {
                                        $lifecycle = 'active';
                                    }
                            ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo escape($promo['name']); ?></strong>
                                        <small class="text-muted text-truncate d-inline-block" style="max-width: 250px;"><?php echo escape($promo['description']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-warning text-dark px-2 py-1">
                                            <?php echo ($promo['promotion_type'] === 'percentage') ? escape((float)$promo['discount_value']) . '%' : '₹' . escape($promo['discount_value']); ?> OFF
                                        </span>
                                        <small class="d-block text-muted mt-1" style="font-size: 0.75rem;">Min order: ₹<?php echo (float)$promo['minimum_order_amount']; ?></small>
                                    </td>
                                    <td>
                                        <?php if (!empty($promo['coupon_code'])): ?>
                                            <code class="text-dark bg-light px-2 py-1 rounded border font-monospace"><?php echo escape($promo['coupon_code']); ?></code>
                                        <?php else: ?>
                                            <span class="text-muted small">Auto-Apply</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center font-monospace fw-bold text-dark">
                                        <?php echo $promo['priority']; ?>
                                    </td>
                                    <td class="small text-muted" style="font-size: 0.8rem;">
                                        <?php echo date('M d, h:i A', strtotime($promo['start_datetime'])); ?><br>
                                        <span class="text-muted">to</span><br>
                                        <?php echo date('M d, h:i A', strtotime($promo['end_datetime'])); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $promo['allow_stacking'] ? 'bg-info' : 'bg-light text-muted border'; ?> px-2 py-1">
                                            <?php echo $promo['allow_stacking'] ? 'Allowed' : 'Disabled'; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($lifecycle === 'active'): ?>
                                            <span class="badge bg-success px-3 py-2 rounded-pill">Active</span>
                                        <?php elseif ($lifecycle === 'scheduled'): ?>
                                            <span class="badge bg-info text-white px-3 py-2 rounded-pill">Scheduled</span>
                                        <?php elseif ($lifecycle === 'expired'): ?>
                                            <span class="badge bg-secondary px-3 py-2 rounded-pill">Expired</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger px-3 py-2 rounded-pill">Disabled</span>
                                        <?php endif; ?>
                                        <small class="d-block text-muted text-center mt-1" style="font-size: 0.75rem;">Used: <?php echo $promo['usage_count']; ?> times</small>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="index.php?action=toggle&id=<?php echo $promo['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-secondary" title="Toggle visibility status">
                                                <i class="bi bi-power"></i>
                                            </a>
                                            <!-- Duplicate Link -->
                                            <a href="index.php?action=add&duplicate_id=<?php echo $promo['id']; ?>" class="btn btn-sm btn-outline-info" title="Duplicate/Copy">
                                                <i class="bi bi-copy"></i>
                                            </a>
                                            <a href="index.php?action=edit&id=<?php echo $promo['id']; ?>" class="btn btn-sm btn-outline-sage" title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="index.php?action=delete&id=<?php echo $promo['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this promotion? Usage logs will remain intact.');">
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
                                    <td colspan="8" class="text-center py-4 text-muted">No promotions created yet. Click "Create Promotion" to configure campaign coupons.</td>
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
