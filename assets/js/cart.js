/**
 * assets/js/cart.js
 * Client-Side Shopping Cart Management using LocalStorage
 */

// Initialize Cart Array
let cart = [];

// Load cart from LocalStorage on load
document.addEventListener('DOMContentLoaded', () => {
    loadCart();
    syncCartBadge();
    
    // If we are on the cart page, render it
    if (document.getElementById('cart-items-container')) {
        renderCartPage();
    }
    
    // If we are on the checkout page, load order data
    if (document.getElementById('checkout-form')) {
        prepareCheckout();
    }
});

// Load cart from LocalStorage
function loadCart() {
    const savedCart = localStorage.getItem('cafe_cart');
    if (savedCart) {
        try {
            cart = JSON.parse(savedCart);
        } catch (e) {
            cart = [];
            saveCart();
        }
    }
}

// Save cart to LocalStorage
function saveCart() {
    localStorage.setItem('cafe_cart', JSON.stringify(cart));
    syncCartBadge();
}

// Add item to cart
function addToCart(id, name, price, image) {
    id = parseInt(id);
    price = parseFloat(price);
    
    const existingItem = cart.find(item => item.id === id);
    
    if (existingItem) {
        existingItem.quantity += 1;
    } else {
        cart.push({
            id: id,
            name: name,
            price: price,
            image: image,
            quantity: 1
        });
    }
    
    saveCart();
    animateCartBadge();
    showToast(`${name} added to cart!`);
}

// Update quantity
function updateQuantity(id, change) {
    id = parseInt(id);
    const item = cart.find(item => item.id === id);
    
    if (item) {
        item.quantity += change;
        if (item.quantity <= 0) {
            removeFromCart(id);
            return;
        }
        saveCart();
        
        // Re-render if on cart page
        if (document.getElementById('cart-items-container')) {
            renderCartPage();
        }
    }
}

// Remove item from cart
function removeFromCart(id) {
    id = parseInt(id);
    cart = cart.filter(item => item.id !== id);
    saveCart();
    
    // Re-render if on cart page
    if (document.getElementById('cart-items-container')) {
        renderCartPage();
    }
    
    showToast("Item removed from cart.");
}

// Clear cart
function clearCart() {
    cart = [];
    saveCart();
}

// Get total items in cart
function getCartCount() {
    return cart.reduce((total, item) => total + item.quantity, 0);
}

// Get cart subtotal
function getCartSubtotal() {
    return cart.reduce((total, item) => total + (item.price * item.quantity), 0);
}

// Sync Navbar Cart Badge count
function syncCartBadge() {
    const badges = document.querySelectorAll('.cart-count');
    const count = getCartCount();
    badges.forEach(badge => {
        badge.textContent = count;
        if (count > 0) {
            badge.classList.remove('d-none');
        } else {
            badge.classList.add('d-none');
        }
    });
}

// Animate Cart Button on adding item
function animateCartBadge() {
    const cartBtn = document.querySelector('.cart-badge-btn');
    if (cartBtn) {
        cartBtn.classList.add('scale-animation');
        setTimeout(() => {
            cartBtn.classList.remove('scale-animation');
        }, 300);
    }
}

