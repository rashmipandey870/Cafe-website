<?php
/**
 * track-order.php
 * Public Order Tracking Page - Visual timeline of order statuses
 */

$page_title = 'Track Order';
$page_description = 'Track the live preparation and delivery status of your Mellow & Meadow Café order.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$order_number = isset($_GET['order_number']) ? strtoupper(trim($_GET['order_number'])) : '';
$order = null;
$order_items = [];
$db = get_db_connection();

if (!empty($order_number)) {
    try {
        $stmt = $db->prepare("SELECT o.*, c.name as customer_name, c.phone as customer_phone 
                              FROM orders o 
                              JOIN customers c ON o.customer_id = c.id 
                              WHERE o.order_number = :order_num LIMIT 1");
        $stmt->execute([':order_num' => $order_number]);
        $order = $stmt->fetch();
        
        if ($order) {
            $item_stmt = $db->prepare("SELECT * FROM order_items WHERE order_id = :order_id");
            $item_stmt->execute([':order_id' => $order['id']]);
            $order_items = $item_stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("Order tracking query fail: " . $e->getMessage());
    }
}

// Map order statuses to step indexes for the timeline rendering
$status_steps = [
    'pending'          => 1,
    'confirmed'        => 2,
    'preparing'        => 3,
    'ready'            => 4,
    'out_for_delivery' => 5,
    'completed'        => 6
];

// For pickup, we skip the Out for Delivery step
$current_step = 0;
if ($order) {
    $status = $order['order_status'];
    if ($order['order_type'] === 'pickup' && $status === 'completed') {
        $current_step = 5; // Complete is step 5 for pickup
    } elseif ($order['order_type'] === 'pickup' && $status === 'ready') {
        $current_step = 4; // Ready is step 4
    } else {
        $current_step = isset($status_steps[$status]) ? $status_steps[$status] : 0;
    }
}
?>

<section class="section-padding py-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <h1 class="display-4 display-font mb-2">Track Your Order</h1>
        <p class="text-muted mb-0">Follow our kitchen baristas and delivery crew in real time.</p>
    </div>
</section>

<section class="section-padding">
    <div class="container" style="max-width: 800px;">
        
        <!-- Search Card (Always shown if no order selected or order not found) -->
        <?php if (!$order): ?>
            <div class="card p-4 p-md-5 border shadow-sm bg-white mx-auto text-center" style="border-radius: var(--border-radius-md); max-width: 550px;">
                <i class="bi bi-compass display-1 text-muted mb-3" style="color: var(--accent-gold) !important;"></i>
                <h3 class="display-font text-dark mb-3">Find Your Order</h3>
                <p class="text-muted small mb-4">Enter the order number provided on your success screen or account history page (e.g. ORD-20260831-XXXX).</p>
                
                <?php if (!empty($order_number)): ?>
                    <div class="alert alert-warning mb-4" style="font-size: 0.9rem;">
                        No order found matching code <strong><?php echo escape($order_number); ?></strong>. Please double check characters.
                    </div>
                <?php endif; ?>
                
                <form action="track-order.php" method="GET">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control py-3" name="order_number" required placeholder="ORD-YYYYMMDD-XXXX" value="<?php echo escape($order_number); ?>">
                        <button type="submit" class="btn btn-sage px-4">Search</button>
                    </div>
                </form>
            </div>
            
        <?php else: ?>
            
            <!-- ORDER FOUND VIEW -->
            <!-- Details Card -->
            <div class="card p-4 border shadow-sm bg-white mb-4" style="border-radius: var(--border-radius-md);">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 border-bottom pb-3" style="border-color: #EFEAE0 !important;">
                    <div>
                        <small class="text-muted d-block">Order Reference</small>
                        <h2 class="display-font h3 text-dark mb-0"><?php echo escape($order['order_number']); ?></h2>
                    </div>
                    <div>
                        <small class="text-muted d-block text-end">Fulfillment Mode</small>
                        <span class="badge bg-secondary text-capitalize px-3 py-2 rounded-pill" style="background-color: var(--bg-secondary) !important; color: var(--color-text) !important; border: 1px solid #EFEAE0;">
                            <?php echo escape($order['order_type']); ?>
                        </span>
                    </div>
                </div>
                
                <!-- Cancelled state warning -->
                <?php if ($order['order_status'] === 'cancelled'): ?>
                    <div class="alert alert-danger p-4 text-center my-4" style="border-radius: var(--border-radius-sm);">
                        <i class="bi bi-x-circle-fill display-3 d-block mb-2"></i>
                        <h4 class="display-font">Order Cancelled</h4>
                        <p class="mb-0 text-muted small">This order has been cancelled by the café staff. For queries, please call us at <?php echo escape($settings['cafe_phone']); ?>.</p>
                    </div>
                <?php else: ?>
                    
                    <!-- TIMELINE GRID -->
                    <div class="my-5 position-relative">
                        <!-- Horizontal connector line -->
                        <div class="progress position-absolute top-50 start-0 translate-y-50 w-100 d-none d-md-flex" style="height: 3px; z-index: 1;">
                            <?php 
                                $total_steps = ($order['order_type'] === 'delivery') ? 6 : 5;
                                $percent = (($current_step - 1) / ($total_steps - 1)) * 100;
                            ?>
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percent; ?>%; background-color: var(--accent-sage);" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        
                        <!-- Timeline milestones container -->
                        <div class="row position-relative justify-content-between g-2 text-center" style="z-index: 2;">
                            <!-- Step 1: Received -->
                            <div class="col-6 col-md">
                                <div class="mb-2">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                          style="width: 45px; height: 45px; background-color: <?php echo $current_step >= 1 ? 'var(--accent-sage)' : '#EFEAE0'; ?>; color: <?php echo $current_step >= 1 ? '#ffffff' : 'var(--color-muted)'; ?>;">
                                        <i class="bi bi-check2-all"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0 small fw-bold <?php echo $current_step >= 1 ? 'text-dark' : 'text-muted'; ?>">Received</h6>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Order registered</small>
                            </div>
                            
                            <!-- Step 2: Confirmed -->
                            <div class="col-6 col-md">
                                <div class="mb-2">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                          style="width: 45px; height: 45px; background-color: <?php echo $current_step >= 2 ? 'var(--accent-sage)' : '#EFEAE0'; ?>; color: <?php echo $current_step >= 2 ? '#ffffff' : 'var(--color-muted)'; ?>;">
                                        <i class="bi bi-hand-thumbs-up"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0 small fw-bold <?php echo $current_step >= 2 ? 'text-dark' : 'text-muted'; ?>">Confirmed</h6>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Accepted by staff</small>
                            </div>
                            
                            <!-- Step 3: Preparing -->
                            <div class="col-6 col-md mt-4 mt-md-0">
                                <div class="mb-2">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                          style="width: 45px; height: 45px; background-color: <?php echo $current_step >= 3 ? 'var(--accent-sage)' : '#EFEAE0'; ?>; color: <?php echo $current_step >= 3 ? '#ffffff' : 'var(--color-muted)'; ?>;">
                                        <i class="bi bi-cup-hot"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0 small fw-bold <?php echo $current_step >= 3 ? 'text-dark' : 'text-muted'; ?>">Preparing</h6>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Kitchen processing</small>
                            </div>
                            
                            <!-- Step 4: Ready -->
                            <div class="col-6 col-md mt-4 mt-md-0">
                                <div class="mb-2">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                          style="width: 45px; height: 45px; background-color: <?php echo $current_step >= 4 ? 'var(--accent-sage)' : '#EFEAE0'; ?>; color: <?php echo $current_step >= 4 ? '#ffffff' : 'var(--color-muted)'; ?>;">
                                        <i class="bi bi-box2-heart"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0 small fw-bold <?php echo $current_step >= 4 ? 'text-dark' : 'text-muted'; ?>">Ready</h6>
                                <small class="text-muted d-block" style="font-size: 0.75rem;"><?php echo $order['order_type'] === 'delivery' ? 'Packed for courier' : 'Ready at counter'; ?></small>
                            </div>
                            
                            <!-- Step 5: Out for Delivery (Delivery only) -->
                            <?php if ($order['order_type'] === 'delivery'): ?>
                                <div class="col-6 col-md mt-4 mt-md-0">
                                    <div class="mb-2">
                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                              style="width: 45px; height: 45px; background-color: <?php echo $current_step >= 5 ? 'var(--accent-sage)' : '#EFEAE0'; ?>; color: <?php echo $current_step >= 5 ? '#ffffff' : 'var(--color-muted)'; ?>;">
                                            <i class="bi bi-bicycle"></i>
                                        </span>
                                    </div>
                                    <h6 class="mb-0 small fw-bold <?php echo $current_step >= 5 ? 'text-dark' : 'text-muted'; ?>">Out for Delivery</h6>
                                    <small class="text-muted d-block" style="font-size: 0.75rem;">Courier transit</small>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Step 5/6: Completed -->
                            <div class="col-6 col-md mt-4 mt-md-0">
                                <div class="mb-2">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center" 
                                          style="width: 45px; height: 45px; background-color: <?php echo (($order['order_type'] === 'delivery' && $current_step >= 6) || ($order['order_type'] === 'pickup' && $current_step >= 5)) ? 'var(--accent-sage)' : '#EFEAE0'; ?>; color: <?php echo (($order['order_type'] === 'delivery' && $current_step >= 6) || ($order['order_type'] === 'pickup' && $current_step >= 5)) ? '#ffffff' : 'var(--color-muted)'; ?>;">
                                        <i class="bi bi-house-heart"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0 small fw-bold <?php echo (($order['order_type'] === 'delivery' && $current_step >= 6) || ($order['order_type'] === 'pickup' && $current_step >= 5)) ? 'text-dark' : 'text-muted'; ?>">Completed</h6>
                                <small class="text-muted d-block" style="font-size: 0.75rem;">Fulfilled & enjoyed</small>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <!-- Order details layout -->
                <div class="row g-4 mt-3 pt-3 border-top" style="border-color: #EFEAE0 !important;">
                    <div class="col-md-6">
                        <h5 class="fw-bold text-dark mb-2">Delivery Summary</h5>
                        <p class="small text-muted mb-1"><i class="bi bi-person me-2"></i><?php echo escape($order['customer_name']); ?></p>
                        <?php if ($order['order_type'] === 'delivery'): ?>
                            <p class="small text-muted mb-0"><i class="bi bi-geo-alt me-2"></i><?php echo escape($order['delivery_address']); ?></p>
                        <?php else: ?>
                            <p class="small text-muted mb-0"><i class="bi bi-shop me-2"></i>Self-Pickup at Café counter</p>
                        <?php endif; ?>
                    </div>
                    <div class="col-md-6 text-md-end">
                        <h5 class="fw-bold text-dark mb-2">Need Assistance?</h5>
                        <p class="small text-muted mb-1">Call Café Desk: <strong><?php echo escape($settings['cafe_phone']); ?></strong></p>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['cafe_whatsapp']); ?>" class="btn btn-sm btn-outline-sage mt-1" target="_blank">
                            <i class="bi bi-whatsapp me-1"></i> WhatsApp Support
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Items table -->
            <div class="card p-4 border shadow-sm bg-white" style="border-radius: var(--border-radius-md);">
                <h4 class="display-font h4 text-dark mb-3">Items Summary</h4>
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0 text-muted small">
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="ps-0 text-dark fw-bold"><?php echo escape($item['item_name']); ?> <span class="text-muted fw-normal">x <?php echo $item['quantity']; ?></span></td>
                                    <td class="text-end pe-0"><?php echo format_price($item['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="border-top" style="border-color: #EFEAE0 !important;">
                                <td class="ps-0 pt-2">Subtotal</td>
                                <td class="text-end pe-0 pt-2"><?php echo format_price($order['subtotal']); ?></td>
                            </tr>
                            <?php if ((float)$order['discount_amount'] > 0): ?>
                                <tr>
                                    <td class="ps-0 text-success">Promotion Discount (<?php echo !empty($order['coupon_code']) ? escape($order['coupon_code']) : 'Applied Campaign'; ?>)</td>
                                    <td class="text-end pe-0 text-success">-<?php echo format_price($order['discount_amount']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ((float)$order['tax_amount'] > 0): ?>
                                <tr>
                                    <td class="ps-0">GST/Tax</td>
                                    <td class="text-end pe-0"><?php echo format_price($order['tax_amount']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ((float)$order['delivery_charge'] > 0): ?>
                                <tr>
                                    <td class="ps-0">Delivery Charge</td>
                                    <td class="text-end pe-0"><?php echo format_price($order['delivery_charge']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="fw-bold text-dark font-monospace" style="font-size: 1.1rem;">
                                <td class="ps-0 pt-2">Grand Total</td>
                                <td class="text-end pe-0 pt-2 display-font" style="font-size: 1.25rem;"><?php echo format_price($order['total_amount']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="track-order.php" class="btn btn-light text-muted btn-sm">Search another order</a>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
