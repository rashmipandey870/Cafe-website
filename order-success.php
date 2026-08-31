<?php
/**
 * order-success.php
 * Order Success Page with Dynamic NPCI UPI QR Code & Verification Badges
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/razorpay.php';
require_once __DIR__ . '/includes/qrcode.php';

$page_title = 'Order Placed';
$page_description = 'Thank you for your order! Your request has been received.';

$order_number = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';
$order_details = null;
$order_items = [];

$db = get_db_connection();
$session_order = isset($_SESSION['last_placed_order_number']) ? $_SESSION['last_placed_order_number'] : '';

if (!empty($order_number) && $order_number === $session_order) {
    try {
        $stmt = $db->prepare("SELECT o.*, c.name, c.email, c.phone 
                              FROM orders o 
                              JOIN customers c ON o.customer_id = c.id 
                              WHERE o.order_number = :order_number 
                              LIMIT 1");
        $stmt->execute([':order_number' => $order_number]);
        $order_details = $stmt->fetch();
        
        if ($order_details) {
            $item_stmt = $db->prepare("SELECT item_name, quantity, unit_price, subtotal 
                                       FROM order_items 
                                       WHERE order_id = :order_id");
            $item_stmt->execute([':order_id' => $order_details['id']]);
            $order_items = $item_stmt->fetchAll();
        }
    } catch (PDOException $e) {
        error_log("Order Success Query Error: " . $e->getMessage());
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="section-padding py-4 py-md-5">
    <div class="container text-center" style="max-width: 680px;">
        
        <?php if ($order_details): ?>
            <!-- Success Icon -->
            <div class="mb-3 text-sage">
                <i class="bi bi-check-circle-fill display-2"></i>
            </div>
            
            <span class="badge bg-light text-sage border px-3 py-1 mb-2">Order Confirmed</span>
            <h1 class="display-5 display-font mb-2">Thank you, <?php echo escape($order_details['name']); ?>!</h1>
            <p class="text-muted mb-4 small">
                Order <strong class="text-dark">#<?php echo escape($order_number); ?></strong> is being prepared. Confirmation sent to <strong><?php echo escape($order_details['email']); ?></strong>.
            </p>

            <!-- DYNAMIC UPI QR PAYMENT CARD (If UPI payment selected) -->
            <?php 
                $is_upi_order = ($order_details['gateway'] === 'upi_qr' || strpos($order_details['payment_method'], 'UPI') !== false);
                $merchant_upi = !empty($settings['merchant_upi_id']) ? $settings['merchant_upi_id'] : 'mellowmeadow@upi';
                $merchant_name = !empty($settings['merchant_upi_name']) ? $settings['merchant_upi_name'] : $settings['cafe_name'];
                $upi_deep_link = generate_upi_uri($merchant_upi, $merchant_name, (float)$order_details['total_amount'], $order_number);
                $upi_qr_image = get_qr_image_url($upi_deep_link, 300);
            ?>

            <?php if ($is_upi_order && $order_details['payment_status'] !== 'paid'): ?>
                <div class="card p-4 border-0 shadow-sm mb-4 bg-white rounded-3 text-center border-start border-4 border-primary">
                    <div class="d-flex justify-content-center align-items-center gap-2 mb-2">
                        <i class="bi bi-qr-code-scan fs-4 text-primary"></i>
                        <h4 class="fw-bold mb-0 fs-5 text-dark">Scan & Pay with Any UPI App</h4>
                    </div>
                    <p class="text-muted small mb-3">Google Pay • PhonePe • Paytm • BHIM • Cred UPI</p>

                    <div class="p-3 bg-light rounded-3 d-inline-block mx-auto mb-3 border">
                        <img src="<?php echo escape($upi_qr_image); ?>" alt="UPI QR Code" class="img-fluid" style="width: 180px; height: 180px;">
                    </div>

                    <div class="fw-bold fs-5 text-dark mb-2">
                        Amount: <span class="text-sage"><?php echo format_price($order_details['total_amount']); ?></span>
                    </div>

                    <div class="d-md-none mt-2">
                        <a href="<?php echo escape($upi_deep_link); ?>" class="btn btn-primary btn-sm rounded-pill px-4 py-2 fw-bold shadow-xs">
                            <i class="bi bi-phone me-1"></i>Tap to Pay via Installed UPI App
                        </a>
                    </div>
                    <small class="text-muted mt-2 d-block" style="font-size: 0.75rem;">UPI ID: <strong><?php echo escape($merchant_upi); ?></strong> (Auto-filled)</small>
                </div>
            <?php endif; ?>

            <!-- Order Details Card -->
            <div class="card p-4 text-start border-0 shadow-sm mb-4 bg-white rounded-3">
                <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                    <h5 class="fw-bold mb-0 text-dark">Order Summary</h5>
                    <div>
                        <?php if ($order_details['payment_status'] === 'paid'): ?>
                            <span class="badge bg-success text-white px-3 py-1 rounded-pill small"><i class="bi bi-patch-check-fill me-1"></i>Paid Online</span>
                        <?php else: ?>
                            <span class="badge bg-warning text-dark px-3 py-1 rounded-pill small text-capitalize"><i class="bi bi-hourglass-split me-1"></i><?php echo escape($order_details['payment_status']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="row g-2 mb-3 small">
                    <div class="col-6">
                        <span class="text-muted d-block">Order Type</span>
                        <strong class="text-dark text-capitalize">
                            <?php echo escape($order_details['order_type']); ?>
                            <?php if (!empty($order_details['table_number'])): ?>
                                (Table #<?php echo escape($order_details['table_number']); ?>)
                            <?php endif; ?>
                        </strong>
                    </div>
                    <div class="col-6">
                        <span class="text-muted d-block">Payment Option</span>
                        <strong class="text-dark"><?php echo escape($order_details['payment_method']); ?></strong>
                    </div>
                    <?php if ($order_details['order_type'] === 'delivery' && !empty($order_details['delivery_address'])): ?>
                        <div class="col-12 mt-2">
                            <span class="text-muted d-block">Delivery Address</span>
                            <strong class="text-dark"><?php echo escape($order_details['delivery_address']); ?></strong>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h6 class="fw-bold mb-2 text-dark border-bottom pb-1 small text-uppercase" style="letter-spacing: 0.5px;">Items</h6>
                <div class="table-responsive mb-3">
                    <table class="table table-sm table-borderless align-middle mb-0 small">
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="ps-0 text-dark fw-bold"><?php echo escape($item['item_name']); ?> <span class="text-muted fw-normal">x <?php echo $item['quantity']; ?></span></td>
                                    <td class="text-end pe-0 text-muted"><?php echo format_price($item['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr class="border-top">
                                <td class="ps-0 text-muted pt-2">Subtotal</td>
                                <td class="text-end pe-0 pt-2 text-dark"><?php echo format_price($order_details['subtotal']); ?></td>
                            </tr>
                            <?php if ((float)$order_details['discount_amount'] > 0): ?>
                                <tr>
                                    <td class="ps-0 text-success">Discount (<?php echo escape($order_details['coupon_code']); ?>)</td>
                                    <td class="text-end pe-0 text-success">-<?php echo format_price($order_details['discount_amount']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ((float)$order_details['tax_amount'] > 0): ?>
                                <tr>
                                    <td class="ps-0 text-muted">GST / Taxes</td>
                                    <td class="text-end pe-0 text-dark"><?php echo format_price($order_details['tax_amount']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if ((float)$order_details['delivery_charge'] > 0): ?>
                                <tr>
                                    <td class="ps-0 text-muted">Delivery Fee</td>
                                    <td class="text-end pe-0 text-dark"><?php echo format_price($order_details['delivery_charge']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr class="border-top fw-bold fs-6">
                                <td class="ps-0 text-dark pt-2">Grand Total</td>
                                <td class="text-end pe-0 text-sage pt-2"><?php echo format_price($order_details['total_amount']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <a href="track-order.php?order_number=<?php echo urlencode($order_number); ?>" class="btn btn-sage text-white px-4 py-2 rounded-pill fw-bold">
                    <i class="bi bi-clock-history me-1"></i>Track Order Timeline
                </a>
                <a href="menu.php" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold">
                    Order More Items
                </a>
            </div>
            
            <!-- Clear local storage cart -->
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (typeof clearCart === 'function') {
                        clearCart();
                    }
                    localStorage.removeItem('cafe_cart');
                    if (typeof syncCartBadge === 'function') {
                        syncCartBadge();
                    }
                });
            </script>
            
            <?php unset($_SESSION['last_placed_order_number']); ?>
            
        <?php else: ?>
            <div class="py-5 text-center">
                <i class="bi bi-check2-circle display-1 text-sage mb-3 d-block"></i>
                <h2 class="display-font">Order Placed Successfully!</h2>
                <p class="text-muted small">Your order has been recorded. Check your active orders timeline or menu.</p>
                <div class="mt-4">
                    <a href="track-order.php" class="btn btn-sage text-white px-4 py-2 rounded-pill me-2">Track Orders</a>
                    <a href="menu.php" class="btn btn-outline-dark px-4 py-2 rounded-pill">View Menu</a>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
