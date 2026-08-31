<?php
/**
 * api/razorpay-create.php
 * Secure Server-Side Razorpay Order Creation Endpoint
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../includes/csrf.php';
    require_once __DIR__ . '/../includes/order_calculator.php';
    require_once __DIR__ . '/../includes/razorpay.php';
    
    // Read input parameters
    $cart_raw = isset($_POST['cart_data']) ? $_POST['cart_data'] : '';
    $order_type = isset($_POST['order_type']) ? trim($_POST['order_type']) : 'pickup';
    $coupon_code = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : '';
    $name = isset($_POST['name']) ? trim($_POST['name']) : '';
    $phone = isset($_POST['phone']) ? trim($_POST['phone']) : '';
    
    $cart_items = json_decode($cart_raw, true);
    if (empty($cart_items) || !is_array($cart_items)) {
        echo json_encode(['success' => false, 'error' => 'Cart is empty.']);
        exit;
    }
    
    // Calculate final server total
    $calc = calculate_order_totals($cart_items, $order_type, $coupon_code);
    if ($calc['delivery_limit_error']) {
        echo json_encode(['success' => false, 'error' => 'Minimum delivery order limit not met.']);
        exit;
    }
    
    $total_amount = $calc['total_amount'];
    $temp_ref = 'ORD-' . mt_rand(1000, 9999) . '-' . time();
    
    // Create Razorpay Order
    $rp_order = create_razorpay_order($temp_ref, $total_amount, [
        'name' => $name,
        'phone' => $phone
    ]);
    
    if (!$rp_order['success']) {
        echo json_encode(['success' => false, 'error' => 'Failed to initialize payment gateway.']);
        exit;
    }
    
    echo json_encode([
        'success' => true,
        'razorpay_order_id' => $rp_order['razorpay_order_id'],
        'amount' => $rp_order['amount'],
        'currency' => 'INR',
        'key_id' => $rp_order['key_id'],
        'is_simulated' => $rp_order['is_simulated'] ?? false
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
