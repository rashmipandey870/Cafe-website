<?php
/**
 * checkout.php
 * Upgraded Checkout Page - Robust Server-Side Calculations, Promotion Engine, and DB Transaction Wrapper
 */

$page_title = 'Checkout';
$page_description = 'Confirm your details, apply coupon codes, choose payment methods, and confirm your order.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = get_db_connection();
$errors = [];

// Load configurations from settings
$delivery_enabled = isset($settings['delivery_enabled']) ? (int)$settings['delivery_enabled'] : 1;
$delivery_charge_config = isset($settings['delivery_charge']) ? (float)$settings['delivery_charge'] : 45.00;
$free_delivery_above = isset($settings['free_delivery_above']) ? (float)$settings['free_delivery_above'] : 500.00;
$minimum_delivery_order = isset($settings['minimum_delivery_order']) ? (float)$settings['minimum_delivery_order'] : 200.00;

$tax_enabled = isset($settings['tax_enabled']) ? (int)$settings['tax_enabled'] : 1;
$tax_rate_config = isset($settings['tax_rate']) ? (float)$settings['tax_rate'] : 5.00;

// Dynamic Session Values if Logged-in Customer
$pre_name = isset($_SESSION['customer_logged_in']) ? $_SESSION['customer_name'] : '';
$pre_email = isset($_SESSION['customer_logged_in']) ? $_SESSION['customer_email'] : '';
$pre_phone = isset($_SESSION['customer_logged_in']) ? $_SESSION['customer_phone'] : '';

// Retrieve address details for logged in customer
$pre_address = '';
$pre_city = '';
$pre_zip = '';
if (isset($_SESSION['customer_logged_in'])) {
    try {
        $st = $db->prepare("SELECT address, city, zip FROM customers WHERE id = :id LIMIT 1");
        $st->execute([':id' => (int)$_SESSION['customer_id']]);
        $addr_data = $st->fetch();
        if ($addr_data) {
            $pre_address = $addr_data['address'] ?: '';
            $pre_city = $addr_data['city'] ?: '';
            $pre_zip = $addr_data['zip'] ?: '';
        }
    } catch (PDOException $e) {
        error_log("Pre-fetch customer details error: " . $e->getMessage());
    }
}

