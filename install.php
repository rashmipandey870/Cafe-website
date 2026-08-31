<?php
/**
 * install.php
 * Web-based Database Configuration & Schema Installer
 */

// 1. Strict Security Lock Verification
$lock_file = __DIR__ . '/config/install.lock';
if (file_exists($lock_file)) {
    header('HTTP/1.1 403 Forbidden');
    http_response_code(403);
    die('<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>403 Forbidden - Installer Locked</title>
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
            <p>Access Denied: The web installation wizard has been locked for security. To reconfigure database settings, please delete the lock file <code>config/install.lock</code> manually from the server filesystem.</p>
            <a href="index.php" class="btn btn-sage">Go to Home</a>
        </div>
    </body>
    </html>');
}

$error = null;
$success = false;

$host = '127.0.0.1';
$port = '3306';
$dbname = 'cafe_db';
$username = 'root';
$password = '';

// Handle setup form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim(filter_input(INPUT_POST, 'db_host', FILTER_DEFAULT));
    $port = trim(filter_input(INPUT_POST, 'db_port', FILTER_DEFAULT));
    $dbname = trim(filter_input(INPUT_POST, 'db_name', FILTER_DEFAULT));
    $username = trim(filter_input(INPUT_POST, 'db_user', FILTER_DEFAULT));
    $password = $_POST['db_pass'] ?? ''; // Keep raw password to support special characters

    if (empty($host) || empty($port) || empty($dbname) || empty($username)) {
        $error = "All fields except password are required.";
    } else {
        try {
            // Step 1: Connect to server without database first to check credentials & server availability
            $dsn_no_db = "mysql:host={$host};port={$port};charset=utf8mb4";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5
            ];
            
            try {
                $pdo = new PDO($dsn_no_db, $username, $password, $options);
            } catch (PDOException $conn_err) {
                $err_code = $conn_err->getCode();
                $err_msg = $conn_err->getMessage();
                
                if ($err_code === 1045 || strpos($err_msg, 'Access denied') !== false) {
                    throw new Exception("Incorrect database username or password.");
                } elseif ($err_code === 2002 || strpos($err_msg, 'Connection refused') !== false || strpos($err_msg, 'getaddrinfo') !== false) {
                    throw new Exception("Could not reach database server host. Make sure the Host address, IP, and Port are correct and active.");
                } else {
                    throw new Exception("Database server connection failed: " . strip_tags($err_msg));
                }
            }

            // Step 2: Check if database exists
            $db_exists = false;
            try {
                $stmt = $pdo->query("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = " . $pdo->quote($dbname));
                if ($stmt->fetch()) {
                    $db_exists = true;
                }
            } catch (PDOException $sch_err) {
                // If query fails, proceed and assume database does not exist
            }

            // Step 3: Try to create database if missing
            if (!$db_exists) {
                try {
                    $pdo->exec("CREATE DATABASE `{$dbname}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                } catch (PDOException $create_err) {
                    throw new Exception("Database '{$dbname}' does not exist and the supplied user '{$username}' does not have permission to create it. Please create the database manually inside cPanel or phpMyAdmin and run the installer again.");
                }
            }

            // Step 4: Reconnect directly to target database
            $dsn_with_db = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
            $pdo = new PDO($dsn_with_db, $username, $password, $options);

            // Step 5: Read and Parse SQL Schema file
            $sql_file = __DIR__ . '/database/cafe_database.sql';
            if (!file_exists($sql_file)) {
                throw new Exception("Master database schema file not found at <code>database/cafe_database.sql</code>. Please restore the SQL file to the directory.");
            }

            $sql_content = file_get_contents($sql_file);
            if ($sql_content === false) {
                throw new Exception("Failed to read schema SQL contents.");
            }

            // Clean comments and build standalone statements
            $sql_content = preg_replace('/--(.*)\n/', '', $sql_content);
            $sql_content = preg_replace('/\/\*(.*)\*\//s', '', $sql_content);

            $queries = [];
            $current_query = '';
            $in_string = false;
            $string_char = '';
            $len = strlen($sql_content);

            for ($i = 0; $i < $len; $i++) {
                $char = $sql_content[$i];

                if (($char === "'" || $char === '"') && ($i === 0 || $sql_content[$i-1] !== '\\')) {
                    if ($in_string && $char === $string_char) {
                        $in_string = false;
                    } elseif (!$in_string) {
                        $in_string = true;
                        $string_char = $char;
                    }
                }

                if ($char === ';' && !$in_string) {
                    $trimmed = trim($current_query);
                    if (!empty($trimmed)) {
                        $queries[] = $trimmed;
                    }
                    $current_query = '';
                } else {
                    $current_query .= $char;
                }
            }
            $trimmed = trim($current_query);
            if (!empty($trimmed)) {
                $queries[] = $trimmed;
            }

            // Step 6: Execute SQL statements sequentially
            try {
                foreach ($queries as $query) {
                    $pdo->exec($query);
                }
            } catch (Exception $exec_err) {
                throw new Exception("Failed to import database tables. Check permissions or schema. SQL details: " . $exec_err->getMessage());
            }

            // Step 7: Write Config credentials File
            $config_dir = __DIR__ . '/config';
            if (!is_dir($config_dir)) {
                mkdir($config_dir, 0755, true);
            }

            $config_content = "<?php\n"
                            . "/**\n"
                            . " * config/db_config.php\n"
                            . " * Dynamically generated database configuration secrets.\n"
                            . " */\n\n"
                            . "define('DB_HOST', '" . addslashes($host) . "');\n"
                            . "define('DB_PORT', '" . addslashes($port) . "');\n"
                            . "define('DB_USER', '" . addslashes($username) . "');\n"
                            . "define('DB_PASS', '" . addslashes($password) . "');\n"
                            . "define('DB_NAME', '" . addslashes($dbname) . "');\n";

            if (file_put_contents($config_dir . '/db_config.php', $config_content) === false) {
                throw new Exception("Database connection verified, but failed to write configuration file to <code>config/db_config.php</code>. Check write permissions on the config folder.");
            }

            // Step 8: Write Installation lock file
            if (file_put_contents($lock_file, date('Y-m-d H:i:s')) === false) {
                throw new Exception("Configuration file written, but failed to write lock file to <code>config/install.lock</code>. Check write permissions.");
            }

            $success = true;
        } catch (Exception $e) {
            // Clean up config files if transaction fails mid-execution
            @unlink(__DIR__ . '/config/db_config.php');
            $error = $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mellow & Meadow Café — Installer</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-sage: #78906F;
            --primary-charcoal: #292725;
            --bg-cream: #FFFDF8;
            --card-cream: #FAF7F0;
            --border-beige: #EFEAE0;
            --accent-terracotta: #8A6249;
        }
        body {
            background-color: var(--bg-cream);
            color: var(--primary-charcoal);
            font-family: 'Inter', sans-serif;
            padding: 60px 20px;
        }
        .installer-container {
            max-width: 600px;
            margin: 0 auto;
        }
        .card-installer {
            background-color: var(--card-cream);
            border: 1px solid var(--border-beige);
            border-radius: 12px;
            padding: 40px;
        }
        .brand-title {
            color: var(--accent-terracotta);
            font-family: serif;
            font-weight: bold;
            text-align: center;
            margin-bottom: 30px;
        }
        .btn-sage {
            background-color: var(--primary-sage);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 6px;
            width: 100%;
            font-weight: 500;
        }
        .btn-sage:hover {
            background-color: #63775c;
            color: white;
        }
        .form-label {
            font-weight: 500;
            color: #555;
            font-size: 0.9rem;
        }
        .alert-custom {
            background-color: #FDF3F2;
            border: 1px solid #F3C3C0;
            color: #B03A2E;
            border-radius: 8px;
            padding: 15px;
        }
        .alert-success-custom {
            background-color: #EAF2F8;
            border: 1px solid #A9CCE3;
            color: #1A5276;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
        }
        .success-icon {
            font-size: 3.5rem;
            color: var(--primary-sage);
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
<div class="installer-container">
    <h2 class="brand-title"><i class="bi bi-cup-hot me-2"></i>Mellow & Meadow Café Setup</h2>

    <div class="card-installer shadow-sm">
        <?php if ($success): ?>
            <div class="alert-success-custom">
                <i class="bi bi-check-circle-fill success-icon"></i>
                <h4 class="fw-bold">Installation Successful!</h4>
                <p class="text-muted mt-2">
                    ✓ Database connection verified<br>
                    ✓ Relational tables created & seeded<br>
                    ✓ Local configurations written to <code>config/db_config.php</code><br>
                    ✓ System locked on production environment
                </p>
                <div class="mt-4">
                    <a href="index.php" class="btn btn-sage px-5">Launch Café Website</a>
                </div>
            </div>
        <?php else: ?>
            <p class="text-muted text-center mb-4">Enter your MySQL database parameters. The installer will test the connection, configure tables, and initialize the system.</p>
            
            <?php if ($error): ?>
                <div class="alert alert-custom alert-dismissible fade show mb-4" role="alert">
                    <div class="d-flex">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                        <div>
                            <strong>Setup Failed</strong><br>
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form action="install.php" method="POST">
                <div class="row g-3">
                    <div class="col-md-8">
                        <label for="db_host" class="form-label">Database Host</label>
                        <input type="text" class="form-control" id="db_host" name="db_host" value="<?php echo htmlspecialchars($host); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="db_port" class="form-label">Port</label>
                        <input type="text" class="form-control" id="db_port" name="db_port" value="<?php echo htmlspecialchars($port); ?>" required>
                    </div>
                    <div class="col-12">
                        <label for="db_name" class="form-label">Database Name</label>
                        <input type="text" class="form-control" id="db_name" name="db_name" value="<?php echo htmlspecialchars($dbname); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="db_user" class="form-label">Database Username</label>
                        <input type="text" class="form-control" id="db_user" name="db_user" value="<?php echo htmlspecialchars($username); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="db_pass" class="form-label">Database Password</label>
                        <input type="password" class="form-control" id="db_pass" name="db_pass" placeholder="Leave empty if none">
                    </div>
                    <div class="col-12 mt-4 pt-2">
                        <button type="submit" class="btn btn-sage"><i class="bi bi-gear-fill me-2"></i>Test & Install System</button>
                    </div>
                </div>
            </form>
        <?php endif; ?>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
