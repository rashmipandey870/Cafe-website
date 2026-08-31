<?php
/**
 * admin/messages/index.php
 * Administrative Inbox - View customer enquiries, toggle read indicators, and manage deletions
 */

$page_title = 'Contact Inquiries';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();

$selected_msg_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$action = isset($_GET['action']) ? trim($_GET['action']) : '';

$selected_msg = null;

// 1. Process Actions (GET actions for simple dashboard triggers)
if (!empty($action) && $selected_msg_id > 0) {
    // Validate GET CSRF token
    $get_token = isset($_GET['token']) ? trim($_GET['token']) : '';
    if (!verify_csrf_get($get_token)) {
        set_flash_message('danger', 'Security token invalid or expired. Action blocked.');
        header("Location: index.php");
        exit;
    }
    
    if ($action === 'delete') {
        try {
            $stmt = $db->prepare("DELETE FROM messages WHERE id = :id");
            $stmt->execute([':id' => $selected_msg_id]);
            set_flash_message('success', 'Message deleted successfully.');
        } catch (PDOException $e) {
            error_log("Message Delete Error: " . $e->getMessage());
            set_flash_message('danger', 'Failed to delete message.');
        }
        header("Location: index.php");
        exit;
    }
}

// 2. Mark Selected Message as Read & Fetch details
if ($selected_msg_id > 0) {
    try {
        // Mark as read automatically when opened
        $stmt = $db->prepare("UPDATE messages SET is_read = 1 WHERE id = :id");
        $stmt->execute([':id' => $selected_msg_id]);
        
        // Fetch message details
        $stmt = $db->prepare("SELECT * FROM messages WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $selected_msg_id]);
        $selected_msg = $stmt->fetch();
    } catch (PDOException $e) {
        error_log("Message Details Exception: " . $e->getMessage());
    }
}

// 3. Fetch all messages in inbox
$messages_list = [];
try {
    $messages_list = $db->query("SELECT id, name, subject, is_read, created_at FROM messages ORDER BY id DESC LIMIT 100")->fetchAll();
} catch (PDOException $e) {
    error_log("Messages Fetch Exception: " . $e->getMessage());
}
?>

<div class="row g-4">
    <!-- Left Section: Message Inbox List -->
    <div class="col-lg-6 col-xl-7">
        <div class="card admin-card p-4 shadow-sm bg-white">
            <h3 class="display-font text-dark fs-4 mb-3">Inquiries Inbox</h3>
            
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr class="text-muted small uppercase">
                            <th>Sender</th>
                            <th>Subject</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($messages_list)): ?>
                            <?php foreach ($messages_list as $msg): ?>
                                <tr class="<?php echo $msg['id'] === $selected_msg_id ? 'table-active' : ''; ?>">
                                    <td>
                                        <a href="index.php?id=<?php echo $msg['id']; ?>" class="fw-bold block">
                                            <?php echo escape($msg['name']); ?>
                                        </a>
                                        <small class="text-muted d-block" style="font-size: 0.75rem;"><?php echo date('M d, h:i A', strtotime($msg['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <span class="text-dark fw-medium"><?php echo escape($msg['subject']); ?></span>
                                    </td>
                                    <td>
                                        <?php if ($msg['is_read']): ?>
                                            <span class="badge bg-light text-muted px-2 py-1 border">Read</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning text-dark px-2 py-1 fw-bold">New</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-5 text-muted">Your inbox is currently empty. No messages received.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Right Section: Full message reader -->
    <div class="col-lg-6 col-xl-5">
        <?php if ($selected_msg): ?>
            <div class="card admin-card p-4 shadow-sm bg-white sticky-lg-top" style="top: 100px;">
                <div class="d-flex justify-content-between align-items-start mb-3 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">
                    <div>
                        <h4 class="display-font text-dark fs-4 mb-1"><?php echo escape($selected_msg['subject']); ?></h4>
                        <small class="text-muted">From: <strong class="text-dark"><?php echo escape($selected_msg['name']); ?></strong> (<?php echo escape($selected_msg['email']); ?>)</small>
                    </div>
                    <a href="index.php?action=delete&id=<?php echo $selected_msg['id']; ?>&token=<?php echo $_SESSION['csrf_token']; ?>" class="btn btn-sm btn-outline-danger" title="Delete Inquiry" onclick="return confirm('Delete this inquiry?');">
                        <i class="bi bi-trash"></i> Delete
                    </a>
                </div>
                
                <div class="row g-2 mb-4 bg-light p-3 rounded" style="border: 1px solid #EFEAE0 !important;">
                    <div class="col-12">
                        <small class="text-muted d-block">Contact Phone</small>
                        <span class="fw-bold text-dark"><?php echo !empty($selected_msg['phone']) ? escape($selected_msg['phone']) : 'No phone provided'; ?></span>
                    </div>
                    <div class="col-12 mt-2">
                        <small class="text-muted d-block">Received Date</small>
                        <span class="text-muted small"><?php echo date('F d, Y \a\t h:i A', strtotime($selected_msg['created_at'])); ?></span>
                    </div>
                </div>
                
                <div>
                    <h5 class="fw-bold text-dark mb-2">Message Body</h5>
                    <p class="text-dark bg-white p-3 border rounded" style="white-space: pre-wrap; font-size: 0.95rem; line-height: 1.6; border-color: #EFEAE0 !important;">
                        <?php echo escape($selected_msg['message']); ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="card admin-card p-5 text-center shadow-sm bg-white d-none d-lg-block sticky-lg-top" style="top: 100px;">
                <i class="bi bi-envelope-paper display-3 text-muted mb-3" style="color: var(--accent-gold) !important;"></i>
                <h4 class="display-font text-dark">Open a Message</h4>
                <p class="text-muted small">Click on a contact message in the left inbox listing to view customer inquiry details, sender email, contact phone number, and body content.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
