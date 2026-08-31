<?php
/**
 * admin/orders/index.php
 * Administrative Order Management - Status modifications, search, and filtering
 */

$page_title = 'Manage Orders';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();

// 1. Capture Filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? trim($_GET['status']) : '';
$selected_order_number = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';

$errors = [];
$selected_order = null;
$selected_order_items = [];

// 2. Handle Status Updates (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    enforce_csrf();
    
    $order_num = trim(filter_input(INPUT_POST, 'order_number', FILTER_DEFAULT));
    $new_status = trim(filter_input(INPUT_POST, 'order_status', FILTER_DEFAULT));
    $payment_status = trim(filter_input(INPUT_POST, 'payment_status', FILTER_DEFAULT));
    
    $valid_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled'];
    $valid_payments = ['pending', 'paid', 'failed'];
    
    if (!in_array($new_status, $valid_statuses)) {
        $errors[] = "Invalid order status choice.";
    }
    if (!in_array($payment_status, $valid_payments)) {
        $errors[] = "Invalid payment status choice.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE orders SET order_status = :status, payment_status = :payment WHERE order_number = :order_num");
            $stmt->execute([
                ':status' => $new_status,
                ':payment' => $payment_status,
                ':order_num' => $order_num
            ]);
            set_flash_message('success', "Order {$order_num} updated successfully.");
            
            // Retain selected order reference in URL
            header("Location: index.php?order_number=" . urlencode($order_num) . "&status=" . urlencode($status_filter) . "&search=" . urlencode($search));
            exit;
        } catch (PDOException $e) {
            error_log("Order Update Error: " . $e->getMessage());
            set_flash_message('danger', 'Failed to update order in database.');
        }
    }
}