// Form Post Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf();
    
    // 1. Inputs Sanitization
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_DEFAULT));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $order_type = trim(filter_input(INPUT_POST, 'order_type', FILTER_DEFAULT));
    $notes = trim(filter_input(INPUT_POST, 'notes', FILTER_DEFAULT));
    $payment_method = trim(filter_input(INPUT_POST, 'payment_method', FILTER_DEFAULT));
    $coupon_submitted = strtoupper(trim(filter_input(INPUT_POST, 'coupon_code', FILTER_DEFAULT)));
    $cart_data_raw = isset($_POST['cart_data']) ? $_POST['cart_data'] : '';
    
    $address = trim(filter_input(INPUT_POST, 'delivery_address', FILTER_DEFAULT));
    $city = trim(filter_input(INPUT_POST, 'delivery_city', FILTER_DEFAULT));
    $zip = trim(filter_input(INPUT_POST, 'delivery_zip', FILTER_DEFAULT));
    
    // 2. Validate Contact Fields
    if (empty($name)) $errors[] = "Please enter your name.";
    if (empty($phone)) $errors[] = "Please enter your contact phone number.";
    if (!$email) $errors[] = "Please enter a valid email address.";
    if ($order_type !== 'pickup' && $order_type !== 'delivery') $errors[] = "Please select a valid order type.";
    
    if ($order_type === 'delivery') {
        if ($delivery_enabled !== 1) {
            $errors[] = "Home delivery is currently disabled. Please select pickup.";
        }
        if (empty($address) || empty($city) || empty($zip)) {
            $errors[] = "Please provide complete delivery address details.";
        }
    }
    
    // Parse cart json
    $cart_items = json_decode($cart_data_raw, true);
    if (empty($cart_items) || !is_array($cart_items)) {
        $errors[] = "Your shopping cart is empty.";
    }
    
    // 3. Database Recalculation & Order Transaction
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Resolve Customer ID
            $customer_id = 0;
            if (isset($_SESSION['customer_logged_in'])) {
                $customer_id = (int)$_SESSION['customer_id'];
            } else {
                // Find guest customer by phone/email
                $c_stmt = $db->prepare("SELECT id FROM customers WHERE email = :email OR phone = :phone LIMIT 1");
                $c_stmt->execute([':email' => $email, ':phone' => $phone]);
                $c_row = $c_stmt->fetch();
                if ($c_row) {
                    $customer_id = (int)$c_row['id'];
                } else {
                    // Create guest customer entry
                    $ins_c = $db->prepare("INSERT INTO customers (name, email, phone) VALUES (:name, :email, :phone)");
                    $ins_c->execute([':name' => $name, ':email' => $email, ':phone' => $phone]);
                    $customer_id = (int)$db->lastInsertId();
                }
            }
            
            // Calculate totals using shared engine
            require_once __DIR__ . '/includes/order_calculator.php';
            $calc = calculate_order_totals($cart_items, $order_type, $coupon_submitted);
            
            // Check for delivery limit error directly
            if ($calc['delivery_limit_error']) {
                throw new Exception("Minimum order amount for delivery is " . format_price($calc['minimum_delivery_order']) . ". Your subtotal is " . format_price($calc['subtotal']) . ".");
            }
            
            $subtotal = $calc['subtotal'];
            $discount_amount = $calc['discount_amount'];
            $applied_promotion = $calc['applied_promotion'];
            $delivery_charge = $calc['delivery_charge'];
            $tax_amount = $calc['tax_amount'];
            $total_amount = $calc['total_amount'];
            $verified_items = $calc['verified_items'];
            
            // Generate temporary placeholder order number
            $temp_order_number = 'TEMP-' . mt_rand(10000, 99999) . '-' . time();
            
            $full_address = ($order_type === 'delivery') ? "{$address}, {$city} - {$zip}" : null;
            $final_payment_method = ($order_type === 'delivery') ? 'Cash on Delivery' : 'Pay at Café';
            $promo_id_ref = $applied_promotion ? (int)$applied_promotion['id'] : null;
            $promo_code_ref = $applied_promotion ? $applied_promotion['coupon_code'] : null;
            
            // Step D: Insert Order Record
            $ins_order = $db->prepare("INSERT INTO orders 
                (order_number, customer_id, order_type, subtotal, discount_amount, delivery_charge, tax_amount, total_amount, promotion_id, coupon_code, payment_method, payment_status, order_status, delivery_address, notes) 
                VALUES 
                (:order_number, :customer_id, :order_type, :subtotal, :discount_amount, :delivery_charge, :tax_amount, :total_amount, :promotion_id, :coupon_code, :payment_method, 'pending', 'pending', :delivery_address, :notes)");
                
            $ins_order->execute([
                ':order_number' => $temp_order_number,
                ':customer_id' => $customer_id,
                ':order_type' => $order_type,
                ':subtotal' => $subtotal,
                ':discount_amount' => $discount_amount,
                ':delivery_charge' => $delivery_charge,
                ':tax_amount' => $tax_amount,
                ':total_amount' => $total_amount,
                ':promotion_id' => $promo_id_ref,
                ':coupon_code' => $promo_code_ref,
                ':payment_method' => $final_payment_method,
                ':delivery_address' => $full_address,
                ':notes' => $notes
            ]);
            
            $order_id = (int)$db->lastInsertId();
            
            // Generate real human-friendly sequential order number (e.g. MM-2026-000001)
            $order_number = 'MM-' . date('Y') . '-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
            
            // Update order record with true order number
            $upd_ord_stmt = $db->prepare("UPDATE orders SET order_number = :num WHERE id = :id");
            $upd_ord_stmt->execute([':num' => $order_number, ':id' => $order_id]);
            
            // Step E: Insert Order Items Records
            $ins_item = $db->prepare("INSERT INTO order_items 
                (order_id, menu_item_id, item_name, unit_price, quantity, discount_amount, subtotal) 
                VALUES 
                (:order_id, :menu_item_id, :item_name, :unit_price, :quantity, :discount_amount, :subtotal)");
                
            foreach ($verified_items as $v_item) {
                // Determine item-level discount allocation proportionally (for reports)
                $item_discount = 0.00;
                if ($discount_amount > 0 && $subtotal > 0) {
                    $item_ratio = $v_item['subtotal'] / $subtotal;
                    $item_discount = round($discount_amount * $item_ratio, 2);
                }
                
                $ins_item->execute([
                    ':order_id' => $order_id,
                    ':menu_item_id' => $v_item['menu_item_id'],
                    ':item_name' => $v_item['item_name'],
                    ':unit_price' => $v_item['unit_price'],
                    ':quantity' => $v_item['quantity'],
                    ':discount_amount' => $item_discount,
                    ':subtotal' => $v_item['subtotal'] - $item_discount
                ]);
            }
            
            // Step F: Write to Promotion Usage History Log and increment counter
            if ($applied_promotion) {
                $ins_usage = $db->prepare("INSERT INTO promotion_usage 
                    (promotion_id, customer_id, order_id, coupon_code, discount_amount) 
                    VALUES 
                    (:promotion_id, :customer_id, :order_id, :coupon_code, :discount_amount)");
                $ins_usage->execute([
                    ':promotion_id' => $promo_id_ref,
                    ':customer_id' => isset($_SESSION['customer_logged_in']) ? $customer_id : null,
                    ':order_id' => $order_id,
                    ':coupon_code' => $promo_code_ref,
                    ':discount_amount' => $discount_amount
                ]);
                
                // Increment promotion usage count in promotions table
                $upd_promo = $db->prepare("UPDATE promotions SET usage_count = usage_count + 1 WHERE id = :id");
                $upd_promo->execute([':id' => $promo_id_ref]);
            }
            
            $db->commit();
            
            // Save success parameters in session
            $_SESSION['last_placed_order_number'] = $order_number;
            header("Location: order-success.php?order_number=" . urlencode($order_number));
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Checkout Placement Transaction Failed: " . $e->getMessage());
            $errors[] = $e->getMessage();
        }
    }
}
?>

