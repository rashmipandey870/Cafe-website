<?php
/**
 * admin/login.php
 * Administrator Secure Login Portal
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/auth.php';

// If already logged in, skip login page
if (is_admin_logged_in()) {
    header("Location: " . BASE_URL . "/admin/dashboard.php");
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF
    if (!verify_csrf_token()) {
        $error = "Session validation failed. Please reload the page.";
    } else {
        $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
        $password = isset($_POST['password']) ? $_POST['password'] : '';
        
        if (!$email || empty($password)) {
            $error = "Please fill in all credentials.";
        } else {
            try {
                $db = get_db_connection();
                // Select user by email
                $stmt = $db->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = :email LIMIT 1");
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();
                
                if ($user && password_verify($password, $user['password_hash'])) {
                    // Start secure admin session
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_user_id']   = (int)$user['id'];
                    $_SESSION['admin_email']     = $user['email'];
                    $_SESSION['admin_name']      = $user['name'];
                    $_SESSION['admin_role']      = $user['role'];
                    
                    // Regenerate session ID to prevent session fixation attacks
                    session_regenerate_id(true);
                    
                    header("Location: " . BASE_URL . "/admin/dashboard.php");
                    exit;
                } else {
                    // Security: Keep error vague to prevent username enumeration
                    $error = "Invalid email or password.";
                }
            } catch (PDOException $e) {
                error_log("Admin Login DB Error: " . $e->getMessage());
                $error = "A database error occurred. Please contact system administrator.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Mellow & Meadow</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Google Fonts & Custom CSS -->
    <link rel="stylesheet" href="<?php echo BASE_URL; ?>/assets/css/style.css">
    <style>
        body {
            background-color: var(--bg-secondary);
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
        }
        .login-card {
            background-color: #ffffff;
            border: 1px solid #EFEAE0;
            border-radius: var(--border-radius-md);
            padding: 40px;
            max-width: 450px;
            width: 100%;
        }
    </style>
</head>
<body>

<div class="login-card shadow-sm">
    <div class="text-center mb-4">
        <h1 class="display-font h2 text-dark mb-1">M&M Admin Portal</h1>
        <p class="text-muted small">Sign in to manage your website settings & orders</p>
    </div>
    
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert" style="font-size: 0.9rem;">
            <?php echo escape($error); ?>
            <button type="button" class="btn-close" data-bs-alert="alert" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    
    <form action="login.php" method="POST">
        <!-- CSRF Token Input -->
        <?php echo csrf_field(); ?>
        
        <!-- Email -->
        <div class="mb-3">
            <label for="email" class="form-label">Email Address</label>
            <input type="email" class="form-control" id="email" name="email" required autocomplete="email" placeholder="admin@mellowandmeadow.com" value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : ''; ?>">
        </div>
        
        <!-- Password -->
        <div class="mb-4">
            <label for="password" class="form-label">Password</label>
            <input type="password" class="form-control" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
        </div>
        
        <!-- Submit -->
        <div class="d-grid gap-2">
            <button type="submit" class="btn btn-sage py-3 fw-bold">Sign In</button>
            <a href="<?php echo BASE_URL; ?>/index.php" class="btn btn-light py-2 small text-muted">Back to Website</a>
        </div>
    </form>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
