<?php
/**
 * admin/settings/index.php
 * Comprehensive Website Settings Center - Organized Tabs for Profile, Razorpay, Google Maps, QR, Delivery & Taxes
 */

$page_title = 'Website Settings';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();
$errors = [];

// Handle Form POST Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    enforce_csrf();
    
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
        'cafe_google_maps_api_key',
        'cafe_latitude',
        'cafe_longitude',
        'cafe_about_text',
        'cafe_timezone',
        'payment_gateway_enabled',
        'payment_gateway_mode',
        'razorpay_key_id',
        'merchant_upi_id',
        'merchant_upi_name',
        'table_ordering_enabled',
        'website_qr_url',
        'delivery_enabled',
        'delivery_charge',
        'free_delivery_above',
        'minimum_delivery_order',
        'tax_enabled',
        'tax_rate'
    ];
    
    // Check if a new logo file is being uploaded
    if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
        $uploaded = upload_image($_FILES['logo_image'], 'assets/images/', 1048576); // Max 1MB
        if ($uploaded) {
            $logo_path = $uploaded;
            $old_logo = isset($settings['cafe_logo']) ? $settings['cafe_logo'] : '';
            if (!empty($old_logo) && file_exists(__DIR__ . '/../../' . $old_logo) && strpos($old_logo, 'placeholder') === false) {
                @unlink(__DIR__ . '/../../' . $old_logo);
            }
            
            try {
                $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES ('cafe_logo', :val) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
                $stmt->execute([':val' => $logo_path]);
            } catch (PDOException $e) {
                error_log("Logo Update Exception: " . $e->getMessage());
            }
        } else {
            $errors[] = "Failed to upload logo image. Must be a valid JPG/PNG/WEBP under 1MB.";
        }
    }
    
    // Loop through setting fields and update
    if (empty($errors)) {
        try {
            $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (:key, :val) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
            
            foreach ($allowed_setting_keys as $key) {
                $val = '';
                
                // Toggle Switches mapping
                if (in_array($key, ['delivery_enabled', 'tax_enabled', 'payment_gateway_enabled', 'table_ordering_enabled'])) {
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
            
            set_flash_message('success', 'Website settings saved successfully!');
            header("Location: index.php");
            exit;
        } catch (PDOException $e) {
            error_log("Settings Update Exception: " . $e->getMessage());
            $errors[] = "Failed to write updates to database.";
        }
    }
}
?>

<div class="row">
    <div class="col-12 col-xl-11">
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger mb-4">
                <ul class="mb-0">
                    <?php foreach ($errors as $err): ?>
                        <li><?php echo escape($err); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="index.php" method="POST" enctype="multipart/form-data">
            <?php echo csrf_field(); ?>

            <!-- Navigation Tabs -->
            <ul class="nav nav-pills mb-4 gap-2 flex-wrap" id="settingsTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active fw-bold" id="profile-tab" data-bs-toggle="pill" data-bs-target="#tab-profile" type="button" role="tab"><i class="bi bi-shop me-1"></i>Business Profile</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="payments-tab" data-bs-toggle="pill" data-bs-target="#tab-payments" type="button" role="tab"><i class="bi bi-credit-card-2-front me-1"></i>Payments & Razorpay</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="maps-tab" data-bs-toggle="pill" data-bs-target="#tab-maps" type="button" role="tab"><i class="bi bi-geo-alt-fill me-1"></i>Google Maps</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="qrcode-tab" data-bs-toggle="pill" data-bs-target="#tab-qrcode" type="button" role="tab"><i class="bi bi-qr-code me-1"></i>QR & Table Ordering</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="delivery-tab" data-bs-toggle="pill" data-bs-target="#tab-delivery" type="button" role="tab"><i class="bi bi-bicycle me-1"></i>Delivery & Logistics</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="tax-tab" data-bs-toggle="pill" data-bs-target="#tab-tax" type="button" role="tab"><i class="bi bi-receipt-cutoff me-1"></i>Taxes (GST)</button>
                </li>
                <li class="nav-item">
                    <button class="nav-link fw-bold" id="social-tab" data-bs-toggle="pill" data-bs-target="#tab-social" type="button" role="tab"><i class="bi bi-share me-1"></i>Social Media</button>
                </li>
            </ul>

            <div class="tab-content" id="settingsTabsContent">
                
                <!-- TAB 1: Business Profile -->
                <div class="tab-pane fade show active" id="tab-profile" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark">Café Profile & Information</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Café Name <span class="text-danger">*</span></label>
                                    <input type="text" name="cafe_name" class="form-control" value="<?php echo escape($settings['cafe_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Contact Email <span class="text-danger">*</span></label>
                                    <input type="email" name="cafe_email" class="form-control" value="<?php echo escape($settings['cafe_email']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Phone Number <span class="text-danger">*</span></label>
                                    <input type="text" name="cafe_phone" class="form-control" value="<?php echo escape($settings['cafe_phone']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">WhatsApp Orders Contact</label>
                                    <input type="text" name="cafe_whatsapp" class="form-control" value="<?php echo escape($settings['cafe_whatsapp']); ?>" placeholder="+919876543210">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">Physical Address <span class="text-danger">*</span></label>
                                    <input type="text" name="cafe_address" class="form-control" value="<?php echo escape($settings['cafe_address']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Opening Hours</label>
                                    <input type="text" name="cafe_opening_hours" class="form-control" value="<?php echo escape($settings['cafe_opening_hours']); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Café Local Timezone <span class="text-danger">*</span></label>
                                    <select name="cafe_timezone" class="form-select">
                                        <?php 
                                        $common_timezones = [
                                            'Asia/Kolkata' => 'Asia/Kolkata (IST +5:30) - India',
                                            'Asia/Dubai' => 'Asia/Dubai (GST +4:00) - UAE',
                                            'Asia/Singapore' => 'Asia/Singapore (SGT +8:00)',
                                            'Europe/London' => 'Europe/London (GMT/BST)',
                                            'America/New_York' => 'America/New_York (EST/EDT)'
                                        ];
                                        foreach ($common_timezones as $tz_id => $tz_label): ?>
                                            <option value="<?php echo $tz_id; ?>" <?php echo ($settings['cafe_timezone'] === $tz_id) ? 'selected' : ''; ?>>
                                                <?php echo $tz_label; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold">About Us Story</label>
                                    <textarea name="cafe_about_text" class="form-control" rows="3"><?php echo escape($settings['cafe_about_text']); ?></textarea>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Café Brand Logo</label>
                                    <input type="file" name="logo_image" class="form-control" accept="image/*">
                                    <small class="text-muted">PNG, JPG, or WEBP under 1MB. Recommended size: 200x60px.</small>
                                </div>
                                <div class="col-md-6 d-flex align-items-center">
                                    <?php if (!empty($settings['cafe_logo']) && file_exists(__DIR__ . '/../../' . $settings['cafe_logo'])): ?>
                                        <div class="p-2 border rounded bg-light mt-3">
                                            <img src="<?php echo BASE_URL . '/' . escape($settings['cafe_logo']); ?>" alt="Current Logo" style="max-height: 45px;">
                                            <span class="small text-muted ms-2">Current Active Logo</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: Payments & Razorpay -->
                <div class="tab-pane fade" id="tab-payments" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark">Indian Payment Integration (Razorpay & UPI)</h5>
                        </div>
                        <div class="card-body p-4">
                            
                            <div class="form-check form-switch mb-4 p-3 bg-light rounded border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="payment_gateway_enabled" name="payment_gateway_enabled" value="1" <?php echo ($settings['payment_gateway_enabled'] == '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold" for="payment_gateway_enabled">
                                    Enable Online Payments (Razorpay & UPI)
                                </label>
                                <div class="small text-muted ps-4">When enabled, customers can pay online via UPI (GPay, PhonePe, Paytm), Debit/Credit Cards, and Net Banking.</div>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Payment Gateway Environment</label>
                                    <select name="payment_gateway_mode" class="form-select">
                                        <option value="test" <?php echo ($settings['payment_gateway_mode'] === 'test') ? 'selected' : ''; ?>>Test Mode (Sandbox / Development)</option>
                                        <option value="live" <?php echo ($settings['payment_gateway_mode'] === 'live') ? 'selected' : ''; ?>>Live Mode (Production Real Payments)</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Razorpay Key ID</label>
                                    <input type="text" name="razorpay_key_id" class="form-control font-monospace" value="<?php echo escape($settings['razorpay_key_id']); ?>" placeholder="rzp_test_... or rzp_live_...">
                                    <small class="text-muted">Public Key ID provided in the Razorpay Merchant Dashboard.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Merchant UPI ID (For Direct UPI QR)</label>
                                    <input type="text" name="merchant_upi_id" class="form-control" value="<?php echo escape($settings['merchant_upi_id']); ?>" placeholder="e.g. yourcafe@upi or 9876543210@paytm">
                                    <small class="text-muted">Used to generate dynamic NPCI UPI payment QR codes.</small>
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label fw-bold">Merchant Display Name</label>
                                    <input type="text" name="merchant_upi_name" class="form-control" value="<?php echo escape($settings['merchant_upi_name']); ?>" placeholder="Mellow & Meadow Cafe">
                                    <small class="text-muted">Business name displayed on customer's UPI app (GPay/PhonePe).</small>
                                </div>
                            </div>

                            <div class="alert alert-warning border-0 mt-4 small">
                                <i class="bi bi-shield-lock-fill me-1"></i><strong>Security Note on Razorpay Secret Key:</strong> For maximum security, your private <code>RAZORPAY_KEY_SECRET</code> is configured on the server in <code>config/db_config.php</code> and is never stored in public HTML or exposed through AJAX APIs.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 3: Google Maps -->
                <div class="tab-pane fade" id="tab-maps" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark">Google Maps Location Integration</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-bold">Google Maps Embed URL / Iframe Source</label>
                                    <textarea name="cafe_google_maps" class="form-control font-monospace" rows="3" placeholder="https://www.google.com/maps/embed?pb=..."><?php echo escape($settings['cafe_google_maps']); ?></textarea>
                                    <small class="text-muted">Open Google Maps, search your café, click <strong>Share → Embed a map</strong>, and copy the <code>src="..."</code> URL here.</small>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Latitude (Optional)</label>
                                    <input type="text" name="cafe_latitude" class="form-control" value="<?php echo escape($settings['cafe_latitude'] ?? ''); ?>" placeholder="28.5355161">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Longitude (Optional)</label>
                                    <input type="text" name="cafe_longitude" class="form-control" value="<?php echo escape($settings['cafe_longitude'] ?? ''); ?>" placeholder="77.1994537">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Google Maps API Key (Optional)</label>
                                    <input type="text" name="cafe_google_maps_api_key" class="form-control" value="<?php echo escape($settings['cafe_google_maps_api_key'] ?? ''); ?>" placeholder="AIzaSy...">
                                    <small class="text-muted">Only required if using custom JavaScript map markers.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 4: QR & Table Ordering -->
                <div class="tab-pane fade" id="tab-qrcode" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark">Contactless Table Ordering & QR Codes</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="form-check form-switch mb-4 p-3 bg-light rounded border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="table_ordering_enabled" name="table_ordering_enabled" value="1" <?php echo ($settings['table_ordering_enabled'] == '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold" for="table_ordering_enabled">
                                    Enable Contactless Table Ordering (Dine-In)
                                </label>
                                <div class="small text-muted ps-4">When enabled, customers can scan table QR codes to place dine-in orders directly from their tables.</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Base Website / Menu URL</label>
                                <input type="url" name="website_qr_url" class="form-control" value="<?php echo escape($settings['website_qr_url']); ?>" placeholder="<?php echo BASE_URL; ?>/menu.php">
                                <small class="text-muted">Base URL encoded inside your QR codes. Leave empty to use current domain.</small>
                            </div>

                            <div class="mt-3">
                                <a href="../qrcode/index.php" class="btn btn-outline-sage"><i class="bi bi-printer me-2"></i>Open Table QR Stand Designer & Print Hub</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 5: Delivery & Logistics -->
                <div class="tab-pane fade" id="tab-delivery" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark">Delivery Policies & Fees</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="form-check form-switch mb-4 p-3 bg-light rounded border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="delivery_enabled" name="delivery_enabled" value="1" <?php echo ($settings['delivery_enabled'] == '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold" for="delivery_enabled">
                                    Accept Home Delivery Orders
                                </label>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Standard Delivery Fee (₹)</label>
                                    <input type="number" step="0.50" min="0" name="delivery_charge" class="form-control" value="<?php echo escape($settings['delivery_charge']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Free Delivery Threshold (₹)</label>
                                    <input type="number" step="1.00" min="0" name="free_delivery_above" class="form-control" value="<?php echo escape($settings['free_delivery_above']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold">Minimum Delivery Order (₹)</label>
                                    <input type="number" step="1.00" min="0" name="minimum_delivery_order" class="form-control" value="<?php echo escape($settings['minimum_delivery_order']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 6: Taxes (GST) -->
                <div class="tab-pane fade" id="tab-tax" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark">Taxation System (GST / VAT)</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="form-check form-switch mb-4 p-3 bg-light rounded border">
                                <input class="form-check-input ms-0 me-3" type="checkbox" role="switch" id="tax_enabled" name="tax_enabled" value="1" <?php echo ($settings['tax_enabled'] == '1') ? 'checked' : ''; ?>>
                                <label class="form-check-label fw-bold" for="tax_enabled">
                                    Charge Sales Tax / GST on Orders
                                </label>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-bold">Tax Rate Percentage (%)</label>
                                <div class="input-group">
                                    <input type="number" step="0.10" min="0" max="100" name="tax_rate" class="form-control" value="<?php echo escape($settings['tax_rate']); ?>">
                                    <span class="input-group-text">%</span>
                                </div>
                                <small class="text-muted">Standard café GST rate in India is typically 5.00%.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- TAB 7: Social Media -->
                <div class="tab-pane fade" id="tab-social" role="tabpanel">
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-white py-3">
                            <h5 class="fw-bold mb-0 text-dark">Social Media Profiles</h5>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold"><i class="bi bi-instagram me-1 text-danger"></i>Instagram URL</label>
                                    <input type="url" name="cafe_social_instagram" class="form-control" value="<?php echo escape($settings['cafe_social_instagram']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold"><i class="bi bi-facebook me-1 text-primary"></i>Facebook URL</label>
                                    <input type="url" name="cafe_social_facebook" class="form-control" value="<?php echo escape($settings['cafe_social_facebook']); ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold"><i class="bi bi-twitter me-1 text-info"></i>Twitter URL</label>
                                    <input type="url" name="cafe_social_twitter" class="form-control" value="<?php echo escape($settings['cafe_social_twitter']); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <div class="mb-5">
                <button type="submit" class="btn btn-sage text-white px-5 py-2 fw-bold"><i class="bi bi-check2-circle me-2"></i>Save All Settings</button>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
