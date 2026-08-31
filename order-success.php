<?php
/**
 * order-success.php
 * Order Success Page - Securely shows order details and clears cart
 */

$page_title = 'Order Placed';
$page_description = 'Thank you for your order! Your request has been received and is being prepared.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$order_number = isset($_GET['order_number']) ? trim($_GET['order_number']) : '';
$order_details = null;
$order_items = [];

$db = get_db_connection();

// Security: Verify that the order number matches the last order placed in this session.
// This prevents unauthorized users from guessing order numbers and seeing other customers' details.
$session_order = isset($_SESSION['last_placed_order_number']) ? $_SESSION['last_placed_order_number'] : '';

if (!empty($order_number) && $order_number === $session_order) {
    try {
        // Query Order & Customer Info
        $stmt = $db->prepare("SELECT o.*, c.name, c.email, c.phone 
                              FROM orders o 
                              JOIN customers c ON o.customer_id = c.id 
                              WHERE o.order_number = :order_number 
                              LIMIT 1");
        $stmt->execute([':order_number' => $order_number]);
        $order_details = $stmt->fetch();
        
        if ($order_details) {
            // Query Order Items
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
?>

<!-- Success Body -->
<section class="section-padding">
    <div class="container text-center max-width-md" style="max-width: 750px;">
        
        <?php if ($order_details): ?>
            <!-- Success Icon -->
            <div class="mb-4 text-success" style="color: var(--accent-sage) !important;">
                <i class="bi bi-check-circle display-1"></i>
            </div>
            
            <span class="hero-subtitle mb-2 d-inline-block">Thank you!</span>
            <h1 class="display-4 display-font mb-3">Order Received</h1>
            <p class="text-muted mb-5">
                Your order <strong class="text-dark"><?php echo escape($order_number); ?></strong> has been successfully placed. We've sent a confirmation email to <strong><?php echo escape($order_details['email']); ?></strong>.
            </p>
            
            <!-- Order Details Card -->
            <div class="card p-4 text-start border shadow-sm mb-5 bg-white" style="border-radius: var(--border-radius-md);">
                <div class="d-flex justify-content-between align-items-center mb-3" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 12px;">
                    <h3 class="display-font h3 mb-0 text-dark">Order Details</h3>
                    <span class="badge bg-warning text-dark px-3 py-2 rounded-pill text-capitalize"><?php echo escape($order_details['order_status']); ?></span>
                </div>
                
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Order Type</small>
                        <span class="fw-bold text-dark text-capitalize"><?php echo escape($order_details['order_type']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Payment Method</small>
                        <span class="fw-bold text-dark"><?php echo escape($order_details['payment_method']); ?></span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Estimated Preparation Time</small>
                        <span class="fw-bold text-success" style="color: var(--accent-sage) !important;">
                            <?php echo $order_details['order_type'] === 'delivery' ? '40-50 minutes' : '15-20 minutes'; ?>
                        </span>
                    </div>
                    <?php if ($order_details['order_type'] === 'delivery'): ?>
                        <div class="col-12">
                            <small class="text-muted d-block">Delivery Address</small>
                            <span class="fw-bold text-dark"><?php echo escape($order_details['delivery_address']); ?></span>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($order_details['notes'])): ?>
                        <div class="col-12">
                            <small class="text-muted d-block">Special Requests</small>
                            <span class="text-muted small">"<?php echo escape($order_details['notes']); ?>"</span>
                        </div>
                    <?php endif; ?>
                </div>
                
                <h4 class="display-font h4 mb-3 text-dark" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 8px;">Items Ordered</h4>
                <div class="table-responsive">
                    <table class="table table-borderless align-middle mb-0">
                        <tbody>
                            <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td class="ps-0 text-dark fw-bold"><?php echo escape($item['item_name']); ?> <span class="text-muted fw-normal">x <?php echo $item['quantity']; ?></span></td>
                                    <td class="text-end pe-0 text-muted"><?php echo format_price($item['subtotal']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <tr style="border-top: 1px solid #EFEAE0;">
                                <td class="ps-0 text-muted pt-3">Subtotal</td>
                                <td class="text-end pe-0 pt-3 text-dark"><?php echo format_price($order_details['subtotal']); ?></td>
                            </tr>
                            <?php if ((float)$order_details['delivery_charge'] > 0): ?>
                                <tr>
                                    <td class="ps-0 text-muted">Delivery Charge</td>
                                    <td class="text-end pe-0 text-dark"><?php echo format_price($order_details['delivery_charge']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr style="font-size: 1.15rem;">
                                <td class="ps-0 text-dark fw-bold pt-2">Grand Total</td>
                                <td class="text-end pe-0 text-dark fw-bold pt-2 display-font"><?php echo format_price($order_details['total_amount']); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <a href="menu.php" class="btn btn-sage px-4 py-3">Back to Menu</a>
            
            <!-- Clear local storage cart since order completed successfully -->
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    if (typeof clearCart === 'function') {
                        clearCart();
                        syncCartBadge();
                    }
                });
            </script>
            
            <?php 
                // Unset the placed order session token so reload won't expose details
                unset($_SESSION['last_placed_order_number']);
            ?>
            
        <?php else: ?>
            <!-- Invalid Access State -->
            <div class="mb-4 text-danger">
                <i class="bi bi-exclamation-triangle display-1"></i>
            </div>
            <h1 class="display-4 display-font mb-3">Invalid Request</h1>
            <p class="text-muted mb-4">
                We couldn't retrieve details for this order. It may have expired or is an unauthorized access request.
            </p>
            <a href="index.php" class="btn btn-sage px-4 py-3">Back to Home</a>
        <?php endif; ?>
        
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
