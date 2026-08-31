<?php
/**
 * admin/qrcode/index.php
 * Table QR Code Generator & Acrylic Stand Print Designer
 */

$page_title = 'Table QR Code Generator';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';
require_once __DIR__ . '/../../includes/qrcode.php';

$cafe_name = !empty($settings['cafe_name']) ? $settings['cafe_name'] : 'Mellow & Meadow';
$base_url = !empty($settings['website_qr_url']) ? rtrim($settings['website_qr_url'], '/') : BASE_URL . '/menu.php';

$selected_table = isset($_GET['table']) ? (int)$_GET['table'] : 1;
if ($selected_table < 1) $selected_table = 1;

$table_url = $base_url . (strpos($base_url, '?') !== false ? '&' : '?') . 'table=' . $selected_table;
$qr_img_url = get_qr_image_url($table_url, 400);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 class="h3 fw-bold mb-1"><i class="bi bi-qr-code me-2 text-sage"></i>Table QR Code & Stand Designer</h2>
        <p class="text-muted mb-0">Generate contactless ordering QR codes and printable acrylic table stands for your café tables.</p>
    </div>
    <div class="d-flex gap-2">
        <button onclick="window.print()" class="btn btn-outline-dark"><i class="bi bi-printer me-1"></i>Print Stand</button>
        <a href="<?php echo escape($qr_img_url); ?>" download="Table-<?php echo $selected_table; ?>-QR.png" target="_blank" class="btn btn-sage text-white"><i class="bi bi-download me-1"></i>Download High-Res QR</a>
    </div>
</div>

<div class="row g-4">
    <!-- Controls Column -->
    <div class="col-lg-5 col-xl-4 no-print">
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-sliders me-2 text-sage"></i>Configure Table QR</h5>
            </div>
            <div class="card-body">
                <form method="GET" action="index.php" class="mb-3">
                    <label class="form-label fw-bold">Select Table Number</label>
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-light"><i class="bi bi-hash"></i></span>
                        <select name="table" class="form-select" onchange="this.form.submit()">
                            <?php for ($t = 1; $t <= 30; $t++): ?>
                                <option value="<?php echo $t; ?>" <?php echo ($selected_table === $t) ? 'selected' : ''; ?>>
                                    Table <?php echo sprintf('%02d', $t); ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                </form>

                <div class="mb-3">
                    <label class="form-label fw-bold text-muted small">Encoded Destination URL</label>
                    <div class="p-2 bg-light rounded border text-break small font-monospace">
                        <?php echo escape($table_url); ?>
                    </div>
                    <small class="text-muted mt-1 d-block">Customers scanning this QR will automatically have Table #<?php echo $selected_table; ?> assigned to their ordering cart.</small>
                </div>

                <hr>

                <h6 class="fw-bold mb-2">Quick Navigation</h6>
                <div class="d-flex flex-wrap gap-1 mb-3">
                    <?php for ($t = 1; $t <= 15; $t++): ?>
                        <a href="index.php?table=<?php echo $t; ?>" class="btn btn-sm <?php echo ($selected_table === $t) ? 'btn-sage text-white' : 'btn-outline-secondary'; ?>">
                            T-<?php echo sprintf('%02d', $t); ?>
                        </a>
                    <?php endfor; ?>
                </div>

                <div class="alert alert-info border-0 mb-0 small">
                    <i class="bi bi-info-circle me-1"></i><strong>Print Tip:</strong> Click the "Print Stand" button above to print a 4x6" table tent stand directly on your printer.
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3">
                <h5 class="fw-bold mb-0"><i class="bi bi-globe me-2 text-sage"></i>General Website QR</h5>
            </div>
            <div class="card-body text-center">
                <p class="text-muted small mb-3">Use this general QR code for menus, brochures, packaging bags, and Instagram banners (without a table number attached).</p>
                <img src="<?php echo escape(get_qr_image_url($base_url, 250)); ?>" alt="Website QR Code" class="img-fluid rounded border p-2 mb-3 bg-white" style="max-width: 180px;">
                <div>
                    <a href="<?php echo escape(get_qr_image_url($base_url, 600)); ?>" target="_blank" class="btn btn-outline-dark btn-sm"><i class="bi bi-download me-1"></i>Download Promo QR</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Table Stand Preview Column -->
    <div class="col-lg-7 col-xl-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center no-print">
                <h5 class="fw-bold mb-0"><i class="bi bi-eye me-2 text-sage"></i>Live Table Stand Preview</h5>
                <span class="badge bg-light text-dark border">Print Ready (4" x 6")</span>
            </div>
            <div class="card-body p-4 d-flex justify-content-center align-items-center bg-light">
                <!-- Acrylic Table Tent Card Layout -->
                <div class="table-stand-card shadow bg-white text-center p-4 border rounded-3" style="width: 340px; border: 2px solid #EFEAE0 !important;">
                    <div class="mb-3">
                        <i class="bi bi-cup-hot fs-2 text-sage d-block mb-1"></i>
                        <h4 class="display-font fw-bold mb-0" style="color: #8A6249; letter-spacing: 1px;"><?php echo escape($cafe_name); ?></h4>
                        <span class="text-muted small text-uppercase" style="letter-spacing: 2px; font-size: 0.75rem;">Specialty Café & Brunch</span>
                    </div>

                    <div class="p-3 my-3 rounded bg-white shadow-sm border d-inline-block">
                        <img src="<?php echo escape($qr_img_url); ?>" alt="Table QR Code" class="img-fluid" style="width: 200px; height: 200px; display: block;">
                    </div>

                    <div class="my-2">
                        <h5 class="fw-bold mb-1 text-dark" style="letter-spacing: 1.5px;">SCAN TO ORDER</h5>
                        <p class="text-muted small mb-2">Scan with your camera to view menu & place your order</p>
                    </div>

                    <div class="py-2 px-4 rounded-pill d-inline-block mb-3" style="background-color: #F7F3EA; border: 1px dashed #78906F;">
                        <span class="fw-bold" style="color: #292725; letter-spacing: 2px; font-size: 1.1rem;">TABLE <?php echo sprintf('%02d', $selected_table); ?></span>
                    </div>

                    <div class="border-top pt-2 mt-2">
                        <small class="text-muted" style="font-size: 0.7rem;">Free Wi-Fi: <strong><?php echo escape(str_replace(' ', '', $cafe_name)); ?>_Guest</strong></small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    .table-stand-card, .table-stand-card * {
        visibility: visible;
    }
    .table-stand-card {
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        box-shadow: none !important;
        border: 2px solid #333 !important;
        width: 380px !important;
    }
    .no-print {
        display: none !important;
    }
}
</style>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