// Render the cart page dynamically
function renderCartPage() {
    const container = document.getElementById('cart-items-container');
    const subtotalEl = document.getElementById('cart-subtotal');
    const deliveryRow = document.getElementById('delivery-charge-row');
    const deliveryEl = document.getElementById('cart-delivery-charge');
    const totalEl = document.getElementById('cart-total');
    const checkoutBtn = document.getElementById('checkout-btn');
    
    if (cart.length === 0) {
        container.innerHTML = `
            <div class="text-center py-5">
                <i class="bi bi-cart3 display-1 text-muted mb-3" style="color: var(--accent-gold) !important;"></i>
                <h3 class="display-font">Your cart is empty</h3>
                <p class="text-muted">Explore our fresh menu and add some delicious items.</p>
                <a href="menu.php" class="btn btn-sage mt-3">Browse Menu</a>
            </div>
        `;
        if (subtotalEl) subtotalEl.textContent = '₹0.00';
        if (totalEl) totalEl.textContent = '₹0.00';
        if (checkoutBtn) checkoutBtn.classList.add('disabled');
        return;
    }
    
    if (checkoutBtn) checkoutBtn.classList.remove('disabled');
    
    let html = '';
    cart.forEach(item => {
        const itemTotal = (item.price * item.quantity).toFixed(2);
        html += `
            <div class="row cart-item-row align-items-center">
                <div class="col-3 col-md-2">
                    <img src="${item.image || 'assets/images/menu_placeholder.jpg'}" alt="${item.name}" class="img-fluid rounded shadow-sm" style="max-height: 80px; object-fit: cover; width: 100%;">
                </div>
                <div class="col-9 col-md-4">
                    <h5 class="mb-1 display-font text-dark">${item.name}</h5>
                    <p class="mb-0 text-muted" style="font-size: 0.9rem;">₹${item.price.toFixed(2)} each</p>
                </div>
                <div class="col-6 col-md-3 mt-3 mt-md-0 d-flex align-items-center">
                    <button type="button" class="qty-btn" onclick="updateQuantity(${item.id}, -1)"><i class="bi bi-dash"></i></button>
                    <span class="mx-3 fw-bold">${item.quantity}</span>
                    <button type="button" class="qty-btn" onclick="updateQuantity(${item.id}, 1)"><i class="bi bi-plus"></i></button>
                </div>
                <div class="col-6 col-md-3 mt-3 mt-md-0 text-end">
                    <span class="fw-bold text-dark block-md">₹${itemTotal}</span>
                    <button type="button" class="btn btn-sm text-danger ms-3 border-0 bg-transparent" onclick="removeFromCart(${item.id})" aria-label="Remove item"><i class="bi bi-trash"></i></button>
                </div>
            </div>
        `;
    });
    
    container.innerHTML = html;
    
    const subtotal = getCartSubtotal();
    if (subtotalEl) subtotalEl.textContent = '₹' + subtotal.toFixed(2);
    
    // Check if delivery charge is set in session config
    let deliveryCharge = 0.00;
    
    // We fetch current order type choice on page to calculate correctly
    const orderTypeEl = document.querySelector('input[name="order_type_toggle"]:checked');
    const isDelivery = orderTypeEl ? orderTypeEl.value === 'delivery' : false;
    
    // Load config delivery charge (usually stored in data-delivery-charge attribute on the container)
    const rawDeliveryCharge = parseFloat(container.getAttribute('data-delivery-charge') || 0);
    
    if (isDelivery) {
        deliveryCharge = rawDeliveryCharge;
        if (deliveryRow) deliveryRow.classList.remove('d-none');
    } else {
        if (deliveryRow) deliveryRow.classList.add('d-none');
    }
    
    if (deliveryEl) deliveryEl.textContent = '₹' + deliveryCharge.toFixed(2);
    
    const total = subtotal + deliveryCharge;
    if (totalEl) totalEl.textContent = '₹' + total.toFixed(2);
}

// Bind order type toggles (pickup / delivery) on cart/checkout pages
function setupOrderTypeToggles() {
    const toggles = document.querySelectorAll('input[name="order_type_toggle"]');
    toggles.forEach(toggle => {
        toggle.addEventListener('change', () => {
            if (document.getElementById('cart-items-container')) {
                renderCartPage();
            }
        });
    });
}

// Prepare checkout page details
function prepareCheckout() {
    const cartInput = document.getElementById('cart_data_input');
    const orderTypeInput = document.getElementById('order_type_input');
    const orderSummaryEl = document.getElementById('checkout-order-summary');
    
    if (cart.length === 0) {
        window.location.href = 'menu.php';
        return;
    }
    
    // Feed hidden inputs for POST submission
    if (cartInput) {
        cartInput.value = JSON.stringify(cart);
    }
    
    // Set up summary on checkout page
    if (orderSummaryEl) {
        let html = '<ul class="list-group list-group-flush bg-transparent">';
        cart.forEach(item => {
            html += `
                <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 px-0 py-2">
                    <div>
                        <h6 class="mb-0 text-dark">${item.name} <span class="text-muted">x ${item.quantity}</span></h6>
                    </div>
                    <span class="text-muted">₹${(item.price * item.quantity).toFixed(2)}</span>
                </li>
            `;
        });
        html += '</ul>';
        orderSummaryEl.innerHTML = html;
    }
    
    // Handle toggles for delivery address display
    const deliveryOptions = document.querySelectorAll('input[name="order_type"]');
    const deliveryAddressSection = document.getElementById('delivery-address-section');
    
    deliveryOptions.forEach(opt => {
        opt.addEventListener('change', () => {
            if (opt.value === 'delivery') {
                if (deliveryAddressSection) deliveryAddressSection.classList.remove('d-none');
                // Make inputs required
                setAddressFieldsRequired(true);
            } else {
                if (deliveryAddressSection) deliveryAddressSection.classList.add('d-none');
                setAddressFieldsRequired(false);
            }
            calculateCheckoutTotal();
        });
    });
    
    calculateCheckoutTotal();
}

function setAddressFieldsRequired(required) {
    const addressInput = document.getElementById('delivery_address');
    const cityInput = document.getElementById('delivery_city');
    const zipInput = document.getElementById('delivery_zip');
    
    if (addressInput) addressInput.required = required;
    if (cityInput) cityInput.required = required;
    if (zipInput) zipInput.required = required;
}

