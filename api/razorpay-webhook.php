<?php
/**
 * api/razorpay-webhook.php
 * Secure Idempotent Razorpay Webhook Handler
 */

header('Content-Type: application/json');

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

$webhook_secret = defined('RAZORPAY_WEBHOOK_SECRET') ? RAZORPAY_WEBHOOK_SECRET : '';
$raw_payload = file_get_contents('php://input');
$signature_header = isset($_SERVER['HTTP_X_RAZORPAY_SIGNATURE']) ? $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] : '';

if (!empty($webhook_secret)) {
    $expected_signature = hash_hmac('sha256', $raw_payload, $webhook_secret);
    if (!hash_equals($expected_signature, $signature_header)) {
        http_response_code(400);
        echo json_encode(['status' => 'invalid_signature']);
        exit;
    }
}

$event_data = json_decode($raw_payload, true);
if (!empty($event_data['event']) && in_array($event_data['event'], ['payment.captured', 'order.paid'])) {
    $payment = $event_data['payload']['payment']['entity'] ?? [];
    $order_id = $payment['order_id'] ?? '';
    $payment_id = $payment['id'] ?? '';
    $notes = $payment['notes'] ?? [];
    $order_number = $notes['order_number'] ?? '';
    
    if (!empty($order_number) || !empty($order_id)) {
        try {
            $db = get_db_connection();
            // Idempotent update
            $stmt = $db->prepare("UPDATE orders 
                SET payment_status = 'paid', 
                    gateway = 'razorpay',
                    gateway_payment_id = :pay_id,
                    payment_verified_at = CURRENT_TIMESTAMP
                WHERE (order_number = :ord_num OR gateway_order_id = :ord_id) AND payment_status != 'paid'");
            $stmt->execute([
                ':pay_id' => $payment_id,
                ':ord_num' => $order_number,
                ':ord_id' => $order_id
            ]);
        } catch (PDOException $e) {
            error_log("Webhook Update DB Error: " . $e->getMessage());
        }
    }
}

http_response_code(200);
echo json_encode(['status' => 'processed']);
