<?php
/**
 * config/database.php
 * Database Connection Configuration using PDO
 */

// Load dynamic database configuration if it exists, otherwise trigger setup redirects
if (file_exists(__DIR__ . '/db_config.php')) {
    require_once __DIR__ . '/db_config.php';
} else {
    // Falls back to safe default parameters for the installer context
    if (!defined('DB_HOST')) define('DB_HOST', '127.0.0.1');
    if (!defined('DB_PORT')) define('DB_PORT', '3306');
    if (!defined('DB_USER')) define('DB_USER', 'root');
    if (!defined('DB_PASS')) define('DB_PASS', '');
    if (!defined('DB_NAME')) define('DB_NAME', 'cafe_db');
    
    // Redirect to installer if config is missing (unless we are already running installer)
    $current_script = basename($_SERVER['SCRIPT_NAME']);
    if ($current_script !== 'install.php') {
        header("Location: install.php");
        exit;
    }
}

/**
 * Establishes and returns a shared PDO database connection instance.
 * Suppresses database exceptions in production and logs them.
 * 
 * @return PDO
 */
function get_db_connection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $port = defined('DB_PORT') ? DB_PORT : '3306';
        $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Write detailed exception to server error log
            error_log("Mellow & Meadow DB Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
            
            // Show friendly message to public. DO NOT expose $e->getMessage() to prevent leakage of credentials or paths.
            http_response_code(500);
            echo '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Server Error - Mellow & Meadow</title>
                <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500&display=swap" rel="stylesheet">
                <style>
                    body { font-family: "Inter", sans-serif; background-color: #FFFDF8; color: #292725; padding: 50px 20px; }
                    .error-container { max-width: 600px; margin: 100px auto; background-color: #F7F3EA; border-radius: 12px; padding: 40px; text-align: center; border: 1px solid #EFEAE0; }
                    h1 { color: #8A6249; font-weight: 600; margin-bottom: 20px; }
                    p { color: #77716A; line-height: 1.6; }
                    .btn-accent { background-color: #78906F; color: white; border: none; padding: 10px 24px; border-radius: 6px; text-decoration: none; }
                    .btn-accent:hover { background-color: #63775c; color: white; }
                </style>
            </head>
            <body>
                <div class="error-container shadow-sm">
                    <h1>Under Maintenance</h1>
                    <p>We are currently updating our systems to serve you better. Please reload the page in a few minutes, or reach out to us if the problem persists.</p>
                    <a href="index.php" class="btn btn-accent mt-3">Try Refreshing</a>
                </div>
            </body>
            </html>';
            exit;
        }
    }
    
    return $pdo;
}
