<?php
/**
 * admin/settings/index.php
 * Upgraded Website Settings Center - Configures profiles, locations, tax systems, and delivery boundaries
 */

$page_title = 'Website Settings';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();
$errors = [];

// Handle Form POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf();
    
    // Define the list of keys we permit to update (now includes tax and delivery policies)
    $allowed_setting_keys = [
        'cafe_name',
        'cafe_phone',
        'cafe_email',
        'cafe_address',
        'cafe_opening_hours',
        'cafe_whatsapp',
        'cafe_social_facebook',
        'cafe_social_instagram',
        'cafe_social_twitter',
        'cafe_google_maps',
        'cafe_about_text',
        'cafe_timezone',
        'delivery_enabled',
        'delivery_charge',
        'free_delivery_above',
        'minimum_delivery_order',
        'tax_enabled',
        'tax_rate'
    ];
    
    // Check if a new logo file is being uploaded
    $logo_path = '';
    if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
        $uploaded = upload_image($_FILES['logo_image'], 'assets/images/', 1048576); // Max 1MB for logo
        if ($uploaded) {
            $logo_path = $uploaded;
            
            // Delete the old logo if it exists
            $old_logo = isset($settings['cafe_logo']) ? $settings['cafe_logo'] : '';
            if (!empty($old_logo) && file_exists(__DIR__ . '/../../' . $old_logo) && strpos($old_logo, 'placeholder') === false) {
                @unlink(__DIR__ . '/../../' . $old_logo);
            }
            
            // Save logo to settings database
            try {
                $stmt = $db->prepare("UPDATE settings SET setting_value = :val WHERE setting_key = 'cafe_logo'");
                $stmt->execute([':val' => $logo_path]);
            } catch (PDOException $e) {
                error_log("Logo Update Exception: " . $e->getMessage());
            }
        } else {
            $errors[] = "Failed to upload logo image. Must be a valid JPG/PNG/WEBP under 1MB.";
        }
    }
    
    // Loop through general setting fields and update
    if (empty($errors)) {
        try {
            $db->beginTransaction();
            
            $stmt = $db->prepare("UPDATE settings SET setting_value = :val WHERE setting_key = :key");
            
            foreach ($allowed_setting_keys as $key) {
                $val = '';
                
                // Toggle Switches mapping
                if ($key === 'delivery_enabled' || $key === 'tax_enabled') {
                    $val = isset($_POST[$key]) ? '1' : '0';
                } else {
                    $val = isset($_POST[$key]) ? trim($_POST[$key]) : '';
                }
                
                // Extra validation for decimals
                if (in_array($key, ['delivery_charge', 'free_delivery_above', 'minimum_delivery_order', 'tax_rate'])) {
                    $val = (float)$val;
                    if ($val < 0) $val = 0.00;
                }
                
                $stmt->execute([
                    ':val' => (string)$val,
                    ':key' => $key
                ]);
            }
            
            $db->commit();
            set_flash_message('success', 'Website settings updated successfully! Please reload the page to see changes in navigation.');
            
            // Refresh variables
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            $db->rollBack();
            error_log("Settings Update Exception: " . $e->getMessage());
            $errors[] = "Failed to write updates to database.";
        }
    }
}
?>

