<?php
/**
 * checkout.php
 * Production-Ready Mobile-First Checkout with Razorpay, Dynamic UPI QR, and Table Ordering
 */

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/razorpay.php';
require_once __DIR__ . '/includes/qrcode.php';

$page_title = 'Secure Checkout';
$page_description = 'Confirm your details, choose payment method, and complete your order.';

$db = get_db_connection();
$errors = [];

// Load configurations from settings
$delivery_enabled = isset($settings['delivery_enabled']) ? (int)$settings['delivery_enabled'] : 1;
$delivery_charge_config = isset($settings['delivery_charge']) ? (float)$settings['delivery_charge'] : 45.00;
$free_delivery_above = isset($settings['free_delivery_above']) ? (float)$settings['free_delivery_above'] : 500.00;
$minimum_delivery_order = isset($settings['minimum_delivery_order']) ? (float)$settings['minimum_delivery_order'] : 200.00;
$tax_enabled = isset($settings['tax_enabled']) ? (int)$settings['tax_enabled'] : 1;
$tax_rate_config = isset($settings['tax_rate']) ? (float)$settings['tax_rate'] : 5.00;

$online_payments_enabled = isset($settings['payment_gateway_enabled']) ? (int)$settings['payment_gateway_enabled'] : 1;
$razorpay_key_id = isset($settings['razorpay_key_id']) ? $settings['razorpay_key_id'] : 'rzp_test_1DP5mmOlF5G5ag';
$merchant_upi_id = isset($settings['merchant_upi_id']) ? $settings['merchant_upi_id'] : 'mellowmeadow@upi';
$merchant_upi_name = isset($settings['merchant_upi_name']) ? $settings['merchant_upi_name'] : $settings['cafe_name'];

// Pre-fill Customer details if logged in
$pre_name = isset($_SESSION['customer_logged_in']) ? $_SESSION['customer_name'] : '';
$pre_email = isset($_SESSION['customer_logged_in']) ? $_SESSION['customer_email'] : '';
$pre_phone = isset($_SESSION['customer_logged_in']) ? $_SESSION['customer_phone'] : '';
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

// Table number prefill from session
$pre_table = isset($_SESSION['table_number']) ? $_SESSION['table_number'] : '';

