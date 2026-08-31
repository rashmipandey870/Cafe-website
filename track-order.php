<?php
/**
 * track-order.php
 * Public Order Tracking Page - Interactive Timeline & Dedicated Live Delivery Route Map
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
        $stmt = $db->prepare("SELECT o.*, c.name as customer_name, c.phone as customer_phone, c.email as customer_email 
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

$current_step = 0;
if ($order) {
    $status = $order['order_status'];
    if ($order['order_type'] === 'pickup' || $order['order_type'] === 'dine_in') {
        if ($status === 'completed') {
            $current_step = 5;
        } elseif ($status === 'ready') {
            $current_step = 4;
        } else {
            $current_step = isset($status_steps[$status]) ? $status_steps[$status] : 1;
        }
    } else {
        $current_step = isset($status_steps[$status]) ? $status_steps[$status] : 1;
    }
}
?>

<section class="py-4 py-md-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <span class="badge bg-white text-dark border mb-2 px-3 py-1">Live Order Status</span>
        <h1 class="display-5 display-font mb-2">Track Your Order</h1>
        <p class="text-muted mb-0 small">Follow your freshly brewed coffee and handcrafted dishes in real time.</p>
    </div>
</section>

<section class="section-padding py-4 py-md-5">
    <div class="container" style="max-width: 820px;">
        
        <!-- Search Card (Shown when no order selected or to switch orders) -->
        <?php if (!$order): ?>
            <div class="card p-4 p-md-5 border-0 shadow-sm bg-white mx-auto text-center rounded-3" style="max-width: 520px;">
                <div class="mb-3 text-sage">
                    <i class="bi bi-geo-alt-fill display-3"></i>
                </div>
                <h3 class="display-font text-dark mb-2">Track Live Delivery</h3>
                <p class="text-muted small mb-4">Enter your order reference code (e.g. <strong>MM-2026-000001</strong>) to view your live preparation timeline and delivery map.</p>
                
                <?php if (!empty($order_number)): ?>
                    <div class="alert alert-warning mb-4 small">
                        <i class="bi bi-exclamation-circle me-1"></i>No order found for <strong><?php echo escape($order_number); ?></strong>. Please verify your order number.
                    </div>
                <?php endif; ?>
                
                <form action="track-order.php" method="GET">
                    <div class="input-group shadow-xs rounded-pill overflow-hidden bg-white border">
                        <span class="input-group-text bg-white border-0 ps-3 text-muted"><i class="bi bi-search"></i></span>
                        <input type="text" class="form-control border-0 shadow-none ps-2 text-uppercase" name="order_number" required placeholder="MM-2026-XXXXXX" value="<?php echo escape($order_number); ?>">
                        <button type="submit" class="btn btn-sage text-white px-4 fw-bold">Track</button>
                    </div>
                </form>
            </div>
            
        <?php else: ?>
            
            <!-- ORDER FOUND VIEW -->
            <div class="card p-4 border-0 shadow-sm bg-white mb-4 rounded-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3 border-bottom pb-3">
                    <div>
                        <small class="text-muted d-block" style="font-size: 0.8rem;">Order Reference</small>
                        <h2 class="display-font fs-3 text-dark mb-0"><?php echo escape($order['order_number']); ?></h2>
                    </div>
                    <div class="text-end">
                        <small class="text-muted d-block" style="font-size: 0.8rem;">Fulfillment</small>
                        <?php if ($order['order_type'] === 'dine_in'): ?>
                            <span class="badge bg-light text-sage border px-3 py-2 rounded-pill fw-bold">
                                <i class="bi bi-cup-hot me-1"></i>Dine-In (Table #<?php echo escape($order['table_number'] ?: 'Counter'); ?>)
                            </span>
                        <?php elseif ($order['order_type'] === 'delivery'): ?>
                            <span class="badge bg-light text-primary border px-3 py-2 rounded-pill fw-bold">
                                <i class="bi bi-bicycle me-1"></i>Home Delivery
                            </span>
                        <?php else: ?>
                            <span class="badge bg-light text-dark border px-3 py-2 rounded-pill fw-bold">
                                <i class="bi bi-bag-check me-1"></i>Takeaway Pickup
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Cancelled state warning -->
                <?php if ($order['order_status'] === 'cancelled'): ?>
                    <div class="alert alert-danger p-4 text-center my-3 rounded-3">
                        <i class="bi bi-x-circle-fill display-4 d-block mb-2"></i>
                        <h4 class="display-font mb-1">Order Cancelled</h4>
                        <p class="mb-0 text-muted small">This order has been cancelled. For help, contact café staff at <?php echo escape($settings['cafe_phone']); ?>.</p>
                    </div>
                <?php else: ?>
                    
                    <!-- PROGRESS TIMELINE -->
                    <div class="my-4 position-relative px-2">
                        <div class="progress position-absolute top-50 start-0 translate-y-50 w-100 d-none d-md-flex" style="height: 3px; z-index: 1;">
                            <?php 
                                $total_steps = ($order['order_type'] === 'delivery') ? 6 : 5;
                                $percent = (($current_step - 1) / ($total_steps - 1)) * 100;
                            ?>
                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percent; ?>%; background-color: var(--accent-sage);" aria-valuenow="<?php echo $percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                        
                        <div class="row position-relative justify-content-between g-2 text-center" style="z-index: 2;">
                            <!-- Step 1: Placed -->
                            <div class="col-4 col-md">
                                <div class="mb-1">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs" 
                                          style="width: 40px; height: 40px; background-color: <?php echo $current_step >= 1 ? 'var(--accent-sage)' : '#F5F2EC'; ?>; color: <?php echo $current_step >= 1 ? '#ffffff' : '#A09C94'; ?>;">
                                        <i class="bi bi-receipt"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0 small fw-bold <?php echo $current_step >= 1 ? 'text-dark' : 'text-muted'; ?>">Placed</h6>
                            </div>
                            
                            <!-- Step 2: Confirmed -->
                            <div class="col-4 col-md">
                                <div class="mb-1">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs" 
                                          style="width: 40px; height: 40px; background-color: <?php echo $current_step >= 2 ? 'var(--accent-sage)' : '#F5F2EC'; ?>; color: <?php echo $current_step >= 2 ? '#ffffff' : '#A09C94'; ?>;">
                                        <i class="bi bi-check-lg"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0 small fw-bold <?php echo $current_step >= 2 ? 'text-dark' : 'text-muted'; ?>">Accepted</h6>
                            </div>
                            
                            <!-- Step 3: Preparing -->
                            <div class="col-4 col-md">
                                <div class="mb-1">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs" 
                                          style="width: 40px; height: 40px; background-color: <?php echo $current_step >= 3 ? 'var(--accent-sage)' : '#F5F2EC'; ?>; color: <?php echo $current_step >= 3 ? '#ffffff' : '#A09C94'; ?>;">
                                        <i class="bi bi-cup-hot"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0 small fw-bold <?php echo $current_step >= 3 ? 'text-dark' : 'text-muted'; ?>">Kitchen</h6>
                            </div>
                            
                            <!-- Step 4: Ready -->
                            <div class="col-4 col-md mt-3 mt-md-0">
                                <div class="mb-1">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs" 
                                          style="width: 40px; height: 40px; background-color: <?php echo $current_step >= 4 ? 'var(--accent-sage)' : '#F5F2EC'; ?>; color: <?php echo $current_step >= 4 ? '#ffffff' : '#A09C94'; ?>;">
                                        <i class="bi bi-box-seam"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0 small fw-bold <?php echo $current_step >= 4 ? 'text-dark' : 'text-muted'; ?>"><?php echo $order['order_type'] === 'delivery' ? 'Packed' : 'Ready'; ?></h6>
                            </div>
                            
                            <!-- Step 5: Out for Delivery (Delivery only) -->
                            <?php if ($order['order_type'] === 'delivery'): ?>
                                <div class="col-4 col-md mt-3 mt-md-0">
                                    <div class="mb-1">
                                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs" 
                                              style="width: 40px; height: 40px; background-color: <?php echo $current_step >= 5 ? 'var(--accent-sage)' : '#F5F2EC'; ?>; color: <?php echo $current_step >= 5 ? '#ffffff' : '#A09C94'; ?>;">
                                            <i class="bi bi-bicycle"></i>
                                        </span>
                                    </div>
                                    <h6 class="mb-0 small fw-bold <?php echo $current_step >= 5 ? 'text-dark' : 'text-muted'; ?>">On Way</h6>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Final Step: Completed -->
                            <div class="col-4 col-md mt-3 mt-md-0">
                                <div class="mb-1">
                                    <span class="rounded-circle d-inline-flex align-items-center justify-content-center shadow-xs" 
                                          style="width: 40px; height: 40px; background-color: <?php echo (($order['order_type'] === 'delivery' && $current_step >= 6) || ($order['order_type'] !== 'delivery' && $current_step >= 5)) ? 'var(--accent-sage)' : '#F5F2EC'; ?>; color: <?php echo (($order['order_type'] === 'delivery' && $current_step >= 6) || ($order['order_type'] !== 'delivery' && $current_step >= 5)) ? '#ffffff' : '#A09C94'; ?>;">
                                        <i class="bi bi-check2-circle"></i>
                                    </span>
                                </div>
                                <h6 class="mb-0 small fw-bold <?php echo (($order['order_type'] === 'delivery' && $current_step >= 6) || ($order['order_type'] !== 'delivery' && $current_step >= 5)) ? 'text-dark' : 'text-muted'; ?>">Delivered</h6>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- DEDICATED LIVE DELIVERY TRACKING GOOGLE MAP (Visible ONLY when an order is being tracked) -->
            <?php if ($order['order_type'] === 'delivery' && !empty($settings['cafe_google_maps'])): ?>
                <div class="card p-4 border-0 shadow-sm bg-white mb-4 rounded-3 border-start border-4 border-sage">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                            <span class="badge bg-success text-white small px-2 py-1 mb-1"><i class="bi bi-broadcast me-1"></i>Live Order Dispatch</span>
                            <h4 class="display-font fs-5 text-dark mb-0">Delivery Route & Tracking Map</h4>
                        </div>
                        <div>
                            <?php if ($order['order_status'] === 'out_for_delivery'): ?>
                                <span class="badge bg-warning text-dark px-3 py-2 rounded-pill fw-bold">
                                    <i class="bi bi-bicycle me-1"></i>Rider En Route (15-20 Mins)
                                </span>
                            <?php elseif ($order['order_status'] === 'completed'): ?>
                                <span class="badge bg-success text-white px-3 py-2 rounded-pill fw-bold">
                                    <i class="bi bi-house-check me-1"></i>Delivered to Address
                                </span>
                            <?php else: ?>
                                <span class="badge bg-light text-dark border px-3 py-2 rounded-pill small">
                                    <i class="bi bi-clock me-1"></i>Dispatches after packing
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Destination Alert -->
                    <div class="p-3 bg-light rounded-3 mb-3 small d-flex align-items-center gap-2 border">
                        <i class="bi bi-geo-alt-fill text-danger fs-5"></i>
                        <div>
                            <strong class="text-dark d-block">Delivering to:</strong>
                            <span class="text-muted"><?php echo escape($order['delivery_address']); ?></span>
                        </div>
                    </div>

                    <!-- Google Map Container -->
                    <div class="map-container rounded-3 overflow-hidden border shadow-xs" style="height: 340px;">
                        <iframe 
                            src="<?php echo escape($settings['cafe_google_maps']); ?>" 
                            width="100%" 
                            height="100%" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 text-muted small flex-wrap gap-2">
                        <span><i class="bi bi-shop me-1 text-sage"></i>Dispatched from: <strong><?php echo escape($settings['cafe_name']); ?></strong></span>
                        <span><i class="bi bi-shield-check text-success me-1"></i>Contactless Delivery Active</span>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Customer & Order Summary -->
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <div class="card p-3 border-0 shadow-sm bg-white h-100 rounded-3">
                        <h6 class="fw-bold text-dark border-bottom pb-2 small text-uppercase">Customer Information</h6>
                        <p class="small mb-1 text-dark"><strong>Name:</strong> <?php echo escape($order['customer_name']); ?></p>
                        <p class="small mb-1 text-muted"><strong>Phone:</strong> <?php echo escape($order['customer_phone']); ?></p>
                        <p class="small mb-0 text-muted"><strong>Email:</strong> <?php echo escape($order['customer_email']); ?></p>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card p-3 border-0 shadow-sm bg-white h-100 rounded-3">
                        <h6 class="fw-bold text-dark border-bottom pb-2 small text-uppercase">Payment & Support</h6>
                        <p class="small mb-1 text-dark"><strong>Payment:</strong> <?php echo escape($order['payment_method']); ?> (<?php echo escape($order['payment_status']); ?>)</p>
                        <p class="small mb-1 text-muted"><strong>Need help?</strong> Call <?php echo escape($settings['cafe_phone']); ?></p>
                        <?php if (!empty($settings['cafe_whatsapp'])): ?>
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['cafe_whatsapp']); ?>" class="btn btn-sm btn-outline-sage mt-1" target="_blank">
                                <i class="bi bi-whatsapp me-1"></i> Chat on WhatsApp
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Items Table -->
            <div class="card p-4 border-0 shadow-sm bg-white rounded-3">
                <h5 class="fw-bold text-dark mb-3 fs-6 border-bottom pb-2">Items in this Order</h5>
                <div class="table-responsive">
                    <table class="table table-sm table-borderless align-middle mb-0 text-muted small">
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="ps-0 text-dark fw-bold"><?php echo escape($item['item_name']); ?> <span class="text-muted fw-normal">x <?php echo $item['quantity']; ?></span></td>
                                    <td class="text-end pe-0"><?php echo format_price($item['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="border-top">
                                <td class="ps-0 pt-2">Subtotal</td>
                                <td class="text-end pe-0 pt-2"><?php echo format_price($order['subtotal']); ?></td>
                            </tr>
                            <?php if ((float)$order['discount_amount'] > 0): ?>
                                <tr>
                                    <td class="ps-0 text-success">Promotion Discount</td>
                                    <td class="text-end pe-0 text-success">-<?php echo format_price($order['discount_amount']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ((float)$order['delivery_charge'] > 0): ?>
                                <tr>
                                    <td class="ps-0">Delivery Charge</td>
                                    <td class="text-end pe-0"><?php echo format_price($order['delivery_charge']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="fw-bold text-dark border-top fs-6">
                                <td class="ps-0 pt-2">Total Amount</td>
                                <td class="text-end pe-0 pt-2 text-sage display-font"><?php echo format_price($order['total_amount']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="track-order.php" class="btn btn-outline-secondary btn-sm rounded-pill px-3 me-2">Track Another Order</a>
                <a href="menu.php" class="btn btn-sage text-white btn-sm rounded-pill px-3">Order More Items</a>
            </div>
            
        <?php endif; ?>
        
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
