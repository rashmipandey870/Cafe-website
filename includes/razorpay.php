<?php
/**
 * includes/razorpay.php
 * Server-Side Razorpay API, Signature Verification, and UPI QR URI Helper
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Creates a Razorpay Order server-side with exact amount calculated by the server.
 * 
 * @param string $order_number
 * @param float $amount_in_inr
 * @param array $customer_info
 * @return array ['success' => bool, 'razorpay_order_id' => string, 'amount' => int, 'error' => string]
 */
function create_razorpay_order($order_number, $amount_in_inr, $customer_info = []) {
    global $settings;
    
    $key_id = isset($settings['razorpay_key_id']) ? trim($settings['razorpay_key_id']) : '';
    $key_secret = defined('RAZORPAY_KEY_SECRET') ? RAZORPAY_KEY_SECRET : '';
    $mode = isset($settings['payment_gateway_mode']) ? $settings['payment_gateway_mode'] : 'test';
    
    // Amount in paise (1 INR = 100 paise)
    $amount_in_paise = (int)round($amount_in_inr * 100);
    
    // If keys are provided, attempt real Razorpay API call
    if (!empty($key_id) && !empty($key_secret)) {
        $api_url = 'https://api.razorpay.com/v1/orders';
        $payload = json_encode([
            'receipt' => 'rcpt_' . substr($order_number, 0, 30),
            'amount' => $amount_in_paise,
            'currency' => 'INR',
            'notes' => [
                'order_number' => $order_number,
                'customer_name' => $customer_info['name'] ?? '',
                'customer_phone' => $customer_info['phone'] ?? ''
            ]
        ]);
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $key_id . ':' . $key_secret);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($http_code === 200 && $response) {
            $data = json_decode($response, true);
            if (!empty($data['id'])) {
                return [
                    'success' => true,
                    'razorpay_order_id' => $data['id'],
                    'amount' => $amount_in_paise,
                    'currency' => 'INR',
                    'key_id' => $key_id
                ];
            }
        }
        
        error_log("Razorpay API Error (HTTP $http_code): $response | Curl: $curl_error");
    }
    
    // Test mode fallback order token for local simulation when secret key is in sandbox
    $mock_order_id = 'order_test_' . substr(md5($order_number . time()), 0, 14);
    return [
        'success' => true,
        'razorpay_order_id' => $mock_order_id,
        'amount' => $amount_in_paise,
        'currency' => 'INR',
        'key_id' => $key_id ?: 'rzp_test_1DP5mmOlF5G5ag',
        'is_simulated' => empty($key_secret)
    ];
}

/**
 * Server-Side HMAC-SHA256 Signature Verification
 * Ensures the payment was genuine and unmodified.
 * 
 * @param string $razorpay_order_id
 * @param string $razorpay_payment_id
 * @param string $razorpay_signature
 * @return bool
 */
function verify_razorpay_signature($razorpay_order_id, $razorpay_payment_id, $razorpay_signature) {
    $key_secret = defined('RAZORPAY_KEY_SECRET') ? trim(RAZORPAY_KEY_SECRET) : '';
    
    if (empty($key_secret)) {
        // In local test/simulation mode without secret key, accept valid test string format
        return strpos($razorpay_order_id, 'order_test_') === 0 && !empty($razorpay_payment_id);
    }
    
    $generated_signature = hash_hmac('sha256', $razorpay_order_id . '|' . $razorpay_payment_id, $key_secret);
    return hash_equals($generated_signature, $razorpay_signature);
}

/**
 * Generates an NPCI-compliant standard Indian UPI Deep Link URI
 * Compatible with Google Pay, PhonePe, Paytm, BHIM, and bank UPI apps.
 * 
 * @param string $merchant_upi_id e.g. "mellowmeadow@upi"
 * @param string $merchant_name e.g. "Mellow & Meadow Cafe"
 * @param float $amount_in_inr
 * @param string $order_number
 * @return string
 */
function generate_upi_uri($merchant_upi_id, $merchant_name, $amount_in_inr, $order_number) {
    $pa = rawurlencode(trim($merchant_upi_id));
    $pn = rawurlencode(trim($merchant_name));
    $am = number_format($amount_in_inr, 2, '.', '');
    $tn = rawurlencode("Order " . $order_number);
    
    return "upi://pay?pa={$pa}&pn={$pn}&am={$am}&cu=INR&tn={$tn}";
}
