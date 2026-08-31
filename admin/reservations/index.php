<?php
/**
 * admin/reservations/index.php
 * Administrative Table Reservation Management - Filtering, Confirmations, Cancellations
 */

$page_title = 'Manage Reservations';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();

// 1. Capture Filters
$date_filter = isset($_GET['date']) ? trim($_GET['date']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';

$action = isset($_GET['action']) ? trim($_GET['action']) : '';
$res_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// 2. Handle Status Changes (GET transitions with safety validation)
if (!empty($action) && $res_id > 0) {
    // Validate GET CSRF token
    $get_token = isset($_GET['token']) ? trim($_GET['token']) : '';
    if (!verify_csrf_get($get_token)) {
        set_flash_message('danger', 'Security token invalid or expired. Action blocked.');
        header("Location: index.php");
        exit;
    }
    
    $valid_actions = ['confirm', 'cancel', 'complete'];
    if (in_array($action, $valid_actions)) {
        try {
            $new_status = 'pending';
            if ($action === 'confirm') $new_status = 'confirmed';
            if ($action === 'cancel') $new_status = 'cancelled';
            if ($action === 'complete') $new_status = 'completed';
            
            $stmt = $db->prepare("UPDATE reservations SET status = :status WHERE id = :id");
            $stmt->execute([':status' => $new_status, ':id' => $res_id]);
            
            set_flash_message('success', "Reservation status updated to '{$new_status}'.");
            header("Location: index.php?date=" . urlencode($date_filter) . "&status=" . urlencode($status_filter));
            exit;
        } catch (PDOException $e) {
            error_log("Reservation Status Update Error: " . $e->getMessage());
            set_flash_message('danger', 'Failed to update reservation status.');
        }
    }
}

// 3. Construct Query
$query = "SELECT * FROM reservations WHERE 1=1";
$params = [];

if (!empty($date_filter)) {
    $query .= " AND reservation_date = :res_date";
    $params[':res_date'] = $date_filter;
}

if (!empty($status_filter)) {
    $query .= " AND status = :status";
    $params[':status'] = $status_filter;
}

$query .= " ORDER BY reservation_date ASC, reservation_time ASC LIMIT 100";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Reservations List Fetch Error: " . $e->getMessage());
    $reservations = [];
}
?>

<div class="row g-4">
    <!-- Top Filter Panel -->
    <div class="col-12">
        <div class="card admin-card p-4 shadow-sm bg-white mb-4">
            <form action="index.php" method="GET" class="row g-3 align-items-center">
                <!-- Date Filter -->
                <div class="col-md-4">
                    <label for="date" class="form-label mb-1 fw-semibold small">Filter by Date</label>
                    <input type="date" class="form-control py-2" id="date" name="date" value="<?php echo escape($date_filter); ?>">
                </div>
                
                <!-- Status Filter -->
                <div class="col-md-4">
                    <label for="status" class="form-label mb-1 fw-semibold small">Filter by Status</label>
                    <select class="form-select py-2" id="status" name="status">
                        <option value="">All Statuses</option>
                        <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                </div>
                
                <!-- Actions Buttons -->
                <div class="col-md-4 d-flex gap-2 align-self-end">
                    <button type="submit" class="btn btn-sage w-100 py-2">Filter</button>
                    <?php if (!empty($date_filter) || !empty($status_filter)): ?>
                        <a href="index.php" class="btn btn-light w-100 py-2 text-muted">Clear Filters</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Listing Panel -->
    <div class="col-12">
        <div class="card admin-card p-4 shadow-sm bg-white">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr class="text-muted small uppercase">
                            <th>Customer Name</th>
                            <th>Contact Info</th>
                            <th>Booking Date & Time</th>
                            <th>Guests</th>
                            <th>Special Requests</th>
                            <th>Status</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($reservations)): ?>
                            <?php foreach ($reservations as $res): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo escape($res['name']); ?></strong>
                                        <small class="text-muted">Booked: <?php echo date('M d, Y', strtotime($res['created_at'])); ?></small>
                                    </td>
                                    <td>
                                        <small class="text-dark d-block"><i class="bi bi-telephone me-2"></i><?php echo escape($res['phone']); ?></small>
                                        <small class="text-muted d-block"><i class="bi bi-envelope me-2"></i><?php echo escape($res['email']); ?></small>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo date('M d, Y', strtotime($res['reservation_date'])); ?></strong>
                                        <span class="text-muted small"><i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($res['reservation_time'])); ?></span>
                                    </td>
                                    <td class="text-center text-dark fw-bold">
                                        <?php echo (int)$res['guests']; ?>
                                    </td>
                                    <td class="text-muted small" style="max-width: 220px;">
                                        <?php echo !empty($res['special_request']) ? '<em>"' . escape($res['special_request']) . '"</em>' : '<span class="text-muted">—</span>'; ?>
                                    </td>
                                    <td>
                                        <?php echo get_reservation_status_badge($res['status']); ?>
                                    </td>
                                    <td class="text-end">
                                        <div class="btn-group">
                                            <?php if ($res['status'] === 'pending'): ?>
                                                <a href="index.php?action=confirm&id=<?php echo $res['id']; ?>&date=<?php echo urlencode($date_filter); ?>&status=<?php echo urlencode($status_filter); ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                   class="btn btn-sm btn-outline-success" title="Confirm Booking" onclick="return confirm('Confirm reservation for <?php echo escape($res['name']); ?>?');">
                                                    <i class="bi bi-check-lg"></i>
                                                </a>
                                                <a href="index.php?action=cancel&id=<?php echo $res['id']; ?>&date=<?php echo urlencode($date_filter); ?>&status=<?php echo urlencode($status_filter); ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" title="Cancel Booking" onclick="return confirm('Cancel reservation for <?php echo escape($res['name']); ?>?');">
                                                    <i class="bi bi-x-lg"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($res['status'] === 'confirmed'): ?>
                                                <a href="index.php?action=complete&id=<?php echo $res['id']; ?>&date=<?php echo urlencode($date_filter); ?>&status=<?php echo urlencode($status_filter); ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                   class="btn btn-sm btn-outline-primary" title="Mark Completed">
                                                    <i class="bi bi-flag-fill"></i>
                                                </a>
                                                <a href="index.php?action=cancel&id=<?php echo $res['id']; ?>&date=<?php echo urlencode($date_filter); ?>&status=<?php echo urlencode($status_filter); ?>&token=<?php echo $_SESSION['csrf_token']; ?>" 
                                                   class="btn btn-sm btn-outline-danger" title="Cancel Booking" onclick="return confirm('Cancel reservation for <?php echo escape($res['name']); ?>?');">
                                                    <i class="bi bi-x-lg"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php if ($res['status'] === 'completed' || $res['status'] === 'cancelled'): ?>
                                                <span class="text-muted small">Closed</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">No reservations found matching the selected filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
