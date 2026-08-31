<?php
/**
 * register.php
 * Customer Registration Page - Optional customer profile creations
 */

$page_title = 'Create Customer Account';
$page_description = 'Sign up for a Mellow & Meadow customer account to easily manage bookings and save shipping address details.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    header("Location: customer-dashboard.php");
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf();
    
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_DEFAULT));
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';
    
    $address = trim(filter_input(INPUT_POST, 'address', FILTER_DEFAULT));
    $city = trim(filter_input(INPUT_POST, 'city', FILTER_DEFAULT));
    $zip = trim(filter_input(INPUT_POST, 'zip', FILTER_DEFAULT));
    
    if (empty($name)) {
        $errors[] = "Please enter your name.";
    }
    if (!$email) {
        $errors[] = "Please enter a valid email address.";
    }
    if (empty($phone)) {
        $errors[] = "Please enter your phone number.";
    }
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters long.";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (empty($errors)) {
        try {
            $db = get_db_connection();
            
            // Check if email or phone is already registered
            $stmt = $db->prepare("SELECT id, password_hash FROM customers WHERE email = :email OR phone = :phone LIMIT 1");
            $stmt->execute([':email' => $email, ':phone' => $phone]);
            $existing = $stmt->fetch();
            
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            if ($existing) {
                // If they exist but password_hash is empty, they checked out as a guest before.
                // We let them claim their account by setting a password!
                if (empty($existing['password_hash'])) {
                    $upd_stmt = $db->prepare("UPDATE customers 
                        SET name = :name, phone = :phone, password_hash = :hash, address = :address, city = :city, zip = :zip 
                        WHERE id = :id");
                    $upd_stmt->execute([
                        ':name' => $name,
                        ':phone' => $phone,
                        ':hash' => $password_hash,
                        ':address' => $address ?: null,
                        ':city' => $city ?: null,
                        ':zip' => $zip ?: null,
                        ':id' => $existing['id']
                    ]);
                    
                    header("Location: login.php?registered=1");
                    exit;
                } else {
                    $errors[] = "An account with this email or phone number is already registered.";
                }
            } else {
                // Insert brand new customer record
                $ins_stmt = $db->prepare("INSERT INTO customers 
                    (name, email, phone, password_hash, address, city, zip) 
                    VALUES 
                    (:name, :email, :phone, :hash, :address, :city, :zip)");
                $ins_stmt->execute([
                    ':name' => $name,
                    ':email' => $email,
                    ':phone' => $phone,
                    ':hash' => $password_hash,
                    ':address' => $address ?: null,
                    ':city' => $city ?: null,
                    ':zip' => $zip ?: null
                ]);
                
                header("Location: login.php?registered=1");
                exit;
            }
        } catch (PDOException $e) {
            error_log("Customer Signup Error: " . $e->getMessage());
            $errors[] = "A system error occurred. Please try again.";
        }
    }
}
?>

<section class="section-padding py-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <h1 class="display-4 display-font mb-2">Create Customer Account</h1>
        <p class="text-muted mb-0">Join Mellow & Meadow to unlock address saving and online booking tracking.</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
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
                
                <div class="card p-4 p-md-5 shadow-sm border bg-white" style="border-radius: var(--border-radius-md);">
                    <h2 class="display-font h2 text-dark mb-4" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 12px;">Customer Signup</h2>
                    
                    <form action="register.php" method="POST">
                        <?php echo csrf_field(); ?>
                        
                        <div class="row g-3">
                            <!-- Basic details -->
                            <div class="col-12">
                                <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="name" name="name" required placeholder="Your full name" value="<?php echo isset($_POST['name']) ? escape($_POST['name']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="email" name="email" required placeholder="name@example.com" value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="tel" class="form-control" id="phone" name="phone" required placeholder="e.g. +91 98765 43210" value="<?php echo isset($_POST['phone']) ? escape($_POST['phone']) : ''; ?>">
                            </div>
                            
                            <!-- Passwords -->
                            <div class="col-md-6">
                                <label for="password" class="form-label">Password <span class="text-danger">*</span> (Min 6 chars)</label>
                                <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required placeholder="••••••••">
                            </div>
                            
                            <h4 class="display-font text-dark fs-4 mt-5 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">Default Delivery Location (Optional)</h4>
                            
                            <!-- Address -->
                            <div class="col-12">
                                <label for="address" class="form-label">Street Address</label>
                                <input type="text" class="form-control" id="address" name="address" placeholder="Apartment, block, street address" value="<?php echo isset($_POST['address']) ? escape($_POST['address']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" placeholder="City" value="<?php echo isset($_POST['city']) ? escape($_POST['city']) : ''; ?>">
                            </div>
                            
                            <div class="col-md-6">
                                <label for="zip" class="form-label">Postal / ZIP Code</label>
                                <input type="text" class="form-control" id="zip" name="zip" placeholder="ZIP Code" value="<?php echo isset($_POST['zip']) ? escape($_POST['zip']) : ''; ?>">
                            </div>
                            
                            <!-- Submit -->
                            <div class="col-12 d-grid mt-4">
                                <button type="submit" class="btn btn-sage py-3 fw-bold">Register Account</button>
                            </div>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <span class="text-muted small">Already have an account? </span>
                        <a href="login.php" class="small fw-bold">Sign In Here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