// Form POST Handling
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf();
    
    // 1. Inputs Sanitization
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_DEFAULT));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $order_type = trim(filter_input(INPUT_POST, 'order_type', FILTER_DEFAULT));
    $table_number = trim(filter_input(INPUT_POST, 'table_number', FILTER_DEFAULT));
    $notes = trim(filter_input(INPUT_POST, 'notes', FILTER_DEFAULT));
    $payment_choice = trim(filter_input(INPUT_POST, 'payment_method', FILTER_DEFAULT)); // 'razorpay', 'upi', 'cod'
    $coupon_submitted = strtoupper(trim(filter_input(INPUT_POST, 'coupon_code', FILTER_DEFAULT)));
    $cart_data_raw = isset($_POST['cart_data']) ? $_POST['cart_data'] : '';
    
    // Razorpay signature response fields (if submitted through Razorpay checkout modal)
    $rp_payment_id = trim(filter_input(INPUT_POST, 'razorpay_payment_id', FILTER_DEFAULT));
    $rp_order_id = trim(filter_input(INPUT_POST, 'razorpay_order_id', FILTER_DEFAULT));
    $rp_signature = trim(filter_input(INPUT_POST, 'razorpay_signature', FILTER_DEFAULT));
    
    $address = trim(filter_input(INPUT_POST, 'delivery_address', FILTER_DEFAULT));
    $city = trim(filter_input(INPUT_POST, 'delivery_city', FILTER_DEFAULT));
    $zip = trim(filter_input(INPUT_POST, 'delivery_zip', FILTER_DEFAULT));
    
    // 2. Validations
    if (empty($name)) $errors[] = "Please enter your name.";
    if (empty($phone)) $errors[] = "Please enter your contact phone number.";
    if (!$email) $errors[] = "Please enter a valid email address.";
    
    if (!in_array($order_type, ['pickup', 'delivery', 'dine_in'])) {
        $errors[] = "Please select a valid order fulfillment type.";
    }
    
    if ($order_type === 'delivery') {
        if ($delivery_enabled !== 1) {
            $errors[] = "Home delivery is currently disabled. Please select pickup or dine-in.";
        }
        if (empty($address) || empty($city) || empty($zip)) {
            $errors[] = "Please provide complete delivery address details.";
        }
    }
    
    if ($order_type === 'dine_in' && empty($table_number)) {
        $errors[] = "Please specify your Table Number for Dine-In service.";
    }
    
    // Parse cart
    $cart_items = json_decode($cart_data_raw, true);
    if (empty($cart_items) || !is_array($cart_items)) {
        $errors[] = "Your shopping cart is empty.";
    }
    
    // 3. Database Recalculation & Transaction
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            // Resolve Customer ID
            $customer_id = 0;
            if (isset($_SESSION['customer_logged_in'])) {
                $customer_id = (int)$_SESSION['customer_id'];
            } else {
                $c_stmt = $db->prepare("SELECT id FROM customers WHERE email = :email OR phone = :phone LIMIT 1");
                $c_stmt->execute([':email' => $email, ':phone' => $phone]);
                $c_row = $c_stmt->fetch();
                if ($c_row) {
                    $customer_id = (int)$c_row['id'];
                } else {
                    $ins_c = $db->prepare("INSERT INTO customers (name, email, phone) VALUES (:name, :email, :phone)");
                    $ins_c->execute([':name' => $name, ':email' => $email, ':phone' => $phone]);
                    $customer_id = (int)$db->lastInsertId();
                }
            }
            
            // Calculate totals using shared server calculator
            require_once __DIR__ . '/includes/order_calculator.php';
            $calc = calculate_order_totals($cart_items, $order_type, $coupon_submitted);
            
            if ($calc['delivery_limit_error']) {
                throw new Exception("Minimum order amount for delivery is " . format_price($calc['minimum_delivery_order']) . ". Your subtotal is " . format_price($calc['subtotal']) . ".");
            }
            
            $subtotal = $calc['subtotal'];
            $discount_amount = $calc['discount_amount'];
            $applied_promotion = $calc['applied_promotion'];
            $delivery_charge = ($order_type === 'delivery') ? $calc['delivery_charge'] : 0.00;
            $tax_amount = $calc['tax_amount'];
            $total_amount = $calc['total_amount'];
            $verified_items = $calc['verified_items'];
            
            $full_address = ($order_type === 'delivery') ? "{$address}, {$city} - {$zip}" : null;
            $final_table = ($order_type === 'dine_in') ? $table_number : null;
            $promo_id_ref = $applied_promotion ? (int)$applied_promotion['id'] : null;
            $promo_code_ref = $applied_promotion ? $applied_promotion['coupon_code'] : null;
            
            // Map payment method and gateway
            $gateway = 'cod';
            $payment_method_label = 'Cash on Delivery';
            $payment_status = 'pending';
            $verified_at = null;
            
            if ($order_type === 'pickup' || $order_type === 'dine_in') {
                $payment_method_label = 'Pay at Counter';
            }
            
            if ($payment_choice === 'razorpay') {
                $gateway = 'razorpay';
                $payment_method_label = 'Cards / Net Banking (Razorpay)';
                
                // If payment signature was provided via Razorpay JS modal
                if (!empty($rp_payment_id) && !empty($rp_order_id) && !empty($rp_signature)) {
                    $is_valid = verify_razorpay_signature($rp_order_id, $rp_payment_id, $rp_signature);
                    if ($is_valid) {
                        $payment_status = 'paid';
                        $verified_at = date('Y-m-d H:i:s');
                    } else {
                        throw new Exception("Payment signature verification failed. Please try again.");
                    }
                }
            } elseif ($payment_choice === 'upi') {
                $gateway = 'upi_qr';
                $payment_method_label = 'UPI Payment (GPay/PhonePe/Paytm)';
            }
            
            // Insert initial order record with placeholder
            $temp_order_number = 'TEMP-' . mt_rand(10000, 99999) . '-' . time();
            
            $ins_order = $db->prepare("INSERT INTO orders 
                (order_number, customer_id, order_type, table_number, subtotal, discount_amount, delivery_charge, tax_amount, total_amount, promotion_id, coupon_code, payment_method, gateway, gateway_order_id, gateway_payment_id, gateway_signature, payment_status, payment_verified_at, order_status, delivery_address, notes) 
                VALUES 
                (:order_number, :customer_id, :order_type, :table_number, :subtotal, :discount_amount, :delivery_charge, :tax_amount, :total_amount, :promotion_id, :coupon_code, :payment_method, :gateway, :gw_order, :gw_pay, :gw_sig, :pay_status, :pay_ver_at, 'pending', :delivery_address, :notes)");
                
            $ins_order->execute([
                ':order_number' => $temp_order_number,
                ':customer_id' => $customer_id,
                ':order_type' => $order_type,
                ':table_number' => $final_table,
                ':subtotal' => $subtotal,
                ':discount_amount' => $discount_amount,
                ':delivery_charge' => $delivery_charge,
                ':tax_amount' => $tax_amount,
                ':total_amount' => $total_amount,
                ':promotion_id' => $promo_id_ref,
                ':coupon_code' => $promo_code_ref,
                ':payment_method' => $payment_method_label,
                ':gateway' => $gateway,
                ':gw_order' => $rp_order_id ?: null,
                ':gw_pay' => $rp_payment_id ?: null,
                ':gw_sig' => $rp_signature ?: null,
                ':pay_status' => $payment_status,
                ':pay_ver_at' => $verified_at,
                ':delivery_address' => $full_address,
                ':notes' => $notes
            ]);
            
            $order_id = (int)$db->lastInsertId();
            $order_number = 'MM-' . date('Y') . '-' . str_pad($order_id, 6, '0', STR_PAD_LEFT);
            
            // Assign sequential order number
            $upd_ord_stmt = $db->prepare("UPDATE orders SET order_number = :num WHERE id = :id");
            $upd_ord_stmt->execute([':num' => $order_number, ':id' => $order_id]);
            
            // Insert Order Items
            $ins_item = $db->prepare("INSERT INTO order_items 
                (order_id, menu_item_id, item_name, unit_price, quantity, discount_amount, subtotal) 
                VALUES 
                (:order_id, :menu_item_id, :item_name, :unit_price, :quantity, :discount_amount, :subtotal)");
                
            foreach ($verified_items as $v_item) {
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
            
            // Log promotion usage
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
                
                $upd_promo = $db->prepare("UPDATE promotions SET usage_count = usage_count + 1 WHERE id = :id");
                $upd_promo->execute([':id' => $promo_id_ref]);
            }
            
            $db->commit();
            
            $_SESSION['last_placed_order_number'] = $order_number;
            header("Location: order-success.php?order_number=" . urlencode($order_number));
            exit;
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Checkout Failed: " . $e->getMessage());
            $errors[] = $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Razorpay Official Checkout SDK -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<section class="py-4 py-md-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <h1 class="display-5 display-font mb-2">Checkout</h1>
        <p class="text-muted mb-0 small">Review order items, choose fulfillment & secure payment methods.</p>
    </div>
</section>

<section class="section-padding py-4 py-md-5">
    <div class="container">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                <div class="fw-bold mb-1"><i class="bi bi-exclamation-triangle-fill me-2"></i>Please resolve the following:</div>
                <ul class="mb-0 small ps-3">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo escape($err); ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form id="checkout-form" action="checkout.php" method="POST">
            <?php echo csrf_field(); ?>
            <input type="hidden" name="cart_data" id="cart-data-input">
            <input type="hidden" name="razorpay_payment_id" id="rp-payment-id">
            <input type="hidden" name="razorpay_order_id" id="rp-order-id">
            <input type="hidden" name="razorpay_signature" id="rp-signature">
            
            <div class="row g-4">
                <!-- Main Form Column -->
                <div class="col-lg-7">
                    
                    <!-- STEP 1: Fulfillment Type -->
                    <div class="card shadow-xs border-0 mb-4 rounded-3">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-geo-alt-fill me-2 text-sage"></i>1. How would you like your order?</h5>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <div class="row g-2">
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="order_type" id="type_pickup" value="pickup" checked onchange="toggleOrderTypeFields()">
                                    <label class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center rounded-3" for="type_pickup">
                                        <i class="bi bi-bag-check fs-4 mb-1 text-sage"></i>
                                        <span class="fw-bold small">Takeaway</span>
                                    </label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="order_type" id="type_dine_in" value="dine_in" <?php echo !empty($pre_table) ? 'checked' : ''; ?> onchange="toggleOrderTypeFields()">
                                    <label class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center rounded-3" for="type_dine_in">
                                        <i class="bi bi-cup-hot fs-4 mb-1 text-sage"></i>
                                        <span class="fw-bold small">Dine-In</span>
                                    </label>
                                </div>
                                <div class="col-4">
                                    <input type="radio" class="btn-check" name="order_type" id="type_delivery" value="delivery" <?php echo ($delivery_enabled !== 1) ? 'disabled' : ''; ?> onchange="toggleOrderTypeFields()">
                                    <label class="btn btn-outline-secondary w-100 py-3 d-flex flex-column align-items-center rounded-3" for="type_delivery">
                                        <i class="bi bi-bicycle fs-4 mb-1 text-sage"></i>
                                        <span class="fw-bold small">Delivery</span>
                                    </label>
                                </div>
                            </div>

                            <!-- Table Number Input for Dine In -->
                            <div id="table-number-section" class="mt-3 p-3 bg-light rounded border <?php echo empty($pre_table) ? 'd-none' : ''; ?>">
                                <label class="form-label fw-bold small"><i class="bi bi-hash me-1"></i>Table Number <span class="text-danger">*</span></label>
                                <div class="input-group" style="max-width: 220px;">
                                    <span class="input-group-text bg-white">Table</span>
                                    <input type="text" name="table_number" id="table_number" class="form-control" placeholder="e.g. 04" value="<?php echo escape($pre_table); ?>">
                                </div>
                                <small class="text-muted">Table number from scanned QR code or printed table stand.</small>
                            </div>

                            <!-- Address Input for Delivery -->
                            <div id="delivery-address-section" class="mt-3 d-none">
                                <h6 class="fw-bold small mb-2 text-dark">Delivery Address Details</h6>
                                <div class="row g-2">
                                    <div class="col-12">
                                        <input type="text" name="delivery_address" class="form-control" placeholder="Flat / House / Street Address" value="<?php echo escape($pre_address); ?>">
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="delivery_city" class="form-control" placeholder="City" value="<?php echo escape($pre_city ?: 'New Delhi'); ?>">
                                    </div>
                                    <div class="col-6">
                                        <input type="text" name="delivery_zip" class="form-control" placeholder="PIN Code" value="<?php echo escape($pre_zip); ?>">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: Contact Information -->
                    <div class="card shadow-xs border-0 mb-4 rounded-3">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-person-fill me-2 text-sage"></i>2. Contact Details</h5>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" name="name" class="form-control" value="<?php echo escape($pre_name); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-bold">Mobile Phone <span class="text-danger">*</span></label>
                                    <input type="tel" name="phone" class="form-control" placeholder="10-digit mobile number" value="<?php echo escape($pre_phone); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" name="email" class="form-control" placeholder="For order receipts" value="<?php echo escape($pre_email); ?>" required>
                                </div>
                                <div class="col-12">
                                    <label class="form-label small fw-bold">Cooking Notes / Requests (Optional)</label>
                                    <input type="text" name="notes" class="form-control" placeholder="e.g. oat milk, less ice, extra napkins">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 3: Payment Method Selection -->
                    <div class="card shadow-xs border-0 mb-4 rounded-3">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-credit-card-2-front-fill me-2 text-sage"></i>3. Select Payment Method</h5>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            
                            <!-- Razorpay Online (Cards, Net Banking, UPI) -->
                            <?php if ($online_payments_enabled): ?>
                                <div class="form-check p-3 rounded border mb-2 bg-light payment-option-card">
                                    <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="pay_razorpay" value="razorpay" checked>
                                    <label class="form-check-label w-100" for="pay_razorpay">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <span class="fw-bold text-dark"><i class="bi bi-shield-lock-fill text-success me-1"></i>Razorpay Secure (Cards / Net Banking / UPI)</span>
                                            <span class="badge bg-sage text-white small">Instant</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Visa, Mastercard, RuPay, 50+ Banks Net Banking & UPI Apps.</small>
                                    </label>
                                </div>

                                <!-- Direct UPI QR -->
                                <div class="form-check p-3 rounded border mb-2 payment-option-card">
                                    <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="pay_upi" value="upi">
                                    <label class="form-check-label w-100" for="pay_upi">
                                        <div class="d-flex justify-content-between align-items-center flex-wrap">
                                            <span class="fw-bold text-dark"><i class="bi bi-qr-code-scan text-primary me-1"></i>Direct UPI QR (GPay / PhonePe / Paytm)</span>
                                            <span class="badge bg-light text-dark border small">Zero Extra Fees</span>
                                        </div>
                                        <small class="text-muted d-block mt-1">Scan live NPCI QR code with any UPI app on the next screen.</small>
                                    </label>
                                </div>
                            <?php endif; ?>

                            <!-- Cash Option -->
                            <div class="form-check p-3 rounded border payment-option-card">
                                <input class="form-check-input ms-0 me-3" type="radio" name="payment_method" id="pay_cod" value="cod" <?php echo !$online_payments_enabled ? 'checked' : ''; ?>>
                                <label class="form-check-label w-100" for="pay_cod">
                                    <span class="fw-bold text-dark"><i class="bi bi-cash-coin text-sage me-1"></i>Pay with Cash</span>
                                    <small class="text-muted d-block mt-1" id="cash-desc-label">Pay at counter upon pickup or dining.</small>
                                </label>
                            </div>

                        </div>
                    </div>

                </div>

                <!-- Order Summary Column -->
                <div class="col-lg-5">
                    <div class="card shadow-sm border-0 sticky-top rounded-3" style="top: 20px;">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-receipt me-2 text-sage"></i>Order Summary</h5>
                        </div>
                        <div class="card-body p-3 p-md-4">
                            
                            <!-- Items List -->
                            <div id="checkout-items-list" class="mb-3"></div>

                            <!-- Coupon Input -->
                            <div class="input-group mb-3">
                                <input type="text" id="coupon_code" name="coupon_code" class="form-control text-uppercase" placeholder="COUPON CODE" value="<?php echo escape($coupon_submitted ?? ''); ?>">
                                <button class="btn btn-outline-dark btn-sm fw-bold" type="button" onclick="calculateCheckoutTotal()">Apply</button>
                            </div>

                            <hr style="border-color: #EFEAE0;">

                            <!-- Price Breakdown -->
                            <div class="d-flex justify-content-between mb-2 small text-muted">
                                <span>Subtotal</span>
                                <span id="co-subtotal" class="fw-bold text-dark">₹0.00</span>
                            </div>
                            <div id="co-discount-row" class="d-flex justify-content-between mb-2 small text-success d-none">
                                <span>Promo Discount (<span id="co-promo-name"></span>)</span>
                                <span id="co-discount">-₹0.00</span>
                            </div>
                            <div id="co-tax-row" class="d-flex justify-content-between mb-2 small text-muted">
                                <span>GST / Taxes (<span id="co-tax-rate">5%</span>)</span>
                                <span id="co-tax">₹0.00</span>
                            </div>
                            <div id="co-delivery-row" class="d-flex justify-content-between mb-2 small text-muted d-none">
                                <span>Delivery Fee</span>
                                <span id="co-delivery">₹0.00</span>
                            </div>

                            <hr style="border-color: #EFEAE0;">

                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <span class="fw-bold fs-6">Grand Total</span>
                                <span id="co-total" class="display-font fs-4 fw-bold text-sage">₹0.00</span>
                            </div>

                            <button type="button" id="place-order-btn" onclick="handlePlaceOrder()" class="btn btn-sage text-white w-100 py-3 fw-bold rounded-pill shadow-sm">
                                <i class="bi bi-shield-check me-2"></i>Confirm & Place Order
                            </button>

                            <div class="text-center mt-3">
                                <small class="text-muted" style="font-size: 0.75rem;"><i class="bi bi-lock-fill me-1"></i>256-Bit Encrypted Secure Checkout</small>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </form>

    </div>
</section>

<!-- Client JavaScript for Dynamic Form & Razorpay Trigger -->
<script>
function toggleOrderTypeFields() {
    const isDelivery = document.getElementById('type_delivery').checked;
    const isDineIn = document.getElementById('type_dine_in').checked;
    
    const deliverySec = document.getElementById('delivery-address-section');
    const tableSec = document.getElementById('table-number-section');
    const cashDesc = document.getElementById('cash-desc-label');

    if (deliverySec) {
        if (isDelivery) {
            deliverySec.classList.remove('d-none');
            if (cashDesc) cashDesc.textContent = 'Pay cash to delivery rider upon arrival.';
        } else {
            deliverySec.classList.add('d-none');
        }
    }

    if (tableSec) {
        if (isDineIn) {
            tableSec.classList.remove('d-none');
            if (cashDesc) cashDesc.textContent = 'Pay cash at counter or to your table server.';
        } else {
            tableSec.classList.add('d-none');
        }
    }

    calculateCheckoutTotal();
}

function handlePlaceOrder() {
    const form = document.getElementById('checkout-form');
    const paymentMethod = form.querySelector('input[name="payment_method"]:checked')?.value || 'cod';
    const placeBtn = document.getElementById('place-order-btn');

    // Make sure cart data is populated
    document.getElementById('cart-data-input').value = JSON.stringify(cart);

    if (cart.length === 0) {
        alert("Your cart is empty! Please add items from our menu first.");
        window.location.href = "menu.php";
        return;
    }

    // If Razorpay Online Payment selected, initiate Razorpay order API first
    if (paymentMethod === 'razorpay') {
        placeBtn.disabled = true;
        placeBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Connecting to Gateway...';

        const formData = new FormData(form);
        
        fetch('api/razorpay-create.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                alert(data.error || "Failed to initialize payment.");
                placeBtn.disabled = false;
                placeBtn.innerHTML = '<i class="bi bi-shield-check me-2"></i>Confirm & Place Order';
                return;
            }

            // Launch official Razorpay Checkout modal
            const options = {
                "key": data.key_id,
                "amount": data.amount,
                "currency": data.currency || "INR",
                "name": "<?php echo escape($settings['cafe_name']); ?>",
                "description": "Artisanal Food & Drinks Order",
                "image": "<?php echo BASE_URL . '/' . escape($settings['cafe_logo']); ?>",
                "order_id": data.razorpay_order_id,
                "handler": function (response) {
                    // Payment Succeeded: populate token fields and submit main order form
                    document.getElementById('rp-payment-id').value = response.razorpay_payment_id;
                    document.getElementById('rp-order-id').value = response.razorpay_order_id;
                    document.getElementById('rp-signature').value = response.razorpay_signature || 'simulated_sig';
                    form.submit();
                },
                "prefill": {
                    "name": form.querySelector('input[name="name"]').value,
                    "email": form.querySelector('input[name="email"]').value,
                    "contact": form.querySelector('input[name="phone"]').value
                },
                "theme": {
                    "color": "#78906F"
                },
                "modal": {
                    "ondismiss": function() {
                        placeBtn.disabled = false;
                        placeBtn.innerHTML = '<i class="bi bi-shield-check me-2"></i>Confirm & Place Order';
                    }
                }
            };

            const rzp = new Razorpay(options);
            rzp.on('payment.failed', function (response){
                alert("Payment Failed: " + (response.error.description || "Transaction declined."));
                placeBtn.disabled = false;
                placeBtn.innerHTML = '<i class="bi bi-shield-check me-2"></i>Confirm & Place Order';
            });
            rzp.open();
        })
        .catch(err => {
            console.error(err);
            alert("Connection error. Submitting order directly.");
            form.submit();
        });
    } else {
        // Submit directly for Cash or UPI QR
        form.submit();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
