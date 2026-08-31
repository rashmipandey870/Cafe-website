<?php
/**
 * includes/order_calculator.php
 * Centralized Financial Calculation Engine for the Café System
 * Used by both checkout order placement and API dynamic total checkers
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Calculates complete order totals including item verifications, discount codes, taxes, and shipping fees.
 * 
 * @param array $cart_items Array of items: [['id' => 1, 'quantity' => 2], ...]
 * @param string $order_type 'pickup' or 'delivery'
 * @param string $coupon_code Optional coupon string
 * @return array Calculated results or throws Exception
 */
function calculate_order_totals($cart_items, $order_type, $coupon_code = '') {
    global $settings; // settings are auto-loaded in config/config.php
    
    $db = get_db_connection();
    
    // 1. Load configuration constants
    $delivery_enabled = isset($settings['delivery_enabled']) ? (int)$settings['delivery_enabled'] : 1;
    $delivery_charge_config = isset($settings['delivery_charge']) ? (float)$settings['delivery_charge'] : 45.00;
    $free_delivery_above = isset($settings['free_delivery_above']) ? (float)$settings['free_delivery_above'] : 500.00;
    $minimum_delivery_order = isset($settings['minimum_delivery_order']) ? (float)$settings['minimum_delivery_order'] : 200.00;
    
    $tax_enabled = isset($settings['tax_enabled']) ? (int)$settings['tax_enabled'] : 1;
    $tax_rate_config = isset($settings['tax_rate']) ? (float)$settings['tax_rate'] : 5.00;
    
    // 2. Validate input parameters
    if (empty($cart_items) || !is_array($cart_items)) {
        throw new Exception("Your shopping cart is empty.");
    }
    if ($order_type !== 'pickup' && $order_type !== 'delivery') {
        throw new Exception("Invalid fulfillment option selected.");
    }
    
    // 3. Query DB and verify menu availability & prices
    $subtotal = 0.00;
    $verified_items = [];
    
    $prod_stmt = $db->prepare("SELECT id, name, price, category_id, is_available FROM menu_items WHERE id = :id LIMIT 1");
    
    foreach ($cart_items as $cart_item) {
        $item_id = isset($cart_item['id']) ? (int)$cart_item['id'] : 0;
        $quantity = isset($cart_item['quantity']) ? (int)$cart_item['quantity'] : 0;
        if ($quantity <= 0) continue;
        
        $prod_stmt->execute([':id' => $item_id]);
        $db_item = $prod_stmt->fetch();
        
        if (!$db_item) {
            throw new Exception("Menu item with ID {$item_id} does not exist.");
        }
        
        // Availability Check
        if ((int)$db_item['is_available'] !== 1) {
            throw new Exception("The item '" . $db_item['name'] . "' is currently sold out. Please remove it from your cart.");
        }
        
        $item_price = (float)$db_item['price'];
        $item_subtotal = $item_price * $quantity;
        $subtotal += $item_subtotal;
        
        $verified_items[] = [
            'menu_item_id' => $item_id,
            'category_id' => (int)$db_item['category_id'],
            'item_name' => $db_item['name'],
            'quantity' => $quantity,
            'unit_price' => $item_price,
            'subtotal' => $item_subtotal
        ];
    }
    
    if (empty($verified_items)) {
        throw new Exception("No valid items in the cart.");
    }
    
    // Verify Minimum delivery boundary
    $delivery_limit_error = false;
    if ($order_type === 'delivery') {
        if ($delivery_enabled !== 1) {
            throw new Exception("Home delivery is currently disabled. Please choose Self-Pickup.");
        }
        if ($subtotal < $minimum_delivery_order) {
            $delivery_limit_error = true;
        }
    }
    
    // 4. Promotions engine calculations
    $applied_promotion = null;
    $discount_amount = 0.00;
    $coupon_code = strtoupper(trim($coupon_code));
    
    $promo_query = "SELECT * FROM promotions 
                    WHERE is_active = 1 
                    AND start_datetime <= NOW() AND end_datetime >= NOW()";
                    
    if (!empty($coupon_code)) {
        $promo_query .= " AND coupon_code = :coupon LIMIT 1";
        $p_stmt = $db->prepare($promo_query);
        $p_stmt->execute([':coupon' => $coupon_code]);
        $applied_promotion = $p_stmt->fetch();
        
        if (!$applied_promotion) {
            throw new Exception("Invalid or expired coupon code: '{$coupon_code}'.");
        }
        
        // Usage counts limit check
        if ($applied_promotion['usage_limit'] !== null && $applied_promotion['usage_count'] >= $applied_promotion['usage_limit']) {
            throw new Exception("This coupon code has reached its usage limit.");
        }
        
        // Minimum order threshold
        if ($subtotal < (float)$applied_promotion['minimum_order_amount']) {
            throw new Exception("Coupon requires a minimum order of " . format_price($applied_promotion['minimum_order_amount']) . ".");
        }
    } else {
        // Auto-apply promotions (coupon_code IS NULL, sorted by priority)
        $promo_query .= " AND coupon_code IS NULL ORDER BY priority ASC, id DESC";
        $p_stmt = $db->query($promo_query);
        $active_promos = $p_stmt->fetchAll();
        
        foreach ($active_promos as $promo) {
            if ($subtotal >= (float)$promo['minimum_order_amount']) {
                if ($promo['usage_limit'] === null || $promo['usage_count'] < $promo['usage_limit']) {
                    $applied_promotion = $promo;
                    break; // Pick the highest priority active promo (no stacking)
                }
            }
        }
    }
    
    // Apply discount calculations if promotion is resolved
    if ($applied_promotion) {
        $promo_id = (int)$applied_promotion['id'];
        
        // Load target scope restrictions
        $cat_stmt = $db->prepare("SELECT category_id FROM promotion_categories WHERE promotion_id = :pid");
        $cat_stmt->execute([':pid' => $promo_id]);
        $restricted_cats = $cat_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $prod_stmt = $db->prepare("SELECT menu_item_id FROM promotion_products WHERE promotion_id = :pid");
        $prod_stmt->execute([':pid' => $promo_id]);
        $restricted_prods = $prod_stmt->fetchAll(PDO::FETCH_COLUMN);
        
        $eligible_subtotal = 0.00;
        foreach ($verified_items as $v_item) {
            $item_eligible = true;
            if (!empty($restricted_cats) && !in_array($v_item['category_id'], $restricted_cats)) {
                $item_eligible = false;
            }
            if (!empty($restricted_prods) && !in_array($v_item['menu_item_id'], $restricted_prods)) {
                $item_eligible = false;
            }
            
            if ($item_eligible) {
                $eligible_subtotal += $v_item['subtotal'];
            }
        }
        
        if ($eligible_subtotal > 0) {
            if ($applied_promotion['promotion_type'] === 'percentage') {
                $discount_amount = ($eligible_subtotal * ((float)$applied_promotion['discount_value'] / 100));
            } else { // fixed
                $discount_amount = (float)$applied_promotion['discount_value'];
            }
            
            // Maximum discount cap limit
            $max_cap = (float)$applied_promotion['maximum_discount_amount'];
            if ($discount_amount > $max_cap) {
                $discount_amount = $max_cap;
            }
            
            // Limit discount to subtotal
            if ($discount_amount > $subtotal) {
                $discount_amount = $subtotal;
            }
        } else {
            if (!empty($coupon_code)) {
                throw new Exception("This coupon code is not applicable to the items in your cart.");
            }
            $applied_promotion = null;
        }
    }
    
    // 5. Calculate Taxes (on discounted subtotal)
    $tax_amount = 0.00;
    if ($tax_enabled === 1) {
        $taxable = $subtotal - $discount_amount;
        if ($taxable < 0) $taxable = 0.00;
        $tax_amount = $taxable * ($tax_rate_config / 100);
    }
    
    // 6. Calculate Delivery Logistics
    $delivery_charge = 0.00;
    if ($order_type === 'delivery') {
        if ($subtotal >= $free_delivery_above) {
            $delivery_charge = 0.00;
        } else {
            $delivery_charge = $delivery_charge_config;
        }
    }
    
    $total_amount = $subtotal - $discount_amount + $tax_amount + $delivery_charge;
    if ($total_amount < 0) $total_amount = 0.00;
    
    return [
        'subtotal'                    => $subtotal,
        'discount_amount'             => $discount_amount,
        'applied_promotion'           => $applied_promotion,
        'delivery_charge'             => $delivery_charge,
        'tax_amount'                  => $tax_amount,
        'tax_rate'                    => $tax_rate_config,
        'total_amount'                => $total_amount,
        'delivery_limit_error'        => $delivery_limit_error,
        'minimum_delivery_order'      => $minimum_delivery_order,
        'verified_items'              => $verified_items
    ];
}
