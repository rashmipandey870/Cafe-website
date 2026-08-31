<?php
/**
 * tools/database-check.php
 * Development Environment & Database Schema Diagnostics
 */

// 1. Strict Security Lock Verification
$lock_file = __DIR__ . '/../config/install.lock';
if (file_exists($lock_file)) {
    header('HTTP/1.1 403 Forbidden');
    http_response_code(403);
    die('<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden - Diagnostics Locked</title>
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <style>
            body { background-color: #FFFDF8; color: #292725; font-family: sans-serif; padding-top: 100px; }
            .error-card { max-width: 550px; margin: 0 auto; background: #F7F3EA; border: 1px solid #EFEAE0; border-radius: 12px; padding: 40px; text-align: center; }
            h1 { color: #8A6249; font-weight: bold; }
            p { color: #77716A; margin: 20px 0; }
            .btn-sage { background-color: #78906F; color: white; border: none; padding: 10px 24px; border-radius: 6px; text-decoration: none; }
            .btn-sage:hover { background-color: #63775c; color: white; }
        </style>
    </head>
    <body>
        <div class="error-card shadow-sm">
            <h1>403 Forbidden</h1>
            <p>Access Denied: Diagnostics tools are locked in production for system safety. If in development, please temporarily delete <code>config/install.lock</code>.</p>
            <a href="../index.php" class="btn btn-sage">Go to Home</a>
        </div>
    </body>
    </html>');
}

// Enable local reports output
$php_ok = version_compare(PHP_VERSION, '8.0.0', '>=');
$pdo_ok = class_exists('PDO');
$pdo_mysql_ok = $pdo_ok && in_array('mysql', PDO::getAvailableDrivers());

$config_loaded = false;
$db_connected = false;
$conn_error = '';

$host = '';
$port = '';
$dbname = '';
$username = '';

$config_file = __DIR__ . '/../config/db_config.php';
if (file_exists($config_file)) {
    require_once $config_file;
    $config_loaded = true;
    
    $host = defined('DB_HOST') ? DB_HOST : '';
    $port = defined('DB_PORT') ? DB_PORT : '3306';
    $dbname = defined('DB_NAME') ? DB_NAME : '';
    $username = defined('DB_USER') ? DB_USER : '';
}

$tables = [
    'users' => false,
    'customers' => false,
    'categories' => false,
    'menu_items' => false,
    'orders' => false,
    'order_items' => false,
    'reservations' => false,
    'promotions' => false,
    'promotion_products' => false,
    'promotion_categories' => false,
    'reviews' => false,
    'gallery' => false,
    'contact_messages' => false,
    'settings' => false
];

if ($config_loaded && $pdo_mysql_ok) {
    try {
        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
        $pdo = new PDO($dsn, $username, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_TIMEOUT => 3
        ]);
        $db_connected = true;
        
        // Scan active tables in database
        $stmt = $pdo->query("SHOW TABLES");
        $db_tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($tables as $t_name => $status) {
            if (in_array($t_name, $db_tables)) {
                $tables[$t_name] = true;
            }
        }
    } catch (PDOException $e) {
        $conn_error = $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mellow & Meadow — DB Diagnostics</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body { background-color: #FFFDF8; color: #292725; padding: 50px 20px; font-family: system-ui, -apple-system, sans-serif; }
        .diagnostics-card { max-width: 700px; margin: 0 auto; background: #FAF7F0; border: 1px solid #EFEAE0; border-radius: 12px; padding: 40px; }
        h1 { color: #8A6249; font-weight: bold; margin-bottom: 25px; border-bottom: 2px solid #EFEAE0; padding-bottom: 15px; }
        .status-badge { font-weight: bold; }
        .table-check { font-family: monospace; }
    </style>
</head>
<body>
    <div class="diagnostics-card shadow-sm">
        <h1><i class="bi bi-shield-check me-2 text-success"></i>System & DB Diagnostics</h1>
        
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <h5 class="fw-bold">PHP & Extensions Environment</h5>
                <ul class="list-group">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        PHP Version: <?php echo PHP_VERSION; ?>
                        <span class="badge <?php echo $php_ok ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                            <?php echo $php_ok ? '✓ OK' : '✗ Upgrade Required'; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        PDO Extension
                        <span class="badge <?php echo $pdo_ok ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                            <?php echo $pdo_ok ? '✓ Available' : '✗ Missing'; ?>
                        </span>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        PDO MySQL Driver
                        <span class="badge <?php echo $pdo_mysql_ok ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                            <?php echo $pdo_mysql_ok ? '✓ Enabled' : '✗ Disabled'; ?>
                        </span>
                    </li>
                </ul>
            </div>
            
            <div class="col-md-6 mb-3">
                <h5 class="fw-bold">Database Server Parameter Configs</h5>
                <ul class="list-group">
                    <li class="list-group-item">Host: <strong><?php echo htmlspecialchars($host ?: '—'); ?></strong></li>
                    <li class="list-group-item">Port: <strong><?php echo htmlspecialchars($port ?: '—'); ?></strong></li>
                    <li class="list-group-item">Database: <strong><?php echo htmlspecialchars($dbname ?: '—'); ?></strong></li>
                    <li class="list-group-item">User: <strong><?php echo htmlspecialchars($username ?: '—'); ?></strong></li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        Connection Status
                        <span class="badge <?php echo $db_connected ? 'bg-success' : 'bg-danger'; ?> rounded-pill">
                            <?php echo $db_connected ? '✓ Connected' : '✗ Failed'; ?>
                        </span>
                    </li>
                </ul>
            </div>
        </div>

        <?php if (!$db_connected && $conn_error): ?>
            <div class="alert alert-danger mb-4">
                <h6 class="fw-bold"><i class="bi bi-bug-fill me-1"></i>Database Connection Error:</h6>
                <p class="mb-0 small"><?php echo htmlspecialchars($conn_error); ?></p>
            </div>
        <?php endif; ?>

        <h5 class="fw-bold mb-3">Required Database Tables Check</h5>
        <div class="row g-2 table-check">
            <?php foreach ($tables as $t_name => $exists): ?>
                <div class="col-sm-6 col-md-4">
                    <div class="p-2 border rounded bg-white d-flex justify-content-between align-items-center">
                        <span><?php echo $t_name; ?></span>
                        <span class="badge <?php echo $exists ? 'bg-success' : 'bg-danger'; ?>">
                            <?php echo $exists ? '✓' : '✗'; ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="mt-4 pt-3 border-top text-center">
            <a href="../index.php" class="btn btn-outline-secondary px-4"><i class="bi bi-house-door me-2"></i>Go to Home</a>
        </div>
    </div>
</body>
</html>
