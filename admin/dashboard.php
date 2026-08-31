<?php
/**
 * admin/dashboard.php
 * Upgraded Administrative Dashboard Overview with Live Order Notifications & KPI Stats
 */

$page_title = 'Dashboard Overview';

// Include layouts and auth protection gates
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db_connection();

// Initialize KPIs
$total_orders = 0;
$todays_orders = 0;
$todays_revenue = 0.00;
$pending_orders = 0;
$pending_reservations = 0;
$total_menu_items = 0;

$recent_orders = [];
$recent_reservations = [];
$latest_pending_order = null;

try {
    // 1. Check for the most recent pending order for the live notification banner
    $stmt = $db->query("SELECT o.order_number, o.total_amount, o.order_type 
                        FROM orders o 
                        WHERE o.order_status = 'pending' 
                        ORDER BY o.id DESC LIMIT 1");
    $latest_pending_order = $stmt->fetch();

    // 2. Fetch KPI Counts
    $total_orders = (int)$db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
    $todays_orders = (int)$db->query("SELECT COUNT(*) FROM orders WHERE DATE(created_at) = CURDATE()")->fetchColumn();
    $todays_revenue = (float)$db->query("SELECT IFNULL(SUM(total_amount), 0.00) FROM orders WHERE DATE(created_at) = CURDATE() AND order_status != 'cancelled'")->fetchColumn();
    $pending_orders = (int)$db->query("SELECT COUNT(*) FROM orders WHERE order_status = 'pending'")->fetchColumn();
    $pending_reservations = (int)$db->query("SELECT COUNT(*) FROM reservations WHERE status = 'pending'")->fetchColumn();
    $total_menu_items = (int)$db->query("SELECT COUNT(*) FROM menu_items")->fetchColumn();
    
    // 3. Fetch Recent Orders (5 latest)
    $stmt = $db->query("SELECT o.order_number, c.name as customer_name, o.total_amount, o.order_status, o.created_at 
                        FROM orders o 
                        JOIN customers c ON o.customer_id = c.id 
                        ORDER BY o.id DESC LIMIT 5");
    $recent_orders = $stmt->fetchAll();
    
    // 4. Fetch Recent Reservations (5 latest)
    $stmt = $db->query("SELECT id, name, reservation_date, reservation_time, guests, status 
                        FROM reservations 
                        ORDER BY id DESC LIMIT 5");
    $recent_reservations = $stmt->fetchAll();
    
} catch (PDOException $e) {
    error_log("Dashboard Data Query Exception: " . $e->getMessage());
    set_flash_message('danger', 'A database retrieval error occurred. Please check logs.');
}
?>

<!-- 🔔 Live New Order Notification Banner -->
<?php if ($latest_pending_order): ?>
    <div class="alert alert-warning border shadow-sm p-4 mb-4" style="background-color: #FFF9F2; border-color: #D88C6A !important; border-left: 5px solid #D88C6A !important;">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
            <div class="d-flex align-items-center gap-3">
                <span class="fs-1">🔔</span>
                <div>
                    <h4 class="alert-heading display-font text-dark mb-1 fw-bold">NEW ORDER</h4>
                    <p class="mb-0 text-muted">
                        Order <strong class="text-dark">#<?php echo escape($latest_pending_order['order_number']); ?></strong> &bull; 
                        Total: <strong class="text-dark"><?php echo format_price($latest_pending_order['total_amount']); ?></strong> &bull; 
                        Type: <strong class="text-capitalize text-dark"><?php echo escape($latest_pending_order['order_type']); ?></strong>
                    </p>
                </div>
            </div>
            <a href="<?php echo BASE_URL; ?>/admin/orders/index.php?order_number=<?php echo urlencode($latest_pending_order['order_number']); ?>" class="btn btn-terracotta px-4 py-2 fw-bold">View Order</a>
        </div>
    </div>
<?php endif; ?>

<!-- KPI Stats Row -->
<div class="row g-4 mb-5">
    <!-- Card 1: Today's Revenue -->
    <div class="col-sm-6 col-lg-3">
        <div class="card admin-card p-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Today's Revenue</small>
                    <div class="stat-value text-success" style="color: var(--accent-sage) !important;"><?php echo format_price($todays_revenue); ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: #EBF7E9; color: var(--accent-sage);">
                    <i class="bi bi-wallet2 fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card 2: Today's Orders -->
    <div class="col-sm-6 col-lg-3">
        <div class="card admin-card p-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Today's Orders</small>
                    <div class="stat-value text-dark"><?php echo $todays_orders; ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: #FAF7F0; color: var(--accent-coffee);">
                    <i class="bi bi-bag-check fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card 3: Pending Orders -->
    <div class="col-sm-6 col-lg-3">
        <div class="card admin-card p-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Pending Orders</small>
                    <div class="stat-value text-warning"><?php echo $pending_orders; ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: #FFF9F2; color: #D88C6A;">
                    <i class="bi bi-clock-history fs-3"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Card 4: Pending Bookings -->
    <div class="col-sm-6 col-lg-3">
        <div class="card admin-card p-4 shadow-sm">
            <div class="d-flex align-items-center justify-content-between">
                <div>
                    <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Pending Bookings</small>
                    <div class="stat-value text-info" style="color: var(--accent-terracotta) !important;"><?php echo $pending_reservations; ?></div>
                </div>
                <div class="rounded-circle p-3" style="background-color: #FDF1ED; color: var(--accent-terracotta);">
                    <i class="bi bi-calendar-event fs-3"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Listings Row -->
<div class="row g-4">
    <!-- Left: Recent Orders Table -->
    <div class="col-lg-7">
        <div class="card admin-card p-4 shadow-sm h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="display-font text-dark fs-4 mb-0">Recent Orders</h3>
                <a href="<?php echo BASE_URL; ?>/admin/orders/index.php" class="btn btn-outline-sage btn-sm">Manage All</a>
            </div>
            
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small uppercase">
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_orders)): ?>
                            <?php foreach ($recent_orders as $ord): ?>
                                <tr>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/admin/orders/index.php?order_number=<?php echo urlencode($ord['order_number']); ?>" class="fw-bold">
                                            <?php echo escape($ord['order_number']); ?>
                                        </a>
                                    </td>
                                    <td class="text-dark fw-medium"><?php echo escape($ord['customer_name']); ?></td>
                                    <td class="text-muted"><?php echo format_price($ord['total_amount']); ?></td>
                                    <td><?php echo get_order_status_badge($ord['order_status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No orders received yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Right: Recent Reservations -->
    <div class="col-lg-5">
        <div class="card admin-card p-4 shadow-sm h-100 bg-white">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="display-font text-dark fs-4 mb-0">Recent Bookings</h3>
                <a href="<?php echo BASE_URL; ?>/admin/reservations/index.php" class="btn btn-outline-sage btn-sm">Manage All</a>
            </div>
            
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small uppercase">
                            <th>Name</th>
                            <th>Date / Time</th>
                            <th>Guests</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($recent_reservations)): ?>
                            <?php foreach ($recent_reservations as $res): ?>
                                <tr>
                                    <td class="text-dark fw-medium"><?php echo escape($res['name']); ?></td>
                                    <td class="small text-muted">
                                        <?php echo date('M d, Y', strtotime($res['reservation_date'])); ?><br>
                                        <?php echo date('h:i A', strtotime($res['reservation_time'])); ?>
                                    </td>
                                    <td class="text-center"><?php echo $res['guests']; ?></td>
                                    <td><?php echo get_reservation_status_badge($res['status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-4 text-muted">No reservations booked yet.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php 
// Include layout footer
require_once __DIR__ . '/includes/footer.php'; 
?>
