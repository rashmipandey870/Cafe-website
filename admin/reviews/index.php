<?php
/**
 * admin/reviews/index.php
 * Administrative Review Management - Approve, Hide, and Delete customer testimonials
 */

$page_title = 'Manage Customer Reviews';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();

$action = isset($_GET['action']) ? trim($_GET['action']) : 'list';
$review_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 1. Process Actions (GET-based for simplicity with safe confirmation triggers)
if (!empty($action) && $review_id > 0) {
    // Validate GET CSRF token
    $get_token = isset($_GET['token']) ? trim($_GET['token']) : '';
    if (!verify_csrf_get($get_token)) {
        set_flash_message('danger', 'Security token invalid or expired. Action blocked.');
        header("Location: index.php");
        exit;
    }
    
    try {
        // Retrieve review first to check validity
        $chk = $db->prepare("SELECT id FROM reviews WHERE id = :id LIMIT 1");
        $chk->execute([':id' => $review_id]);
        if ($chk->fetch()) {
            if ($action === 'approve') {
                $stmt = $db->prepare("UPDATE reviews SET is_approved = 1 WHERE id = :id");
                $stmt->execute([':id' => $review_id]);
                set_flash_message('success', 'Review approved and published to the website.');
            } elseif ($action === 'hide') {
                $stmt = $db->prepare("UPDATE reviews SET is_approved = 0 WHERE id = :id");
                $stmt->execute([':id' => $review_id]);
                set_flash_message('success', 'Review unapproved and hidden from public views.');
            } elseif ($action === 'delete') {
                $stmt = $db->prepare("DELETE FROM reviews WHERE id = :id");
                $stmt->execute([':id' => $review_id]);
                set_flash_message('success', 'Review deleted successfully.');
            }
        } else {
            set_flash_message('danger', 'Review entry not found.');
        }
    } catch (PDOException $e) {
        error_log("Reviews action error: " . $e->getMessage());
        set_flash_message('danger', 'Failed to perform requested action on reviews database.');
    }
    
    header("Location: index.php");
    exit;
}

// 2. Fetch all reviews
$reviews_list = [];
try {
    $reviews_list = $db->query("SELECT * FROM reviews ORDER BY id DESC LIMIT 100")->fetchAll();
} catch (PDOException $e) {
    error_log("Reviews list fetch error: " . $e->getMessage());
}
?>

<div class="row">
    <div class="col-12">
        <div class="card admin-card p-4 shadow-sm bg-white">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr class="text-muted small uppercase">
                            <th>Customer Name</th>
                            <th>Rating</th>
                            <th>Comment</th>
                            <th>Submitted At</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reviews_list)): ?>
                            <?php foreach ($reviews_list as $rev): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark"><?php echo escape($rev['customer_name']); ?></strong>
                                    </td>
                                    <td>
                                        <div class="text-warning">
                                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                                <i class="bi <?php echo $i <= $rev['rating'] ? 'bi-star-fill' : 'bi-star'; ?>"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </td>
                                    <td class="text-muted small" style="max-width: 400px; white-space: normal;">
                                        "<?php echo escape($rev['comment']); ?>"
                                    </td>
                                    <td class="text-muted small">
                                        <?php echo date('M d, Y', strtotime($rev['created_at'])); ?>
                                    </td>
                                    <td>
                                        <?php if ($rev['is_approved']): ?>
                                            <span class="badge bg-success px-3 py-2 rounded-pill">Approved (Public)</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark px-3 py-2 rounded-pill">Pending Approval</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <?php if ($rev['is_approved']): ?>
                                                <a href="index.php?action=hide&id=<?php echo $rev['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-secondary" title="Hide/Unapprove">
                                                    <i class="bi bi-eye-slash-fill"></i> Unapprove
                                                </a>
                                            <?php else: ?>
                                                <a href="index.php?action=approve&id=<?php echo $rev['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-success" title="Approve">
                                                    <i class="bi bi-eye-fill"></i> Approve
                                                </a>
                                            <?php endif; ?>
                                            <a href="index.php?action=delete&id=<?php echo $rev['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this review?');">
                                                <i class="bi bi-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-4 text-muted">No reviews submitted by customers yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
