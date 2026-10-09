<?php
/**
 * Grand Cafe - Database Connection (PDO)
 * Handles secure PDO connection with error management and helper functions.
 */

if (!defined('DB_HOST')) define('DB_HOST', getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? 'localhost')));
if (!defined('DB_PORT')) define('DB_PORT', getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? ($_SERVER['DB_PORT'] ?? '3306')));
if (!defined('DB_NAME')) define('DB_NAME', getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($_SERVER['DB_NAME'] ?? 'grand_cafe_db')));
if (!defined('DB_USER')) define('DB_USER', getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($_SERVER['DB_USER'] ?? 'root')));
if (!defined('DB_PASS')) define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? ($_SERVER['DB_PASS'] ?? '')));
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// Currency symbol used throughout the application
if (!defined('CURRENCY_SYMBOL')) define('CURRENCY_SYMBOL', '₹');

try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    // Enable SSL/TLS encryption for remote cloud databases (e.g. TiDB Cloud Serverless, Aiven)
    $isRemote = (DB_HOST !== 'localhost' && DB_HOST !== '127.0.0.1');
    if ($isRemote && defined('PDO::MYSQL_ATTR_SSL_CA')) {
        $caFile = __DIR__ . '/cacert.pem';
        if (!file_exists($caFile)) {
            if (file_exists('/etc/pki/tls/certs/ca-bundle.crt')) {
                $caFile = '/etc/pki/tls/certs/ca-bundle.crt';
            } elseif (file_exists('/etc/ssl/certs/ca-certificates.crt')) {
                $caFile = '/etc/ssl/certs/ca-certificates.crt';
            }
        }
        if (file_exists($caFile)) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $caFile;
        }
        if (defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
    }

    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
} catch (PDOException $e) {
    // If the database does not exist yet or connection fails
    $errorCode = $e->getCode();
    $errorMessage = $e->getMessage();

    // Check if table or database is missing (error 1049 is ER_BAD_DB_ERROR)
    if (strpos($errorMessage, 'Unknown database') !== false || $errorCode == 1049) {
        $setupUrl = (file_exists(__DIR__ . '/setup.php')) ? 'setup.php' : '#';
        die("
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Grand Cafe - Database Not Initialized</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #FAF6F0; color: #2C1810; display: flex; align-items: center; justify-content: center; min-height: 100vh; margin: 0; padding: 20px; box-sizing: border-box; }
                .card { background: #FFFFFF; border-radius: 12px; max-width: 580px; width: 100%; padding: 36px; box-shadow: 0 10px 30px rgba(44,24,16,0.1); border-top: 6px solid #C68B59; }
                h1 { margin-top: 0; color: #3E2723; font-size: 1.6rem; display: flex; align-items: center; gap: 10px; }
                p { line-height: 1.6; color: #5D4037; }
                .btn { display: inline-block; background: #C68B59; color: #FFFFFF; padding: 12px 24px; text-decoration: none; border-radius: 6px; font-weight: 600; margin-top: 15px; transition: background 0.2s; }
                .btn:hover { background: #B27645; }
                .code-box { background: #F5EBE1; padding: 12px; border-radius: 6px; font-family: monospace; font-size: 0.9rem; overflow-x: auto; margin-top: 15px; }
            </style>
        </head>
        <body>
            <div class='card'>
                <h1>☕ Grand Cafe Database Setup</h1>
                <p>The MySQL database <strong>" . htmlspecialchars(DB_NAME) . "</strong> has not been created yet.</p>
                <p>You can initialize it in one click using our setup script, or manually import <code>schema.sql</code> via phpMyAdmin.</p>
                <a href='{$setupUrl}' class='btn'>Run 1-Click Database Installer</a>
                <div class='code-box'>
                    <strong>Manual Import Option:</strong><br>
                    1. Open <em>http://localhost/phpmyadmin</em><br>
                    2. Click <strong>Import</strong> tab<br>
                    3. Select <code>schema.sql</code> from this project folder and click <strong>Go</strong>.
                </div>
            </div>
        </body>
        </html>
        ");
    }

    // General connection failure
    die("<div style='font-family: sans-serif; padding: 30px; background: #FFF3CD; color: #856404; border: 1px solid #FFEEBA; border-radius: 8px; margin: 20px;'>
        <h3>Database Connection Error</h3>
        <p>" . htmlspecialchars($errorMessage) . "</p>
        <p><small>Ensure MySQL is started in XAMPP/WAMP Control Panel and verify credentials in <code>db.php</code>.</small></p>
    </div>");
}

/**
 * Helper to escape output and prevent XSS
 */
function e($value) {
    return htmlspecialchars((string)($value ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Helper to format price with cafe currency symbol
 */
function formatPrice($amount) {
    return CURRENCY_SYMBOL . number_format((float)$amount, 2);
}
