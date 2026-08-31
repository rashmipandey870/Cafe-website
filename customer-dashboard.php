<?php
/**
 * customer-dashboard.php
 * Customer Account Dashboard - Manage profiles, track active orders, and view bookings
 */

$page_title = 'My Dashboard';
$page_description = 'View your Mellow & Meadow orders history, track reservations, and edit your saved billing addresses.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// Enforce Customer Authentication Gate
if (!isset($_SESSION['customer_logged_in']) || $_SESSION['customer_logged_in'] !== true) {
    header("Location: login.php");
    exit;
}

$customer_id = (int)$_SESSION['customer_id'];
$db = get_db_connection();

$errors = [];
$success_msg = '';

// Handle Profile & Address Updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    enforce_csrf();
    
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_DEFAULT));
    $address = trim(filter_input(INPUT_POST, 'address', FILTER_DEFAULT));
    $city = trim(filter_input(INPUT_POST, 'city', FILTER_DEFAULT));
    $zip = trim(filter_input(INPUT_POST, 'zip', FILTER_DEFAULT));
    
    if (empty($name)) {
        $errors[] = "Please enter your name.";
    }
    if (empty($phone)) {
        $errors[] = "Please enter your phone number.";
    }
    
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("UPDATE customers 
                                  SET name = :name, phone = :phone, address = :address, city = :city, zip = :zip 
                                  WHERE id = :id");
            $stmt->execute([
                ':name' => $name,
                ':phone' => $phone,
                ':address' => $address ?: null,
                ':city' => $city ?: null,
                ':zip' => $zip ?: null,
                ':id' => $customer_id
            ]);
            
            // Sync Session details
            $_SESSION['customer_name']  = $name;
            $_SESSION['customer_phone'] = $phone;
            
            $success_msg = "Your profile information was updated successfully.";
        } catch (PDOException $e) {
            error_log("Update Customer Profile Fail: " . $e->getMessage());
            $errors[] = "Failed to update profile details in database.";
        }
    }
}

