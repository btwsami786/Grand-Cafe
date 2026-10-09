<?php
/**
 * Grand Cafe - Login Page
 * Secure session-based authentication for Customers & Administrators.
 * Redirects customers to menu.php and admins to admin.php.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

// If already logged in, redirect based on role
if (isLoggedIn()) {
    if (isAdmin()) {
        header('Location: admin.php');
    } else {
        header('Location: menu.php');
    }
    exit;
}

$pageTitle = 'Sign In';
$error = '';
$redirect = $_GET['redirect'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $loginIdentifier = trim($_POST['identifier'] ?? '');
    $password = $_POST['password'] ?? '';
    $redirect = trim($_POST['redirect'] ?? '');

    if (empty($loginIdentifier) || empty($password)) {
        $error = 'Please enter both your email/username and password.';
    } else {
        try {
            // Find user by email or username using prepared statements
            $stmt = $pdo->prepare("SELECT * FROM `users` WHERE `email` = ? OR `username` = ? LIMIT 1");
            $stmt->execute([$loginIdentifier, $loginIdentifier]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                // Password matches! Initialize secure session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];

                setFlash('success', "Welcome back, {$user['username']}! Enjoy your coffee.");

                // Redirect logic based on role or intended destination
                if (!empty($redirect) && strpos($redirect, 'logout.php') === false) {
                    header("Location: " . filter_var($redirect, FILTER_SANITIZE_URL));
                    exit;
                }

                if ($user['role'] === 'admin') {
                    header('Location: admin.php');
                } else {
                    header('Location: menu.php');
                }
                exit;
            } else {
                $error = 'Invalid email/username or password. Please try again.';
            }
        } catch (PDOException $e) {
            $error = 'A database error occurred. Please try again later.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div style="font-size: 2.5rem; margin-bottom: 5px;">☕</div>
            <h2>Welcome Back</h2>
            <p>Sign in to your Grand Cafe account to order & track your brews.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-alert flash-error" style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?php echo e($error); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="login.php" id="loginForm">
            <input type="hidden" name="redirect" value="<?php echo e($redirect); ?>">

            <div class="form-group">
                <label for="identifier" class="form-label">
                    <i class="fa-solid fa-user" style="color: var(--color-accent); margin-right: 5px;"></i>
                    Email or Username
                </label>
                <input type="text" 
                       id="identifier" 
                       name="identifier" 
                       class="form-control" 
                       placeholder="e.g. admin@grandcafe.com or customer" 
                       value="<?php echo e($_POST['identifier'] ?? ''); ?>" 
                       required 
                       autofocus>
            </div>

            <div class="form-group">
                <label for="password" class="form-label">
                    <i class="fa-solid fa-lock" style="color: var(--color-accent); margin-right: 5px;"></i>
                    Password
                </label>
                <input type="password" 
                       id="password" 
                       name="password" 
                       class="form-control" 
                       placeholder="Enter your password" 
                       required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 1rem; margin-top: 10px;">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Grand Cafe
            </button>
        </form>

        <!-- Quick Demo Credentials for Fast Testing -->
        <div class="demo-credentials-box">
            <h4><i class="fa-solid fa-key"></i> Quick Demo Logins:</h4>
            <div class="demo-btn-group">
                <button type="button" onclick="fillCredentials('admin@grandcafe.com', 'admin123')">
                    👑 Demo Admin
                </button>
                <button type="button" onclick="fillCredentials('customer@grandcafe.com', 'customer123')">
                    ☕ Demo Customer
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--color-cream-border); font-size: 0.92rem;">
            New to Grand Cafe? 
            <a href="register.php" style="color: var(--color-accent); font-weight: 700;">
                Create an account
            </a>
        </div>
    </div>
</div>

<script>
function fillCredentials(id, pass) {
    document.getElementById('identifier').value = id;
    document.getElementById('password').value = pass;
    showToast('Credentials filled! Click Sign In to continue.', 'info');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