function calculateCheckoutTotal() {
    const subtotalEl = document.getElementById('checkout-subtotal');
    const discountRow = document.getElementById('checkout-discount-row');
    const discountLabelEl = document.getElementById('checkout-discount-label');
    const discountAmountEl = document.getElementById('checkout-discount-amount');
    const taxRow = document.getElementById('checkout-tax-row');
    const taxLabelEl = document.getElementById('checkout-tax-label');
    const taxAmountEl = document.getElementById('checkout-tax-amount');
    const deliveryRow = document.getElementById('checkout-delivery-row');
    const deliveryEl = document.getElementById('checkout-delivery-charge');
    const totalEl = document.getElementById('checkout-total');
    const submitBtn = document.querySelector('#checkout-form button[type="submit"]');
    
    const couponInput = document.getElementById('coupon_code');
    const couponCode = couponInput ? couponInput.value.trim() : '';
    
    const selectedTypeEl = document.querySelector('input[name="order_type"]:checked');
    const orderType = selectedTypeEl ? selectedTypeEl.value : 'pickup';
    
    if (cart.length === 0) return;
    
    const formData = new FormData();
    formData.append('cart_data', JSON.stringify(cart));
    formData.append('order_type', orderType);
    formData.append('coupon_code', couponCode);
    
    fetch('api/calculate-total.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update Subtotal
            if (subtotalEl) subtotalEl.textContent = '₹' + data.subtotal.toFixed(2);
            
            // Update Discount
            if (data.discount_amount > 0) {
                if (discountRow) discountRow.classList.remove('d-none');
                if (discountLabelEl) discountLabelEl.textContent = 'Discount (' + data.applied_promo_name + ')';
                if (discountAmountEl) discountAmountEl.textContent = '-₹' + data.discount_amount.toFixed(2);
            } else {
                if (discountRow) discountRow.classList.add('d-none');
            }
            
            // Update Tax
            if (data.tax_amount > 0) {
                if (taxRow) taxRow.classList.remove('d-none');
                if (taxLabelEl) taxLabelEl.textContent = 'GST/Tax (' + data.tax_rate + '%)';
                if (taxAmountEl) taxAmountEl.textContent = '₹' + data.tax_amount.toFixed(2);
            } else {
                if (taxRow) taxRow.classList.add('d-none');
            }
            
            // Update Delivery
            if (orderType === 'delivery') {
                if (deliveryRow) deliveryRow.classList.remove('d-none');
                if (deliveryEl) deliveryEl.textContent = data.delivery_charge > 0 ? '₹' + data.delivery_charge.toFixed(2) : 'FREE';
            } else {
                if (deliveryRow) deliveryRow.classList.add('d-none');
            }
            
            // Update Grand Total
            if (totalEl) totalEl.textContent = '₹' + data.total_amount.toFixed(2);
            
            // Check minimum order limit for delivery
            if (data.delivery_limit_error) {
                if (submitBtn) submitBtn.disabled = true;
                showToast(`Delivery requires minimum order of ₹${data.minimum_delivery_order.toFixed(2)}.`);
            } else {
                if (submitBtn) submitBtn.disabled = false;
            }
            
            // If they entered a valid coupon code and it succeeded, show a toast notification!
            if (couponCode !== '' && data.discount_amount > 0) {
                showToast(`Coupon '${couponCode}' applied successfully!`);
            }
        } else {
            showToast(data.error || 'Failed to verify promotion details.');
            if (couponInput) couponInput.value = ''; // Reset invalid coupon in form
            // Recalculate without coupon code
            if (couponCode !== '') {
                calculateCheckoutTotal();
            }
        }
    })
    .catch(error => {
        console.error('API total check error:', error);
    });
}

// Simple dynamic Toast notification
function showToast(message) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.position = 'fixed';
        container.style.bottom = '30px';
        container.style.right = '30px';
        container.style.zIndex = '9999';
        document.body.appendChild(container);
    }
    
    const toast = document.createElement('div');
    toast.className = 'toast-msg fade show shadow-sm border';
    toast.style.backgroundColor = '#ffffff';
    toast.style.borderColor = '#EFEAE0';
    toast.style.borderLeft = '4px solid #78906F';
    toast.style.color = '#292725';
    toast.style.padding = '15px 25px';
    toast.style.borderRadius = '8px';
    toast.style.marginBottom = '10px';
    toast.style.fontFamily = 'Inter, sans-serif';
    toast.style.fontSize = '0.95rem';
    toast.style.display = 'flex';
    toast.style.alignItems = 'center';
    toast.style.gap = '10px';
    
    toast.innerHTML = `<i class="bi bi-check2-circle" style="color: #78906F; font-size: 1.25rem;"></i> <span>${message}</span>`;
    
    container.appendChild(toast);
    
    setTimeout(() => {
        toast.classList.remove('show');
        toast.classList.add('hide');
        setTimeout(() => toast.remove(), 500);
    }, 3000);
}

// Add CSS keyframes for scales
const style = document.createElement('style');
style.innerHTML = `
    .scale-animation {
        transform: scale(1.15);
        background-color: var(--accent-sage) !important;
        color: white !important;
    }
    .toast-msg {
        animation: slideIn 0.3s ease forwards;
    }
    @keyframes slideIn {
        from { transform: translateX(120%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
`;
document.head.appendChild(style);
