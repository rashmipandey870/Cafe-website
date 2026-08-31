<?php
/**
 * reservation.php
 * Table Reservation Page - Dynamic booking validation and DB entry
 */

$page_title = 'Book a Table';
$page_description = 'Reserve a table online at Mellow & Meadow Café. Select your date, time, and guest count for a relaxing breakfast or dinner.';

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

$db = get_db_connection();
$errors = [];
$success = false;

// Process booking post
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Check CSRF
    enforce_csrf();
    
    // 2. Filter Inputs
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_DEFAULT));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $res_date = trim(filter_input(INPUT_POST, 'res_date', FILTER_DEFAULT));
    $res_time = trim(filter_input(INPUT_POST, 'res_time', FILTER_DEFAULT));
    $guests = (int)filter_input(INPUT_POST, 'guests', FILTER_VALIDATE_INT);
    $special_request = trim(filter_input(INPUT_POST, 'special_request', FILTER_DEFAULT));
    
    // 3. Server-Side Validations
    if (empty($name)) {
        $errors[] = "Please enter your name.";
    }
    if (empty($phone)) {
        $errors[] = "Please enter your contact phone number.";
    }
    if (!$email) {
        $errors[] = "Please enter a valid email address.";
    }
    
    // Date Checks (must be in future)
    if (empty($res_date)) {
        $errors[] = "Please select a date for your reservation.";
    } else {
        $today = date('Y-m-d');
        if ($res_date < $today) {
            $errors[] = "Reservation date must be today or in the future.";
        }
    }
    
    // Time Checks (must be between 8:00 AM and 9:30 PM)
    if (empty($res_time)) {
        $errors[] = "Please select a booking time.";
    } else {
        $time_hour = (int)date('H', strtotime($res_time));
        $time_min = (int)date('i', strtotime($res_time));
        
        if ($time_hour < 8 || ($time_hour >= 21 && $time_min > 30) || $time_hour > 21) {
            $errors[] = "Reservation times must be between 8:00 AM and 9:30 PM (kitchen closes at 9:30 PM).";
        }
    }
    
    // Guest Counts (1 to 20)
    if ($guests < 1 || $guests > 20) {
        $errors[] = "Online bookings are restricted to 1 to 20 guests. For larger parties, please contact us directly.";
    }
    
    // 4. Save to Database
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO reservations 
                (name, phone, email, reservation_date, reservation_time, guests, special_request, status) 
                VALUES 
                (:name, :phone, :email, :res_date, :res_time, :guests, :special_request, 'pending')");
            
            $stmt->execute([
                ':name' => $name,
                ':phone' => $phone,
                ':email' => $email,
                ':res_date' => $res_date,
                ':res_time' => $res_time,
                ':guests' => $guests,
                ':special_request' => $special_request
            ]);
            
            $success = true;
            
        } catch (PDOException $e) {
            error_log("Reservation DB Insert Error: " . $e->getMessage());
            $errors[] = "A system error occurred. Please try again later or book by phone.";
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Reservation Header -->
<section class="section-padding py-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <span class="section-tagline mb-2 d-inline-block">Gatherings</span>
        <h1 class="display-4 display-font mb-2">Book a Table</h1>
        <p class="text-muted mb-0">Join us for sunlit mornings, quiet workspaces, or shared brunches.</p>
    </div>
</section>

<!-- Reservation Form -->
<section class="section-padding">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                
                <!-- Success State Alert -->
                <?php if ($success): ?>
                    <div class="card p-5 text-center shadow-sm border-0 mb-4 bg-cream" style="border-radius: var(--border-radius-md);">
                        <i class="bi bi-calendar-check-fill text-success display-1 mb-4" style="color: var(--accent-sage) !important;"></i>
                        <h2 class="display-font h1 text-dark mb-3">Reservation Submitted</h2>
                        <p class="text-muted mx-auto" style="max-width: 500px;">
                            Thank you! Your table booking request has been received. Our team will review your request and send a confirmation to <strong><?php echo escape($email); ?></strong> shortly.
                        </p>
                        <div class="mt-4">
                            <a href="menu.php" class="btn btn-sage px-4 py-2">Explore Menu</a>
                            <a href="index.php" class="btn btn-outline-sage px-4 py-2 ms-2">Back to Home</a>
                        </div>
                    </div>
                <?php else: ?>
                    
                    <!-- Errors Alert -->
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
                    
                    <!-- Form -->
                    <div class="card p-4 p-md-5 shadow-sm border bg-white" style="border-radius: var(--border-radius-md);">
                        <h2 class="display-font h2 text-dark mb-4" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 12px;">Reservation Request</h2>
                        
                        <form action="reservation.php" method="POST">
                            <!-- CSRF Field -->
                            <?php echo csrf_field(); ?>
                            
                            <div class="row g-4">
                                <!-- Name -->
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required placeholder="Your Name" value="<?php echo isset($_POST['name']) ? escape($_POST['name']) : ''; ?>">
                                </div>
                                
                                <!-- Phone -->
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required placeholder="e.g. +91 98765 43210" value="<?php echo isset($_POST['phone']) ? escape($_POST['phone']) : ''; ?>">
                                </div>
                                
                                <!-- Email -->
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required placeholder="e.g. name@example.com" value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : ''; ?>">
                                </div>
                                
                                <!-- Guests -->
                                <div class="col-md-6">
                                    <label for="guests" class="form-label">Number of Guests <span class="text-danger">*</span></label>
                                    <select class="form-select" id="guests" name="guests" required>
                                        <?php 
                                            $curr_guests = isset($_POST['guests']) ? (int)$_POST['guests'] : 2;
                                            for ($i = 1; $i <= 20; $i++): 
                                        ?>
                                            <option value="<?php echo $i; ?>" <?php echo $i === $curr_guests ? 'selected' : ''; ?>>
                                                <?php echo $i; ?> <?php echo $i === 1 ? 'Guest' : 'Guests'; ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                
                                <!-- Date -->
                                <div class="col-md-6">
                                    <label for="res_date" class="form-label">Date <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="res_date" name="res_date" required min="<?php echo date('Y-m-d'); ?>" value="<?php echo isset($_POST['res_date']) ? escape($_POST['res_date']) : date('Y-m-d'); ?>">
                                </div>
                                
                                <!-- Time -->
                                <div class="col-md-6">
                                    <label for="res_time" class="form-label">Time <span class="text-danger">*</span></label>
                                    <input type="time" class="form-control" id="res_time" name="res_time" required min="08:00" max="21:30" value="<?php echo isset($_POST['res_time']) ? escape($_POST['res_time']) : '10:00'; ?>">
                                    <small class="text-muted">Available slots: 8:00 AM - 9:30 PM</small>
                                </div>
                                
                                <!-- Special Request -->
                                <div class="col-12">
                                    <label for="special_request" class="form-label">Special Requests (Optional)</label>
                                    <textarea class="form-control" id="special_request" name="special_request" rows="3" placeholder="e.g. Baby chair, window seat, celebrating an anniversary, food allergies..."><?php echo isset($_POST['special_request']) ? escape($_POST['special_request']) : ''; ?></textarea>
                                </div>
                                
                                <!-- Submit button -->
                                <div class="col-12 d-grid mt-4">
                                    <button type="submit" class="btn btn-sage py-3 fw-bold">Confirm Reservation Request</button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