<div class="row">
    <div class="col-lg-10 col-xl-8">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" style="font-size: 0.9rem;">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo escape($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <div class="card admin-card p-4 p-md-5 shadow-sm bg-white mb-5">
            <h3 class="display-font text-dark fs-4 mb-4 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">Profile & Identity</h3>
            
            <form action="index.php" method="POST" enctype="multipart/form-data">
                <!-- CSRF PROTECTION -->
                <?php echo csrf_field(); ?>
                
                <div class="row g-4">
                    <!-- Café Name -->
                    <div class="col-md-6">
                        <label for="cafe_name" class="form-label">Café Brand Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cafe_name" name="cafe_name" required value="<?php echo escape($settings['cafe_name']); ?>">
                    </div>
                    
                    <!-- Logo Upload -->
                    <div class="col-md-6">
                        <label for="logo_image" class="form-label">Café Logo (JPG/PNG/WEBP, Max 1MB)</label>
                        <input type="file" class="form-control" id="logo_image" name="logo_image" accept="image/*">
                        <?php if (!empty($settings['cafe_logo'])): ?>
                            <small class="text-muted d-block mt-1">Current logo path: <code><?php echo escape($settings['cafe_logo']); ?></code></small>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Contact Phone -->
                    <div class="col-md-6">
                        <label for="cafe_phone" class="form-label">Contact Phone Number <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cafe_phone" name="cafe_phone" required value="<?php echo escape($settings['cafe_phone']); ?>">
                    </div>
                    
                    <!-- Contact Email -->
                    <div class="col-md-6">
                        <label for="cafe_email" class="form-label">Business Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="cafe_email" name="cafe_email" required value="<?php echo escape($settings['cafe_email']); ?>">
                    </div>
                    
                    <!-- WhatsApp Number -->
                    <div class="col-md-6">
                        <label for="cafe_whatsapp" class="form-label">WhatsApp Contact Number (No spaces)</label>
                        <input type="text" class="form-control" id="cafe_whatsapp" name="cafe_whatsapp" placeholder="e.g. +919876543210" value="<?php echo escape($settings['cafe_whatsapp']); ?>">
                    </div>
                    
                    <!-- Timezone selection -->
                    <div class="col-md-6">
                        <label for="cafe_timezone" class="form-label">Local Café Timezone <span class="text-danger">*</span></label>
                        <select class="form-select text-capitalize" name="cafe_timezone" id="cafe_timezone" required>
                            <?php 
                                $timezones = ['Asia/Kolkata', 'UTC', 'GMT', 'Europe/London', 'America/New_York', 'Asia/Singapore', 'Australia/Sydney'];
                                foreach ($timezones as $tz):
                            ?>
                                <option value="<?php echo $tz; ?>" <?php echo $settings['cafe_timezone'] === $tz ? 'selected' : ''; ?>><?php echo $tz; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- About Us Summary text -->
                    <div class="col-12">
                        <label for="cafe_about_text" class="form-label">Editorial Description / About Us Text <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="cafe_about_text" name="cafe_about_text" rows="4" required><?php echo escape($settings['cafe_about_text']); ?></textarea>
                    </div>
                    
                    <!-- Address -->
                    <div class="col-12">
                        <label for="cafe_address" class="form-label">Physical Address <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cafe_address" name="cafe_address" required value="<?php echo escape($settings['cafe_address']); ?>">
                    </div>
                    
                    <!-- Opening Hours -->
                    <div class="col-12">
                        <label for="cafe_opening_hours" class="form-label">Opening Hours Summary <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cafe_opening_hours" name="cafe_opening_hours" required placeholder="e.g. Mon - Sun: 8:00 AM - 10:00 PM" value="<?php echo escape($settings['cafe_opening_hours']); ?>">
                    </div>
                    
                    <!-- --------------------------------------------------------
                     * POLICY / CALCULATION SETTINGS (NEW SECTION)
                     * -------------------------------------------------------- -->
                    <h4 class="display-font text-dark fs-4 mt-5 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">Taxation & Delivery Policies</h4>
                    
                    <!-- Stacking Tax Toggle -->
                    <div class="col-md-6">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="tax_enabled" name="tax_enabled" value="1" <?php echo ((int)$settings['tax_enabled'] === 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label text-dark fw-bold" for="tax_enabled">Enable GST / Taxation at Checkout</label>
                        </div>
                    </div>
                    
                    <!-- Tax Rate -->
                    <div class="col-md-6">
                        <label for="tax_rate" class="form-label">GST/Tax Rate (%)</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0" class="form-control" id="tax_rate" name="tax_rate" value="<?php echo escape($settings['tax_rate']); ?>">
                            <span class="input-group-text">%</span>
                        </div>
                    </div>
                    
                    <!-- Stacking Delivery Toggle -->
                    <div class="col-md-6 mt-4">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" role="switch" id="delivery_enabled" name="delivery_enabled" value="1" <?php echo ((int)$settings['delivery_enabled'] === 1) ? 'checked' : ''; ?>>
                            <label class="form-check-label text-dark fw-bold" for="delivery_enabled">Enable Home Delivery Service</label>
                        </div>
                    </div>
                    
                    <!-- Delivery Charge -->
                    <div class="col-md-6 mt-4">
                        <label for="delivery_charge" class="form-label">Standard Delivery Charge (INR)</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="delivery_charge" name="delivery_charge" value="<?php echo escape($settings['delivery_charge']); ?>">
                        </div>
                    </div>
                    
                    <!-- Minimum Order amount for delivery -->
                    <div class="col-md-6">
                        <label for="minimum_delivery_order" class="form-label">Minimum Order for Delivery (INR)</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="minimum_delivery_order" name="minimum_delivery_order" value="<?php echo escape($settings['minimum_delivery_order']); ?>">
                        </div>
                    </div>
                    
                    <!-- Free Delivery above -->
                    <div class="col-md-6">
                        <label for="free_delivery_above" class="form-label">Free Delivery Threshold (INR)</label>
                        <div class="input-group">
                            <span class="input-group-text">₹</span>
                            <input type="number" step="0.01" min="0" class="form-control" id="free_delivery_above" name="free_delivery_above" value="<?php echo escape($settings['free_delivery_above']); ?>">
                        </div>
                    </div>
                    
                    <h4 class="display-font text-dark fs-4 mt-5 pb-2 border-bottom" style="border-color: #EFEAE0 !important;">Social Channels & Maps</h4>
                    
                    <!-- Facebook -->
                    <div class="col-md-4">
                        <label for="cafe_social_facebook" class="form-label">Facebook Profile Link</label>
                        <input type="url" class="form-control" id="cafe_social_facebook" name="cafe_social_facebook" value="<?php echo escape($settings['cafe_social_facebook']); ?>">
                    </div>
                    
                    <!-- Instagram -->
                    <div class="col-md-4">
                        <label for="cafe_social_instagram" class="form-label">Instagram Profile Link</label>
                        <input type="url" class="form-control" id="cafe_social_instagram" name="cafe_social_instagram" value="<?php echo escape($settings['cafe_social_instagram']); ?>">
                    </div>
                    
                    <!-- Twitter -->
                    <div class="col-md-4">
                        <label for="cafe_social_twitter" class="form-label">Twitter / X Link</label>
                        <input type="url" class="form-control" id="cafe_social_twitter" name="cafe_social_twitter" value="<?php echo escape($settings['cafe_social_twitter']); ?>">
                    </div>
                    
                    <!-- Google Maps URL -->
                    <div class="col-12">
                        <label for="cafe_google_maps" class="form-label">Google Maps Embed URL (Iframe Src)</label>
                        <textarea class="form-control" id="cafe_google_maps" name="cafe_google_maps" rows="3" placeholder="https://www.google.com/maps/embed?..."><?php echo escape($settings['cafe_google_maps']); ?></textarea>
                    </div>
                    
                    <!-- Save Buttons -->
                    <div class="col-12 mt-4 pt-2 border-top" style="border-color: #EFEAE0 !important;">
                        <button type="submit" class="btn btn-sage px-5 py-3 fw-bold">Save Website Configurations</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
