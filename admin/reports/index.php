<?php
/**
 * admin/reports/index.php
 * Administrative Reports Dashboard - Revenue calculations, date filters, and popular items list
 */

$page_title = 'Sales Reports';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/sidebar.php';

$db = get_db_connection();

// 1. Resolve Date Range Filters (Default: Current Month)
$filter_type = isset($_GET['range']) ? trim($_GET['range']) : 'this_month';
$start_date = isset($_GET['start_date']) ? trim($_GET['start_date']) : '';
$end_date = isset($_GET['end_date']) ? trim($_GET['end_date']) : '';

$today = date('Y-m-d');

switch ($filter_type) {
    case 'today':
        $start_date = $today;
        $end_date = $today;
        break;
    case 'yesterday':
        $start_date = date('Y-m-d', strtotime('-1 day'));
        $end_date = date('Y-m-d', strtotime('-1 day'));
        break;
    case 'this_week':
        $start_date = date('Y-m-d', strtotime('monday this week'));
        $end_date = $today;
        break;
    case 'this_month':
    default:
        $start_date = date('Y-m-01'); // First day of current month
        $end_date = $today;
        $filter_type = 'this_month'; // override default value
        break;
    case 'custom':
        if (empty($start_date)) $start_date = date('Y-m-01');
        if (empty($end_date)) $end_date = $today;
        break;
}

// Convert boundaries to datetime format
$start_datetime = $start_date . ' 00:00:00';
$end_datetime = $end_date . ' 23:59:59';

// 2. Query KPIs (excluding cancelled orders for financial totals)
$kpis = [
    'orders_count' => 0,
    'gross_revenue' => 0.00,
    'discounts' => 0.00,
    'taxes' => 0.00,
    'delivery' => 0.00
];

