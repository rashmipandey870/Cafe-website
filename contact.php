<?php
/**
 * contact.php
 * Contact Page - Customer inquiry forms and location details
 */

$page_title = 'Contact Us';
$page_description = 'Get in touch with Mellow & Meadow Café. Find our phone number, email address, locations, hours, or send an enquiry directly online.';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$db = get_db_connection();
$errors = [];
$success = false;

// Process POST submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Validate CSRF token
    enforce_csrf();
    
    // 2. Extract inputs
    $name = trim(filter_input(INPUT_POST, 'name', FILTER_DEFAULT));
    $email = trim(filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL));
    $phone = trim(filter_input(INPUT_POST, 'phone', FILTER_DEFAULT));
    $subject = trim(filter_input(INPUT_POST, 'subject', FILTER_DEFAULT));
    $message = trim(filter_input(INPUT_POST, 'message', FILTER_DEFAULT));
    
    // 3. Validation
    if (empty($name)) {
        $errors[] = "Please enter your name.";
    }
    if (!$email) {
        $errors[] = "Please enter a valid email address.";
    }
    if (empty($subject)) {
        $errors[] = "Please enter a subject.";
    }
    if (empty($message)) {
        $errors[] = "Please enter your message.";
    }
    
    // 4. Save to Database
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO messages (name, email, phone, subject, message, is_read) 
                                  VALUES (:name, :email, :phone, :subject, :message, 0)");
            $stmt->execute([
                ':name' => $name,
                ':email' => $email,
                ':phone' => $phone,
                ':subject' => $subject,
                ':message' => $message
            ]);
            $success = true;
        } catch (PDOException $e) {
            error_log("Contact DB Insert Error: " . $e->getMessage());
            $errors[] = "An error occurred on the server. Please try calling us directly.";
        }
    }
}
?>

<!-- Contact Header -->
<section class="section-padding py-5" style="background-color: var(--bg-secondary);">
    <div class="container text-center">
        <span class="section-tagline mb-2 d-inline-block">Get in Touch</span>
        <h1 class="display-4 display-font mb-2">Contact Us</h1>
        <p class="text-muted mb-0">Have a query, feedback, or catering requirement? Drop us a line below.</p>
    </div>
</section>