// 3. Retrieve Details of Selected Order
if (!empty($selected_order_number)) {
    try {
        $stmt = $db->prepare("SELECT o.*, c.name as customer_name, c.email as customer_email, c.phone as customer_phone 
                              FROM orders o 
                              JOIN customers c ON o.customer_id = c.id 
                              WHERE o.order_number = :order_num LIMIT 1");
        $stmt->execute([':order_num' => $selected_order_number]);
        $selected_order = $stmt->fetch();
        
        if ($selected_order) {
            $item_stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
            $item_stmt->execute([':order_id' => $selected_order['id']]);
            $selected_order_items = $item_stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("Select Order Details Exception: " . $e->getMessage());
    }
}

// 4. Construct Query for Listing Orders (with search and status filters)
$query = "SELECT o.order_number, c.name as customer_name, o.total_amount, o.order_type, o.order_status, o.created_at 
          FROM orders o 
          JOIN customers c ON o.customer_id = c.id WHERE 1=1";
$params = [];

if (!empty($status_filter)) {
    $query .= " AND o.order_status = :status";
    $params[':status'] = $status_filter;
}

if (!empty($search)) {
    $query .= " AND (o.order_number LIKE :search OR c.name LIKE :search_name OR c.phone LIKE :search_phone)";
    $params[':search'] = "%{$search}%";
    $params[':search_name'] = "%{$search}%";
    $params[':search_phone'] = "%{$search}%";
}

$query .= " ORDER BY o.id DESC LIMIT 50";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $orders_list = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Orders List Exception: " . $e->getMessage());
    $orders_list = [];
}
?>

<div class="row g-4">
    <!-- Left Section: Search filters and orders list table -->
    <div class="col-lg-7 col-xl-8">
        <div class="card admin-card p-4 shadow-sm bg-white mb-4">
            <!-- Search & Filters Header Form -->
            <form action="index.php" method="GET" class="row g-2 align-items-center mb-3">
                <!-- Search bar -->
                <div class="col-md-5">
                    <input type="text" class="form-control py-2" name="search" placeholder="Search by name, phone or order #" value="<?php echo escape($search); ?>">
                </div>
                
                <!-- Status dropdown -->
                <div class="col-md-4">
                    <select class="form-select py-2" name="status">
                        <option value="">All Statuses</option>
                        <?php 
                            $status_options = ['pending', 'confirmed', 'preparing', 'ready', 'out_for_delivery', 'completed', 'cancelled'];
                            foreach ($status_options as $opt):
                        ?>
                            <option value="<?php echo $opt; ?>" <?php echo $opt === $status_filter ? 'selected' : ''; ?> class="text-capitalize">
                                <?php echo escape($opt); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- Search/Clear Buttons -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sage w-100 py-2">Filter</button>
                    <?php if (!empty($search) || !empty($status_filter)): ?>
                        <a href="index.php" class="btn btn-light w-100 py-2 text-muted">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
            
            <!-- List Table -->
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr class="text-muted small uppercase">
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Type</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($orders_list)): ?>
                            <?php foreach ($orders_list as $ord): ?>
                                <tr class="<?php echo $ord['order_number'] === $selected_order_number ? 'table-active' : ''; ?>">
                                    <td>
                                        <a href="index.php?order_number=<?php echo urlencode($ord['order_number']); ?>&status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="fw-bold">
                                            <?php echo escape($ord['order_number']); ?>
                                        </a>
                                    </td>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo escape($ord['customer_name']); ?></strong>
                                        <small class="text-muted"><?php echo date('M d, h:i A', strtotime($ord['created_at'])); ?></small>
                                    </td>
                                    <td class="text-capitalize small"><?php echo escape($ord['order_type']); ?></td>
                                    <td class="text-dark fw-semibold"><?php echo format_price($ord['total_amount']); ?></td>
                                    <td><?php echo get_order_status_badge($ord['order_status']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-5 text-muted">No orders found matching the filters.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Right Section: Selected Order Details (Inbox style layout) -->
    <div class="col-lg-5 col-xl-4">
        <?php if ($selected_order): ?>
            <div class="card admin-card p-4 shadow-sm bg-white sticky-lg-top" style="top: 100px; z-index: 100;">
                <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">
                    <h3 class="display-font text-dark fs-4 mb-0"><?php echo escape($selected_order['order_number']); ?></h3>
                    <a href="index.php?status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" class="btn-close" aria-label="Close details"></a>
                </div>
                
                <!-- Status Update Form -->
                <form action="index.php?status=<?php echo urlencode($status_filter); ?>&search=<?php echo urlencode($search); ?>" method="POST" class="mb-4 bg-light p-3 rounded border" style="border-color: #EFEAE0 !important;">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="action" value="update_status">
                    <input type="hidden" name="order_number" value="<?php echo escape($selected_order['order_number']); ?>">
                    
                    <div class="row g-2">
                        <!-- Order Status -->
                        <div class="col-12">
                            <label for="order_status" class="form-label mb-1 small fw-bold">Order Status</label>
                            <select class="form-select form-select-sm text-capitalize" name="order_status" id="order_status">
                                <?php foreach ($status_options as $opt): ?>
                                    <option value="<?php echo $opt; ?>" <?php echo $opt === $selected_order['order_status'] ? 'selected' : ''; ?>>
                                        <?php echo escape($opt); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <!-- Payment Status -->
                        <div class="col-12 mt-2">
                            <label for="payment_status" class="form-label mb-1 small fw-bold">Payment Status</label>
                            <select class="form-select form-select-sm text-capitalize" name="payment_status" id="payment_status">
                                <option value="pending" <?php echo $selected_order['payment_status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="paid" <?php echo $selected_order['payment_status'] === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                <option value="failed" <?php echo $selected_order['payment_status'] === 'failed' ? 'selected' : ''; ?>>Failed</option>
                            </select>
                        </div>
                        
                        <div class="col-12 mt-3">
                            <button type="submit" class="btn btn-sage btn-sm w-100">Update Status</button>
                        </div>
                    </div>
                </form>
                
                <!-- Customer Details -->
                <div class="mb-4">
                    <h5 class="fw-bold text-dark mb-2">Customer Profile</h5>
                    <p class="mb-1 text-dark small"><i class="bi bi-person me-2"></i><?php echo escape($selected_order['customer_name']); ?></p>
                    <p class="mb-1 text-muted small"><i class="bi bi-telephone me-2"></i><?php echo escape($selected_order['customer_phone']); ?></p>
                    <p class="mb-1 text-muted small"><i class="bi bi-envelope me-2"></i><?php echo escape($selected_order['customer_email']); ?></p>
                    <p class="mb-1 text-muted small"><i class="bi bi-clock me-2"></i><?php echo date('M d, Y - h:i A', strtotime($selected_order['created_at'])); ?></p>
                    
                    <?php if (!empty($selected_order['table_number'])): ?>
                        <div class="p-2 bg-light rounded mt-2 border">
                            <span class="badge bg-sage text-white me-1">Dine-In</span>
                            <strong>Table #<?php echo escape($selected_order['table_number']); ?></strong>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($selected_order['gateway_payment_id'])): ?>
                        <div class="p-2 bg-light rounded mt-2 border small font-monospace">
                            <span class="text-muted">Payment ID:</span> <?php echo escape($selected_order['gateway_payment_id']); ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Logistics -->
                <?php if ($selected_order['order_type'] === 'delivery'): ?>
                    <div class="mb-4">
                        <h5 class="fw-bold text-dark mb-2">Delivery Location</h5>
                        <p class="text-muted small mb-0"><i class="bi bi-geo-alt me-2"></i><?php echo escape($selected_order['delivery_address']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($selected_order['notes'])): ?>
                    <div class="mb-4">
                        <h5 class="fw-bold text-dark mb-2">Special Notes</h5>
                        <p class="text-muted small italic mb-0">"<?php echo escape($selected_order['notes']); ?>"</p>
                    </div>
                <?php endif; ?>
                
                <!-- Items list -->
                <div>
                    <h5 class="fw-bold text-dark mb-2">Items Detail</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-borderless small mb-0">
                            <tbody>
                                <?php foreach ($selected_order_items as $item): ?>
                                    <tr>
                                        <td><?php echo escape($item['item_name']); ?> <span class="text-muted">x <?php echo $item['quantity']; ?></span></td>
                                        <td class="text-end text-muted"><?php echo format_price($item['subtotal']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="border-top" style="border-color: #EFEAE0 !important;">
                                    <td class="pt-2 text-muted">Subtotal</td>
                                    <td class="pt-2 text-end text-dark"><?php echo format_price($selected_order['subtotal']); ?></td>
                                </tr>
                                <?php if ((float)$selected_order['delivery_charge'] > 0): ?>
                                    <tr>
                                        <td class="text-muted">Delivery</td>
                                        <td class="text-end text-dark"><?php echo format_price($selected_order['delivery_charge']); ?></td>
                                    </tr>
                                <?php endif; ?>
                                <tr class="fw-bold text-dark">
                                    <td>Grand Total</td>
                                    <td class="text-end"><?php echo format_price($selected_order['total_amount']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="card admin-card p-5 text-center shadow-sm bg-white d-none d-lg-block sticky-lg-top" style="top: 100px;">
                <i class="bi bi-receipt-cutoff display-3 text-muted mb-3" style="color: var(--accent-gold) !important;"></i>
                <h4 class="display-font text-dark">Select an Order</h4>
                <p class="text-muted small">Click on an order number in the left list to view customer details, ordered items, and to change order fulfillment status.</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