// Fetch Latest Customer Data
$customer_data = null;
try {
    $stmt = $db->prepare("SELECT * FROM customers WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $customer_id]);
    $customer_data = $stmt->fetch();
} catch (PDOException $e) {
    error_log("Fetch customer error: " . $e->getMessage());
}

// Fetch Customer's Orders (latest 10)
$orders = [];
try {
    $stmt = $db->prepare("SELECT * FROM orders WHERE customer_id = :cust_id ORDER BY id DESC LIMIT 10");
    $stmt->execute([':cust_id' => $customer_id]);
    $orders = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch customer orders error: " . $e->getMessage());
}

// Fetch Customer's Reservations
$reservations = [];
try {
    // Look up reservations using matching customer email or phone
    $stmt = $db->prepare("SELECT * FROM reservations 
                          WHERE email = :email OR phone = :phone 
                          ORDER BY reservation_date DESC, reservation_time DESC LIMIT 10");
    $stmt->execute([
        ':email' => $_SESSION['customer_email'],
        ':phone' => $_SESSION['customer_phone']
    ]);
    $reservations = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Fetch customer reservations error: " . $e->getMessage());
}
?>

<!-- Header -->
<section class="section-padding py-5" style="background-color: var(--bg-secondary);">
    <div class="container d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div>
            <h1 class="display-5 display-font mb-1 text-dark">Welcome back, <?php echo escape($_SESSION['customer_name']); ?>!</h1>
            <p class="text-muted mb-0">Track active deliveries, check bookings, or update address defaults.</p>
        </div>
        <a href="admin/logout.php" class="btn btn-outline-danger px-4 py-2" onclick="window.location.href='admin/logout.php'; return false;">Logout Account</a>
    </div>
</section>

<!-- Dashboard Main Contents -->
<section class="section-padding">
    <div class="container">
        
        <?php if (!empty($success_msg)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo escape($success_msg); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
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
        
        <div class="row g-5">
            <!-- Left: Profile Updates Form -->
            <div class="col-lg-4">
                <div class="card p-4 shadow-sm border bg-white mb-4" style="border-radius: var(--border-radius-md);">
                    <h3 class="display-font h3 mb-4 text-dark" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 12px;">My Profile</h3>
                    
                    <form action="customer-dashboard.php" method="POST">
                        <?php echo csrf_field(); ?>
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="row g-3">
                            <div class="col-12">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" class="form-control" id="name" name="name" required value="<?php echo escape($customer_data['name']); ?>">
                            </div>
                            
                            <div class="col-12">
                                <label for="phone" class="form-label">Phone Number</label>
                                <input type="tel" class="form-control" id="phone" name="phone" required value="<?php echo escape($customer_data['phone']); ?>">
                            </div>
                            
                            <div class="col-12">
                                <label class="form-label">Email Address</label>
                                <input type="email" class="form-control text-muted bg-light" value="<?php echo escape($customer_data['email']); ?>" readonly disabled>
                                <small class="text-muted">Email cannot be modified.</small>
                            </div>
                            
                            <h4 class="display-font text-dark fs-5 mt-4 mb-2 pb-1 border-bottom" style="border-color: #EFEAE0 !important;">Saved Address</h4>
                            
                            <div class="col-12">
                                <label for="address" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="address" name="address" value="<?php echo escape($customer_data['address']); ?>" placeholder="Street details">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" value="<?php echo escape($customer_data['city']); ?>" placeholder="City">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="zip" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip" name="zip" value="<?php echo escape($customer_data['zip']); ?>" placeholder="ZIP">
                            </div>
                            
                            <div class="col-12 d-grid mt-4">
                                <button type="submit" class="btn btn-sage fw-bold">Update Profile</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Right: Bookings & Orders History Tabs -->
            <div class="col-lg-8">
                <!-- Orders Tab -->
                <div class="card p-4 shadow-sm border bg-white mb-4" style="border-radius: var(--border-radius-md);">
                    <h3 class="display-font h3 mb-4 text-dark" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 12px;">Recent Orders</h3>
                    
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr class="text-muted small uppercase">
                                    <th>Order #</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($orders)): ?>
                                    <?php foreach ($orders as $ord): ?>
                                        <tr>
                                            <td>
                                                <strong class="text-dark"><?php echo escape($ord['order_number']); ?></strong>
                                                <small class="text-muted d-block text-capitalize"><?php echo escape($ord['order_type']); ?></small>
                                            </td>
                                            <td class="small text-muted">
                                                <?php echo date('M d, Y h:i A', strtotime($ord['created_at'])); ?>
                                            </td>
                                            <td class="text-dark fw-bold">
                                                <?php echo format_price($ord['total_amount']); ?>
                                            </td>
                                            <td>
                                                <?php echo get_order_status_badge($ord['order_status']); ?>
                                            </td>
                                            <td class="text-end">
                                                <a href="track-order.php?order_number=<?php echo urlencode($ord['order_number']); ?>" class="btn btn-sm btn-outline-sage">Track</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted">You haven't placed any orders yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <!-- Reservations History -->
                <div class="card p-4 shadow-sm border bg-white" style="border-radius: var(--border-radius-md);">
                    <h3 class="display-font h3 mb-4 text-dark" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 12px;">Reservations Log</h3>
                    
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr class="text-muted small uppercase">
                                    <th>Guests</th>
                                    <th>Date & Time</th>
                                    <th>Special Request</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($reservations)): ?>
                                    <?php foreach ($reservations as $res): ?>
                                        <tr>
                                            <td class="text-dark fw-bold text-center" style="width: 80px;">
                                                <?php echo $res['guests']; ?>
                                            </td>
                                            <td>
                                                <strong class="text-dark d-block"><?php echo date('M d, Y', strtotime($res['reservation_date'])); ?></strong>
                                                <small class="text-muted"><i class="bi bi-clock me-1"></i><?php echo date('h:i A', strtotime($res['reservation_time'])); ?></small>
                                            </td>
                                            <td class="text-muted small" style="max-width: 250px;">
                                                <?php echo !empty($res['special_request']) ? '"' . escape($res['special_request']) . '"' : '<span class="text-muted">—</span>'; ?>
                                            </td>
                                            <td>
                                                <?php echo get_reservation_status_badge($res['status']); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-4 text-muted">You haven't requested any table reservations yet.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
