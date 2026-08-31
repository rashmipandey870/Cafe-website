<?php
/**
 * api/calculate-total.php
 * Secure JSON API Endpoint - Performs server-side financial calculations for cart/checkout totals
 */

header('Content-Type: application/json');

try {
    require_once __DIR__ . '/../includes/order_calculator.php';
    
    // Read input parameters
    $cart_raw = isset($_POST['cart_data']) ? $_POST['cart_data'] : '';
    $order_type = isset($_POST['order_type']) ? trim($_POST['order_type']) : 'pickup';
    $coupon_code = isset($_POST['coupon_code']) ? trim($_POST['coupon_code']) : '';
    
    $cart_items = json_decode($cart_raw, true);
    
    if (empty($cart_items) || !is_array($cart_items)) {
        echo json_encode([
            'success' => false,
            'error' => 'Your shopping cart is empty.'
        ]);
        exit;
    }
    
    // Call the centralized calculator
    $calc = calculate_order_totals($cart_items, $order_type, $coupon_code);
    
    echo json_encode([
        'success'               => true,
        'subtotal'              => $calc['subtotal'],
        'discount_amount'       => $calc['discount_amount'],
        'applied_promo_name'    => $calc['applied_promotion'] ? $calc['applied_promotion']['name'] : '',
        'delivery_charge'       => $calc['delivery_charge'],
        'tax_amount'            => $calc['tax_amount'],
        'tax_rate'              => $calc['tax_rate'],
        'total_amount'          => $calc['total_amount'],
        'delivery_limit_error'  => $calc['delivery_limit_error'],
        'minimum_delivery_order'=> $calc['minimum_delivery_order']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error'   => $e->getMessage()
    ]);
}