<!-- Contact Body -->
<section class="section-padding">
    <div class="container">
        
        <div class="row g-5">
            <!-- Left Column: Business Details -->
            <div class="col-lg-5">
                <div class="contact-info-card h-100 shadow-sm border bg-cream">
                    <h3 class="display-font h2 mb-4 text-dark" style="color: var(--accent-coffee) !important;">Visit Mellow & Meadow</h3>
                    <p class="text-muted mb-4">We are located in a peaceful street in Green Park. Come for the quiet space, stay for the fresh brews.</p>
                    
                    <div class="d-flex align-items-start mb-4">
                        <div class="text-success me-3" style="color: var(--accent-sage) !important; font-size: 1.5rem;">
                            <i class="bi bi-geo-alt-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Our Address</h5>
                            <p class="text-muted mb-0"><?php echo escape($settings['cafe_address']); ?></p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mb-4">
                        <div class="text-success me-3" style="color: var(--accent-sage) !important; font-size: 1.5rem;">
                            <i class="bi bi-telephone-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Call Us</h5>
                            <p class="text-muted mb-0"><?php echo escape($settings['cafe_phone']); ?></p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start mb-4">
                        <div class="text-success me-3" style="color: var(--accent-sage) !important; font-size: 1.5rem;">
                            <i class="bi bi-envelope-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Email Us</h5>
                            <p class="text-muted mb-0"><?php echo escape($settings['cafe_email']); ?></p>
                        </div>
                    </div>
                    
                    <div class="d-flex align-items-start">
                        <div class="text-success me-3" style="color: var(--accent-sage) !important; font-size: 1.5rem;">
                            <i class="bi bi-clock-fill"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold text-dark mb-1">Operating Hours</h5>
                            <p class="text-muted mb-0"><?php echo escape($settings['cafe_opening_hours']); ?></p>
                        </div>
                    </div>
                    
                    <?php if (!empty($settings['cafe_whatsapp'])): ?>
                        <div class="mt-4 pt-3 border-top" style="border-color: rgba(0,0,0,0.05) !important;">
                            <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $settings['cafe_whatsapp']); ?>" class="btn btn-sage w-100 py-3" target="_blank">
                                <i class="bi bi-whatsapp me-2"></i>Text us on WhatsApp
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Right Column: Enquiry Form -->
            <div class="col-lg-7">
                
                <?php if ($success): ?>
                    <div class="card p-5 text-center shadow-sm border-0 mb-4 bg-cream h-100 d-flex flex-column justify-content-center" style="border-radius: var(--border-radius-md);">
                        <i class="bi bi-send-check text-success display-1 mb-4" style="color: var(--accent-sage) !important;"></i>
                        <h2 class="display-font h1 text-dark mb-3">Message Sent!</h2>
                        <p class="text-muted mx-auto" style="max-width: 500px;">
                            Thank you! Your inquiry has been received. Our team will review your message and reach out to you at <strong><?php echo escape($email); ?></strong> as soon as possible.
                        </p>
                        <div class="mt-4">
                            <a href="index.php" class="btn btn-sage px-4 py-2">Back to Home</a>
                        </div>
                    </div>
                <?php else: ?>
                    
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
                        <h2 class="display-font h2 text-dark mb-4" style="border-bottom: 1px solid #EFEAE0; padding-bottom: 12px;">Send an Enquiry</h2>
                        
                        <form action="contact.php" method="POST">
                            <!-- CSRF PROTECTION -->
                            <?php echo csrf_field(); ?>
                            
                            <div class="row g-3">
                                <!-- Name -->
                                <div class="col-12">
                                    <label for="name" class="form-label">Full Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="name" name="name" required placeholder="Your Name" value="<?php echo isset($_POST['name']) ? escape($_POST['name']) : ''; ?>">
                                </div>
                                
                                <!-- Email -->
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="email" name="email" required placeholder="e.g. email@example.com" value="<?php echo isset($_POST['email']) ? escape($_POST['email']) : ''; ?>">
                                </div>
                                
                                <!-- Phone -->
                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Phone Number (Optional)</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" placeholder="e.g. +91 98765 43210" value="<?php echo isset($_POST['phone']) ? escape($_POST['phone']) : ''; ?>">
                                </div>
                                
                                <!-- Subject -->
                                <div class="col-12">
                                    <label for="subject" class="form-label">Subject <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="subject" name="subject" required placeholder="e.g. Catering, Feedback, Supplier Query" value="<?php echo isset($_POST['subject']) ? escape($_POST['subject']) : ''; ?>">
                                </div>
                                
                                <!-- Message -->
                                <div class="col-12">
                                    <label for="message" class="form-label">Message <span class="text-danger">*</span></label>
                                    <textarea class="form-control" id="message" name="message" rows="5" required placeholder="Enter details of your request here..."><?php echo isset($_POST['message']) ? escape($_POST['message']) : ''; ?></textarea>
                                </div>
                                
                                <div class="col-12 d-grid mt-4">
                                    <button type="submit" class="btn btn-sage py-3 fw-bold">Send Message</button>
                                </div>
                            </div>
                        </form>
                    </div>
                <?php endif; ?>
                
            </div>
        </div>
        
        <!-- Google Map Embedding Section -->
        <?php if (!empty($settings['cafe_google_maps'])): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <div class="map-container shadow-sm">
                        <iframe 
                            src="<?php echo escape($settings['cafe_google_maps']); ?>" 
                            width="100%" 
                            height="450" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy" 
                            referrerpolicy="no-referrer-when-downgrade">
                        </iframe>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