<section class="section-padding py-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <h1 class="display-4 display-font mb-2">Checkout</h1>
        <p class="text-muted mb-0">Review your final totals, apply coupons, and confirm your fresh items.</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo escape($err); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form action="checkout.php" method="POST" id="checkout-form">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="cart_data" id="cart_data_input">
            
            <div class="row g-5">
                <!-- Left: Contact info form -->
                <div class="col-lg-7">
                    <div class="card p-4 p-md-5 border shadow-sm bg-white" style="border-radius: var(--border-radius-md);">
                        <h3 class="display-font h3 mb-4 text-dark" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 12px;">Contact Information</h3>
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Full Name" value="<?php echo isset($_POST['name']) ? escape($_POST['name']) : escape($pre_name); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required placeholder="e.g. +91 98765 43210" value="<?php echo isset($_POST['phone']) ? escape($_POST['phone']) : escape($pre_phone); ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : escape($pre_email); ?>">
                            </div>
                        </div>
                        
                        <h3 class="display-font h3 mt-5 mb-4 text-dark" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 12px;">Fulfillment Option</h3>
                        
                        <!-- Order type radio checks -->
                        <div class="mb-4">
                            <div class="d-flex gap-3">
                                <div class="w-50">
                                    <input type="radio" class="btn-check" name="order_type" id="checkout_pickup" value="pickup" checked autocomplete="off">
                                    <label class="btn btn-outline-sage w-100 py-3 d-flex flex-column align-items-center" for="checkout_pickup">
                                        <i class="bi bi-shop mb-1" style="font-size: 1.3rem;"></i>
                                        Self-Pickup
                                    </label>
                                </div>
                                <div class="w-50">
                                    <input type="radio" class="btn-check" name="order_type" id="checkout_delivery" value="delivery" <?php echo ($delivery_enabled !== 1) ? 'disabled' : ''; ?> autocomplete="off">
                                    <label class="btn btn-outline-sage w-100 py-3 d-flex flex-column align-items-center" for="checkout_delivery">
                                        <i class="bi bi-bicycle mb-1" style="font-size: 1.3rem;"></i>
                                        Home Delivery
                                    </label>
                                </div>
                            </div>
                            <?php if ($delivery_enabled !== 1): ?>
                                <small class="text-danger mt-1 d-block"><i class="bi bi-exclamation-triangle-fill me-1"></i> Home delivery is currently disabled by café desk.</small>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Delivery Address blocks (toggled via JS) -->
                        <div id="delivery-address-section" class="row g-3 d-none">
                            <div class="col-12">
                                <label for="delivery_address" class="form-label">Street Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="delivery_address" name="delivery_address" placeholder="Apartment, building, street address" value="<?php echo isset($_POST['delivery_address']) ? escape($_POST['delivery_address']) : escape($pre_address); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="delivery_city" class="form-label">City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="delivery_city" name="delivery_city" placeholder="City" value="<?php echo isset($_POST['delivery_city']) ? escape($_POST['delivery_city']) : escape($pre_city); ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="delivery_zip" class="form-label">ZIP / Postal Code <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="delivery_zip" name="delivery_zip" placeholder="ZIP Code" value="<?php echo isset($_POST['delivery_zip']) ? escape($_POST['delivery_zip']) : escape($pre_zip); ?>">
                            </div>
                        </div>
                        
                        <!-- Notes -->
                        <div class="mt-4">
                            <label for="notes" class="form-label">Fulfillment / Preparation Instructions (Optional)</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="e.g. Ring bell, make latte extra hot, etc."><?php echo isset($_POST['notes']) ? escape($_POST['notes']) : ''; ?></textarea>
                        </div>
                    </div>
                </div>
                
                <!-- Right: Summary Card -->
                <div class="col-lg-5">
                    <div class="cart-summary-card shadow-sm border bg-cream" id="checkout-summary-card" data-delivery-charge="<?php echo $delivery_charge_config; ?>">
                        <h3 class="display-font h3 mb-4 text-dark" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 12px;">Order Summary</h3>
                        
                        <div id="checkout-order-summary" class="mb-4">
                            <!-- Items loaded from JS -->
                        </div>
                        
                        <!-- Coupon Apply Field -->
                        <div class="mb-4 pt-3 border-top" style="border-color: #DFD7C7 !important;">
                            <label for="coupon_code" class="form-label text-dark fw-bold mb-2">Apply Coupon Code</label>
                            <div class="input-group">
                                <input type="text" class="form-control" name="coupon_code" id="coupon_code" placeholder="e.g. DIWALI20" value="<?php echo isset($_POST['coupon_code']) ? escape($_POST['coupon_code']) : ''; ?>">
                                <button type="button" class="btn btn-outline-sage fw-bold" onclick="calculateCheckoutTotal()">Apply</button>
                            </div>
                        </div>
                        
                        <hr style="border-color: #DFD7C7;">
                        
                        <!-- Calculations -->
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal</span>
                            <span class="fw-bold text-dark" id="checkout-subtotal">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 d-none" id="checkout-discount-row">
                            <span class="text-success" id="checkout-discount-label">Discount</span>
                            <span class="fw-bold text-success" id="checkout-discount-amount">-₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 d-none" id="checkout-tax-row">
                            <span class="text-muted" id="checkout-tax-label">Tax</span>
                            <span class="fw-bold text-dark" id="checkout-tax-amount">₹0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2 d-none" id="checkout-delivery-row">
                            <span class="text-muted">Delivery Charge</span>
                            <span class="fw-bold text-dark" id="checkout-delivery-charge">₹0.00</span>
                        </div>
                        <hr style="border-color: #DFD7C7;">
                        <div class="d-flex justify-content-between mb-4">
                            <span class="text-dark fw-bold" style="font-size: 1.15rem;">Total</span>
                            <span class="fw-bold text-dark display-font" style="font-size: 1.5rem;" id="checkout-total">₹0.00</span>
                        </div>
                        
                        <h4 class="display-font h4 mb-3 text-dark">Payment Option</h4>
                        <div class="card p-3 border mb-4 bg-white">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="pay_cod" value="cod" checked>
                                <label class="form-check-label fw-bold text-dark" for="pay_cod">
                                    Cash on Delivery / Pay at Café
                                </label>
                                <small class="d-block text-muted">Pay at the counter or to our delivery courier.</small>
                            </div>
                        </div>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn btn-sage py-3 fw-bold">Place Order</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
