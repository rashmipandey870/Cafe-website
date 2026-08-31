<?php
/**
 * login.php
 * Customer Login Page - Authentication gate for orders history and profile management
 */

$page_title = 'Customer Login';
$page_description = 'Sign in to your Mellow & Meadow customer account to track your orders, manage reservations, and edit saved delivery addresses.';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['customer_logged_in']) && $_SESSION['customer_logged_in'] === true) {
    header("Location: customer-dashboard.php");
    exit;
}

$error = '';
$success_msg = isset($_GET['registered']) ? 'Registration successful! Please sign in below.' : '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf();
    
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    
    if (!$email || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        try {
            $db = get_db_connection();
            // Fetch customer record with password_hash
            $stmt = $db->prepare("SELECT id, name, email, phone, password_hash FROM customers WHERE email = :email LIMIT 1");
            $stmt->execute([':email' => $email]);
            $cust = $stmt->fetch();
            
            // Confirm customer exists and has a registered password (guests will have NULL password_hash)
            if ($cust && !empty($cust['password_hash']) && password_verify($password, $cust['password_hash'])) {
                // Populate customer session variables
                $_SESSION['customer_logged_in'] = true;
                $_SESSION['customer_id']        = (int)$cust['id'];
                $_SESSION['customer_name']      = $cust['name'];
                $_SESSION['customer_email']     = $cust['email'];
                $_SESSION['customer_phone']     = $cust['phone'];
                
                session_regenerate_id(true);
                
                // Redirect back to dashboard or checkout if they came from cart
                header("Location: customer-dashboard.php");
                exit;
            } else {
                $error = "Invalid email or password.";
            }
        } catch (PDOException $e) {
            error_log("Customer Login DB Exception: " . $e->getMessage());
            $error = "A system error occurred. Please try again.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<section class="section-padding py-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <h1 class="display-4 display-font mb-2">Customer Login</h1>
        <p class="text-muted mb-0">Manage your table reservations, profile addresses, and past order details.</p>
    </div>
</section>

<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo escape($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success_msg)): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo escape($success_msg); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <div class="card p-4 p-md-5 shadow-sm border bg-white" style="border-radius: var(--border-radius-md);">
                    <h2 class="display-font h2 text-dark mb-4 text-center">Sign In</h2>
                    
                    <form action="login.php" method="POST">
                        <?php echo csrf_field(); ?>
                        
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" class="form-control" id="email" name="email" required placeholder="Enter your email" value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : ''; ?>">
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required placeholder="••••••••">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-sage py-3 fw-bold">Login</button>
                        </div>
                    </form>
                    
                    <div class="text-center mt-4">
                        <span class="text-muted small">Don't have an account? </span>
                        <a href="register.php" class="small fw-bold">Sign Up Here</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
