<?php
/**
 * cart.php
 * Shopping Cart Page - View and modify cart items before checkout
 */

$page_title = 'Your Cart';
$page_description = 'Review your Mellow & Meadow selections, modify quantities, select delivery or pickup, and proceed to secure checkout.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Get delivery charge configuration from database settings
$delivery_charge = isset($settings['delivery_charge']) ? (float)$settings['delivery_charge'] : 45.00;
?>

<!-- Cart Header -->
<section class="section-padding py-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <h1 class="display-4 display-font mb-2">Shopping Cart</h1>
        <p class="text-muted mb-0">Review your selected items and choose your pickup or delivery preference.</p>
    </div>
</section>

<!-- Cart Body -->
<section class="section-padding">
    <div class="container">
        
        <div class="row g-5">
            <!-- Left Column: Cart Items List -->
            <div class="col-lg-8">
                <!-- Outer Container with delivery charge attribute for Javascript calculations -->
                <div id="cart-items-container" data-delivery-charge="<?php echo $delivery_charge; ?>">
                    <!-- Items rendered dynamically by assets/js/cart.js -->
                    <div class="text-center py-5">
                        <div class="spinner-border text-success" role="status" style="color: var(--accent-sage) !important;">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Right Column: Cart Summary Details -->
            <div class="col-lg-4">
                <div class="cart-summary-card shadow-none">
                    <h3 class="display-font h3 mb-4 text-dark" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 15px;">Summary</h3>
                    
                    <!-- 1. Order Type Toggles (Required for Delivery Charge Calculations) -->
                    <div class="mb-4">
                        <label class="form-label d-block text-dark fw-bold mb-3">How would you like your order?</label>
                        <div class="d-flex gap-3">
                            <input type="radio" class="btn-check" name="order_type_toggle" id="type_pickup" value="pickup" checked autocomplete="off">
                            <label class="btn btn-outline-sage w-50 py-2 d-flex flex-column align-items-center" for="type_pickup">
                                <i class="bi bi-shop mb-1" style="font-size: 1.2rem;"></i>
                                Pickup
                            </label>
                            
                            <input type="radio" class="btn-check" name="order_type_toggle" id="type_delivery" value="delivery" autocomplete="off">
                            <label class="btn btn-outline-sage w-50 py-2 d-flex flex-column align-items-center" for="type_delivery">
                                <i class="bi bi-bicycle mb-1" style="font-size: 1.2rem;"></i>
                                Delivery
                            </label>
                        </div>
                    </div>
                    
                    <!-- 2. Cost Details -->
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Subtotal</span>
                        <span class="fw-bold text-dark" id="cart-subtotal">₹0.00</span>
                    </div>
                    
                    <div class="d-flex justify-content-between mb-3 d-none" id="delivery-charge-row">
                        <span class="text-muted">Delivery Charge</span>
                        <span class="fw-bold text-dark" id="cart-delivery-charge">₹0.00</span>
                    </div>
                    
                    <hr style="border-color: #DFD7C7;">
                    
                    <div class="d-flex justify-content-between mb-4">
                        <span class="text-dark fw-bold" style="font-size: 1.15rem;">Total</span>
                        <span class="fw-bold text-dark display-font" style="font-size: 1.5rem;" id="cart-total">₹0.00</span>
                    </div>
                    
                    <!-- 3. Actions -->
                    <div class="d-grid gap-2">
                        <a href="checkout.php" class="btn btn-sage py-3 fw-bold disabled" id="checkout-btn">Proceed to Checkout</a>
                        <a href="menu.php" class="btn btn-outline-sage py-3">Continue Shopping</a>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</section>

<!-- Include script bindings -->
<script>
    document.addEventListener('DOMContentLoaded', () => {
        setupOrderTypeToggles();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
