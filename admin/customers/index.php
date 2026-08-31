<?php
/**
 * admin/customers/index.php
 * Administrative Customer Directory - Lists registered and guest checkout profiles
 */

$page_title = 'Customer Directory';

require_once __DIR__ . '/../includes/header.php';
require_once __DIR__ . '/../includes/sidebar.php';

$db = get_db_connection();

$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$type_filter = isset($_GET['type']) ? trim($_GET['type']) : '';

// Query Customers list
$query = "SELECT * FROM customers WHERE 1=1";
$params = [];

if ($type_filter === 'registered') {
    $query .= " AND password_hash IS NOT NULL";
} elseif ($type_filter === 'guest') {
    $query .= " AND password_hash IS NULL";
}

if (!empty($search)) {
    $query .= " AND (name LIKE :search OR email LIKE :search_email OR phone LIKE :search_phone)";
    $params[':search'] = "%{$search}%";
    $params[':search_email'] = "%{$search}%";
    $params[':search_phone'] = "%{$search}%";
}

$query .= " ORDER BY id DESC LIMIT 100";

try {
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $customers = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Customers fetch fail: " . $e->getMessage());
    $customers = [];
}
?>

<div class="row g-4">
    <!-- Filter Header -->
    <div class="col-12">
        <div class="card admin-card p-4 shadow-sm bg-white">
            <form action="index.php" method="GET" class="row g-2 align-items-center">
                <!-- Search -->
                <div class="col-md-5">
                    <input type="text" class="form-control" name="search" placeholder="Search by name, email or phone..." value="<?php echo escape($search); ?>">
                </div>
                
                <!-- Type Filter -->
                <div class="col-md-4">
                    <select class="form-select" name="type">
                        <option value="">All Profiles</option>
                        <option value="registered" <?php echo $type_filter === 'registered' ? 'selected' : ''; ?>>Registered Members</option>
                        <option value="guest" <?php echo $type_filter === 'guest' ? 'selected' : ''; ?>>Guest Customers</option>
                    </select>
                </div>
                
                <!-- Buttons -->
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-sage w-100 py-2">Filter</button>
                    <?php if (!empty($search) || !empty($type_filter)): ?>
                        <a href="index.php" class="btn btn-light w-100 py-2 text-muted">Clear</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Table Directory -->
    <div class="col-12">
        <div class="card admin-card p-4 shadow-sm bg-white">
            <div class="table-responsive">
                <table class="table table-custom align-middle">
                    <thead>
                        <tr class="text-muted small uppercase">
                            <th>Customer Name</th>
                            <th>Contact Details</th>
                            <th>Fulfillment Address</th>
                            <th>Account Type</th>
                            <th>Registered On</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($customers)): ?>
                            <?php foreach ($customers as $cust): ?>
                                <tr>
                                    <td>
                                        <strong class="text-dark d-block"><?php echo escape($cust['name']); ?></strong>
                                    </td>
                                    <td>
                                        <small class="text-dark d-block"><i class="bi bi-envelope me-2"></i><?php echo escape($cust['email']); ?></small>
                                        <small class="text-muted d-block"><i class="bi bi-telephone me-2"></i><?php echo escape($cust['phone']); ?></small>
                                    </td>
                                    <td class="small text-muted" style="max-width: 250px;">
                                        <?php if (!empty($cust['address'])): ?>
                                            <span><?php echo escape($cust['address']); ?>, <?php echo escape($cust['city']); ?> - <?php echo escape($cust['zip']); ?></span>
                                        <?php else: ?>
                                            <span class="text-muted small italic">No saved address</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($cust['password_hash'])): ?>
                                            <span class="badge bg-success px-2 py-1 rounded">Registered Member</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary px-2 py-1 rounded">Guest Checkout</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?php echo date('M d, Y', strtotime($cust['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="text-center py-4 text-muted">No customer profiles found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
