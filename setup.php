<?php
/**
 * Grand Cafe - 1-Click Database Installer & Reset Tool
 * Reads schema.sql and sets up the grand_cafe_db database automatically.
 */

$host = getenv('DB_HOST') ?: ($_ENV['DB_HOST'] ?? ($_SERVER['DB_HOST'] ?? 'localhost'));
$port = getenv('DB_PORT') ?: ($_ENV['DB_PORT'] ?? ($_SERVER['DB_PORT'] ?? '3306'));
$user = getenv('DB_USER') ?: ($_ENV['DB_USER'] ?? ($_SERVER['DB_USER'] ?? 'root'));
$pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? ($_SERVER['DB_PASS'] ?? ''));

$dbName = getenv('DB_NAME') ?: ($_ENV['DB_NAME'] ?? ($_SERVER['DB_NAME'] ?? 'grand_cafe_db'));

$message = '';
$status = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install_db'])) {
    try {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
        ];

        $isRemote = ($host !== 'localhost' && $host !== '127.0.0.1');
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

        // Connect with SSL enabled
        $dsn = "mysql:host={$host};port={$port};charset=utf8mb4";
        if ($isRemote && !empty($dbName)) {
            $dsn .= ";dbname={$dbName}";
        }

        $pdo = new PDO($dsn, $user, $pass, $options);

        $schemaFile = __DIR__ . '/schema.sql';
        if (!file_exists($schemaFile)) {
            throw new Exception("schema.sql file was not found in the project root.");
        }

        $sql = file_get_contents($schemaFile);

        // Dynamically map database name if configured differently (e.g. 'test')
        if (!empty($dbName) && $dbName !== 'grand_cafe_db') {
            $sql = str_replace('`grand_cafe_db`', "`{$dbName}`", $sql);
        }

        // Execute queries
        $pdo->exec($sql);

        $status = 'success';
        $message = "Database '{$dbName}' and tables were successfully initialized with seed data!";
    } catch (Exception $e) {
        $status = 'error';
        $message = "Error installing database: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grand Cafe - Database Installer</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .setup-container {
            max-width: 650px;
            margin: 60px auto;
            background: #ffffff;
            border-radius: 16px;
            padding: 40px;
            box-shadow: 0 16px 36px rgba(44, 24, 16, 0.12);
            border-top: 6px solid var(--color-accent);
        }
        .setup-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .setup-header h1 {
            font-family: 'Playfair Display', serif;
            color: var(--color-primary);
            margin: 10px 0 5px;
            font-size: 2rem;
        }
        .alert {
            padding: 16px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.95rem;
        }
        .alert-success {
            background-color: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #C8E6C9;
        }
        .alert-error {
            background-color: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
        }
        .credential-box {
            background: var(--color-cream);
            border: 1px dashed var(--color-accent);
            border-radius: 10px;
            padding: 20px;
            margin: 20px 0;
        }
        .credential-box h4 {
            margin-top: 0;
            color: var(--color-primary);
            font-size: 1.05rem;
        }
        .credential-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }
        .cred-item {
            background: #fff;
            padding: 12px 16px;
            border-radius: 8px;
            border: 1px solid #E5DCD3;
        }
        .cred-item strong {
            display: block;
            color: var(--color-accent);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
    </style>
</head>
<body style="background: var(--color-cream); min-height: 100vh; display: flex; align-items: center;">
    <div class="setup-container">
        <div class="setup-header">
            <div style="font-size: 3rem;">☕</div>
            <h1>Grand Cafe Installer</h1>
            <p style="color: var(--color-text-muted);">Quick Setup & Database Initialization Tool</p>
        </div>

        <?php if ($status === 'success'): ?>
            <div class="alert alert-success">
                <strong>✓ Success!</strong> <?php echo htmlspecialchars($message); ?>
            </div>
            
            <div class="credential-box">
                <h4>Default Accounts Ready to Use:</h4>
                <div class="credential-grid">
                    <div class="cred-item">
                        <strong>Admin Access</strong>
                        Email: <code>admin@grandcafe.com</code><br>
                        Password: <code>admin123</code>
                    </div>
                    <div class="cred-item">
                        <strong>Customer Access</strong>
                        Email: <code>customer@grandcafe.com</code><br>
                        Password: <code>customer123</code>
                    </div>
                </div>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 25px;">
                <a href="index.php" class="btn btn-primary" style="flex: 1; text-align: center; text-decoration: none;">Go to Homepage</a>
                <a href="login.php" class="btn btn-accent" style="flex: 1; text-align: center; text-decoration: none;">Login Page</a>
            </div>
        <?php else: ?>
            <?php if ($status === 'error'): ?>
                <div class="alert alert-error">
                    <strong>✗ Error:</strong> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <p style="color: var(--color-text-muted); line-height: 1.6;">
                Clicking the button below will connect to your local MySQL server (<code>localhost:3306</code>) and automatically create the <strong>grand_cafe_db</strong> database, configure the required tables (<code>users</code>, <code>coffee_menu</code>, <code>orders</code>, <code>order_items</code>), and pre-populate the initial menu and demo accounts.
            </p>

            <form method="POST" style="margin-top: 25px;">
                <button type="submit" name="install_db" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 1rem; cursor: pointer;">
                    🚀 Initialize Grand Cafe Database Now
                </button>
            </form>

            <div style="text-align: center; margin-top: 20px;">
                <small style="color: var(--color-text-muted);">
                    Already imported <code>schema.sql</code> manually? <a href="index.php" style="color: var(--color-accent); font-weight: 600;">Visit Homepage</a>
                </small>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
