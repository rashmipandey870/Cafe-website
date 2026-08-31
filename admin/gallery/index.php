<?php
/**
 * admin/gallery/index.php
 * Administrative Gallery Management - Image upload validation, toggling, and physical file unlinking
 */

$page_title = 'Manage Gallery';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();

$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$gallery_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$errors = [];
$gallery_item = null;

// Fetch item if action requested
if ($gallery_id > 0 && ($action === 'toggle' || $action === 'delete')) {
    try {
        $stmt = $db->prepare("SELECT * FROM gallery WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $gallery_id]);
        $gallery_item = $stmt->fetch();
        if (!$gallery_item) {
            set_flash_message('danger', 'Photo not found.');
            header("Location: index.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Gallery Fetch Error: " . $e->getMessage());
    }
}

// Process Upload submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'upload') {
    enforce_csrf();
    
    $title = trim(filter_input(INPUT_POST, 'title', FILTER_DEFAULT));
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // File validation
    if (!isset($_FILES['gallery_image']) || $_FILES['gallery_image']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = "Please select a valid image file to upload.";
    } else {
        $uploaded = upload_image($_FILES['gallery_image'], 'uploads/gallery/');
        if ($uploaded) {
            $image_path = $uploaded;
        } else {
            $errors[] = "Failed to upload photo. Must be a valid JPG/PNG/WEBP under 2MB.";
        }
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO gallery (title, image, is_active) VALUES (:title, :image, :is_active)");
            $stmt->execute([
                ':title' => $title ?: 'Mellow & Meadow Moments',
                ':image' => $image_path,
                ':is_active' => $is_active
            ]);
            set_flash_message('success', 'Photo added to gallery successfully.');
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            error_log("Gallery DB Insert Fail: " . $e->getMessage());
            $errors[] = "A system database error occurred.";
        }
    }
}

// Handle Status Toggle & Delete CSRF Check
if (($action === 'toggle' || $action === 'delete') && $gallery_id > 0) {
    $get_token = isset($_GET['token']) ? trim($_GET['token']) : '';
    if (!verify_csrf_get($get_token)) {
        set_flash_message('danger', 'Security token invalid or expired. Action blocked.');
        header("Location: index.php");
        exit;
    }
}

// Handle Status Toggle
if ($action === 'toggle' && $gallery_id > 0) {
    try {
        $new_status = $gallery_item['is_active'] ? 0 : 1;
        $stmt = $db->prepare("UPDATE gallery SET is_active = :status WHERE id = :id");
        $stmt->execute([':status' => $new_status, ':id' => $gallery_id]);
        
        set_flash_message('success', 'Photo visibility toggled.');
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        error_log("Gallery Toggle Error: " . $e->getMessage());
        set_flash_message('danger', 'Failed to toggle visibility status.');
    }
}

// Handle Safe Deletions (Database removal + physical unlink)
if ($action === 'delete' && $gallery_id > 0) {
    try {
        // Step 1: Remove DB record
        $stmt = $db->prepare("DELETE FROM gallery WHERE id = :id");
        $stmt->execute([':id' => $gallery_id]);
        
        // Step 2: Unlink physical file from disk to save shared hosting storage space
        $physical_file = __DIR__ . '/../../' . $gallery_item['image'];
        if (!empty($gallery_item['image']) && file_exists($physical_file)) {
            @unlink($physical_file);
        }
        
        set_flash_message('success', 'Photo removed from gallery and physical file deleted.');
        header("Location: index.php");
        exit;
    } catch (PDOException $e) {
        error_log("Gallery Delete Exception: " . $e->getMessage());
        set_flash_message('danger', 'Failed to delete photo from database.');
        header("Location: index.php");
        exit;
    }
}
?>

<div class="row g-4">
    <!-- Left Section: Upload Form Card -->
    <div class="col-lg-4">
        <div class="card admin-card p-4 shadow-sm bg-white sticky-lg-top" style="top: 100px;">
            <h3 class="display-font text-dark fs-4 mb-4 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">Upload Photo</h3>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger" style="font-size: 0.9rem;">
                    <ul class="mb-0">
                        <?php foreach ($errors as $err): ?>
                            <li><?php echo escape($err); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <form action="index.php?action=upload" method="POST" enctype="multipart/form-data">
                <!-- CSRF Field -->
                <?php echo csrf_field(); ?>
                
                <!-- Caption Title -->
                <div class="mb-3">
                    <label for="title" class="form-label">Photo Caption / Title</label>
                    <input type="text" class="form-control" id="title" name="title" placeholder="e.g. Lavender Cold Brew Prep" value="<?php echo isset($_POST['title']) ? escape($_POST['title']) : ''; ?>">
                </div>
                
                <!-- Image file upload -->
                <div class="mb-3">
                    <label for="gallery_image" class="form-label">Select Image <span class="text-danger">*</span></label>
                    <input type="file" class="form-control" id="gallery_image" name="gallery_image" accept="image/*" required>
                    <small class="text-muted d-block mt-1">Allowed formats: JPG, PNG, WEBP. Max size: 2MB.</small>
                </div>
                
                <!-- Active Toggle Switch -->
                <div class="mb-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" value="1" checked>
                        <label class="form-check-label text-dark fw-medium" for="is_active">Make visible in public gallery</label>
                    </div>
                </div>
                
                <div class="d-grid">
                    <button type="submit" class="btn btn-sage py-3 fw-bold">Upload to Gallery</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Right Section: Table list of current photos -->
    <div class="col-lg-8">
        <div class="card admin-card p-4 shadow-sm bg-white">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr class="text-muted small uppercase">
                            <th>Preview</th>
                            <th>Caption</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        try {
                            $stmt = $db->query("SELECT * FROM gallery ORDER BY id DESC");
                            $gallery_list = $stmt->fetchAll();
                        } catch (PDOException $e) {
                            error_log("Gallery Fetch Error: " . $e->getMessage());
                            $gallery_list = [];
                        }
                        
                        if (!empty($gallery_list)):
                            foreach ($gallery_list as $img):
                        ?>
                                <tr>
                                    <td style="width: 100px;">
                                        <img src="<?php echo BASE_URL . '/' . (file_exists(__DIR__ . '/../../' . $img['image']) && !empty($img['image']) ? escape($img['image']) : 'assets/images/gal_placeholder.jpg'); ?>" alt="Preview" class="img-fluid rounded" style="max-height: 60px; object-fit: cover;">
                                    </td>
                                    <td>
                                        <strong class="text-dark"><?php echo escape($img['title']); ?></strong>
                                        <small class="text-muted d-block font-monospace" style="font-size: 0.75rem;"><?php echo escape($img['image']); ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $img['is_active'] ? 'bg-success' : 'bg-secondary'; ?> px-2 py-1 rounded">
                                            <?php echo $img['is_active'] ? 'Public' : 'Hidden'; ?>
                                        </span>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <a href="index.php?action=toggle&id=<?php echo $img['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-secondary" title="Toggle Visibility">
                                                <i class="bi bi-power"></i>
                                            </a>
                                            <a href="index.php?action=delete&id=<?php echo $img['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to permanently delete this photo? The image file will also be deleted from server.');">
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
                                <td colspan="4" class="text-center py-4 text-muted">No images uploaded to the gallery yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