try {
    // Total Orders count
    $stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE created_at >= :start AND created_at <= :end");
    $stmt->execute([':start' => $start_datetime, ':end' => $end_datetime]);
    $kpis['orders_count'] = (int)$stmt->fetchColumn();
    
    // Revenue calculations (excluding Cancelled)
    $stmt = $db->prepare("SELECT 
                            IFNULL(SUM(total_amount), 0.00) as gross, 
                            IFNULL(SUM(discount_amount), 0.00) as disc, 
                            IFNULL(SUM(tax_amount), 0.00) as tax,
                            IFNULL(SUM(delivery_charge), 0.00) as deliv
                          FROM orders 
                          WHERE order_status != 'cancelled' 
                          AND created_at >= :start AND created_at <= :end");
    $stmt->execute([':start' => $start_datetime, ':end' => $end_datetime]);
    $financials = $stmt->fetch();
    if ($financials) {
        $kpis['gross_revenue'] = (float)$financials['gross'];
        $kpis['discounts'] = (float)$financials['disc'];
        $kpis['taxes'] = (float)$financials['tax'];
        $kpis['delivery'] = (float)$financials['deliv'];
    }
} catch (PDOException $e) {
    error_log("Reports KPI query error: " . $e->getMessage());
}

// Calculate Net Sales (subtotal after discounts, excluding taxes and logistics)
$net_sales = $kpis['gross_revenue'] - $kpis['taxes'] - $kpis['delivery'];
if ($net_sales < 0) $net_sales = 0.00;

// 3. Query Popular Items
$popular_items = [];
try {
    $stmt = $db->prepare("SELECT item_name, SUM(quantity) as total_qty, SUM(subtotal) as total_sales 
                          FROM order_items oi 
                          JOIN orders o ON oi.order_id = o.id 
                          WHERE o.order_status != 'cancelled' 
                          AND o.created_at >= :start AND o.created_at <= :end 
                          GROUP BY oi.menu_item_id, oi.item_name 
                          ORDER BY total_qty DESC LIMIT 5");
    $stmt->execute([':start' => $start_datetime, ':end' => $end_datetime]);
    $popular_items = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Popular items query error: " . $e->getMessage());
}

// 4. Query Order status breakdown
$status_breakdown = [];
try {
    $stmt = $db->prepare("SELECT order_status, COUNT(*) as count 
                          FROM orders 
                          WHERE created_at >= :start AND created_at <= :end 
                          GROUP BY order_status");
    $stmt->execute([':start' => $start_datetime, ':end' => $end_datetime]);
    $status_breakdown = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
} catch (PDOException $e) {
    error_log("Status breakdown query error: " . $e->getMessage());
}
?>

<!-- Filter Panel -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card admin-card p-4 shadow-sm bg-white">
            <form action="index.php" method="GET" class="row g-3 align-items-end">
                <input type="hidden" name="range" value="custom">
                
                <!-- Quick range toggles -->
                <div class="col-md-5">
                    <label class="form-label fw-bold text-dark small">Quick Date Presets</label>
                    <div class="d-flex flex-wrap gap-2">
                        <a href="index.php?range=today" class="btn btn-sm btn-outline-sage <?php echo $filter_type === 'today' ? 'active' : ''; ?>">Today</a>
                        <a href="index.php?range=yesterday" class="btn btn-sm btn-outline-sage <?php echo $filter_type === 'yesterday' ? 'active' : ''; ?>">Yesterday</a>
                        <a href="index.php?range=this_week" class="btn btn-sm btn-outline-sage <?php echo $filter_type === 'this_week' ? 'active' : ''; ?>">This Week</a>
                        <a href="index.php?range=this_month" class="btn btn-sm btn-outline-sage <?php echo $filter_type === 'this_month' ? 'active' : ''; ?>">This Month</a>
                    </div>
                </div>
                
                <!-- Custom dates input -->
                <div class="col-md-3 col-6">
                    <label for="start_date" class="form-label mb-1 small fw-bold text-dark">Start Date</label>
                    <input type="date" class="form-control form-control-sm" id="start_date" name="start_date" value="<?php echo escape($start_date); ?>">
                </div>
                
                <div class="col-md-3 col-6">
                    <label for="end_date" class="form-label mb-1 small fw-bold text-dark">End Date</label>
                    <input type="date" class="form-control form-control-sm" id="end_date" name="end_date" value="<?php echo escape($end_date); ?>">
                </div>
                
                <!-- Submit -->
                <div class="col-md-1 col-12 text-end">
                    <button type="submit" class="btn btn-sage btn-sm w-100 py-2"><i class="bi bi-filter"></i></button>
                </div>
            </form>
            
            <div class="text-muted mt-3 small">
                Showing data from: <strong><?php echo date('F d, Y', strtotime($start_date)); ?></strong> to <strong><?php echo date('F d, Y', strtotime($end_date)); ?></strong>
            </div>
        </div>
    </div>
</div>

<!-- Sales Statistics KPI Row -->
<div class="row g-4 mb-5">
    <!-- Card 1: Gross Sales -->
    <div class="col-sm-6 col-lg-3">
        <div class="card admin-card p-4 shadow-sm h-100">
            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Gross Revenue</small>
            <div class="stat-value text-dark"><?php echo format_price($kpis['gross_revenue']); ?></div>
            <small class="text-muted mt-2 d-block">Total processed cash flow</small>
        </div>
    </div>
    
    <!-- Card 2: Total Orders -->
    <div class="col-sm-6 col-lg-3">
        <div class="card admin-card p-4 shadow-sm h-100">
            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Orders Placed</small>
            <div class="stat-value text-dark"><?php echo $kpis['orders_count']; ?></div>
            <small class="text-muted mt-2 d-block">Includes cancelled orders</small>
        </div>
    </div>
    
    <!-- Card 3: Discounts Applied -->
    <div class="col-sm-6 col-lg-3">
        <div class="card admin-card p-4 shadow-sm h-100">
            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Discounts Applied</small>
            <div class="stat-value text-danger">-<?php echo format_price($kpis['discounts']); ?></div>
            <small class="text-muted mt-2 d-block">Promo campaigns value</small>
        </div>
    </div>
    
    <!-- Card 4: Net Product Revenue -->
    <div class="col-sm-6 col-lg-3">
        <div class="card admin-card p-4 shadow-sm h-100">
            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.75rem; letter-spacing: 1px;">Net Product Sales</small>
            <div class="stat-value text-success" style="color: var(--accent-sage) !important;"><?php echo format_price($net_sales); ?></div>
            <small class="text-muted mt-2 d-block">Excludes tax & delivery fees</small>
        </div>
    </div>
</div>

<!-- Details Columns -->
<div class="row g-4">
    <!-- Popular Items table list -->
    <div class="col-lg-7">
        <div class="card admin-card p-4 shadow-sm bg-white h-100">
            <h3 class="display-font text-dark fs-4 mb-4">Most Popular Items</h3>
            
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                        <tr class="text-muted small uppercase">
                            <th>Item Name</th>
                            <th class="text-center">Quantity Sold</th>
                            <th class="text-end">Revenue contribution</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($popular_items)): ?>
                            <?php foreach ($popular_items as $item): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark"><?php echo escape($item['item_name']); ?></strong>
                                    </td>
                                    <td class="text-center fw-bold text-dark">
                                        <?php echo (int)$item['total_qty']; ?>
                                    </td>
                                    <td class="text-end text-muted">
                                        <?php echo format_price($item['total_sales']); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="3" class="text-center py-4 text-muted">No items sold within this date range.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Fulfillment status breakdown chart stats -->
    <div class="col-lg-5">
        <div class="card admin-card p-4 shadow-sm bg-white h-100">
            <h3 class="display-font text-dark fs-4 mb-4">Fulfillment Status</h3>
            
            <ul class="list-group list-group-flush bg-transparent">
                <?php 
                    $states = [
                        'pending'          => 'Pending Inquiries',
                        'confirmed'        => 'Confirmed Orders',
                        'preparing'        => 'In Kitchen Preparation',
                        'ready'            => 'Ready at Counter',
                        'out_for_delivery' => 'Out for Delivery',
                        'completed'        => 'Completed Transactions',
                        'cancelled'        => 'Cancelled Orders'
                    ];
                    foreach ($states as $key => $label):
                        $count = isset($status_breakdown[$key]) ? (int)$status_breakdown[$key] : 0;
                ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center bg-transparent border-0 px-0 py-2">
                        <span class="text-capitalize small text-dark fw-medium"><?php echo $label; ?></span>
                        <span class="badge bg-secondary rounded-pill px-3 py-2 fw-bold" style="background-color: var(--bg-secondary) !important; color: var(--color-text) !important;">
                            <?php echo $count; ?>
                        </span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
