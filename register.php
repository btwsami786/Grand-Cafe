<?php
/**
 * Grand Cafe - Customer Registration
 * Handles secure account creation with input validation, password hashing, and auto-login.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

if (isLoggedIn()) {
    header('Location: menu.php');
    exit;
}

$pageTitle = 'Create an Account';
$error = '';
$username = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($confirmPassword)) {
        $error = 'All fields are required. Please fill in all fields.';
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $error = 'Username must be between 3 and 50 characters.';
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $error = 'Username may only contain letters, numbers, and underscores.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters long.';
    } elseif ($password !== $confirmPassword) {
        $error = 'Passwords do not match. Please re-enter.';
    } else {
        try {
            // Check if username or email is already taken using prepared statements
            $checkStmt = $pdo->prepare("SELECT `id`, `username`, `email` FROM `users` WHERE `username` = ? OR `email` = ? LIMIT 1");
            $checkStmt->execute([$username, $email]);
            $existing = $checkStmt->fetch();

            if ($existing) {
                if (strcasecmp($existing['username'], $username) === 0) {
                    $error = 'This username is already taken. Please choose another.';
                } else {
                    $error = 'An account with this email address already exists. Try signing in.';
                }
            } else {
                // Securely hash password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

                // Insert new customer record
                $insertStmt = $pdo->prepare("INSERT INTO `users` (`username`, `email`, `password`, `role`) VALUES (?, ?, ?, 'customer')");
                $insertStmt->execute([$username, $email, $hashedPassword]);
                $newUserId = $pdo->lastInsertId();

                // Auto-login the new user
                $_SESSION['user_id'] = $newUserId;
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                $_SESSION['role'] = 'customer';

                setFlash('success', "Welcome to Grand Cafe, {$username}! Your account has been created.");
                header('Location: menu.php');
                exit;
            }
        } catch (PDOException $e) {
            $error = 'Database error during registration: ' . $e->getMessage();
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="auth-wrapper">
    <div class="auth-card">
        <div class="auth-header">
            <div style="font-size: 2.5rem; margin-bottom: 5px;">☕</div>
            <h2>Join Grand Cafe</h2>
            <p>Create an account to order fresh brews and track your deliveries.</p>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-alert flash-error" style="margin-bottom: 20px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?php echo e($error); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="register.php">
            <div class="form-group">
                <label for="username" class="form-label">
                    <i class="fa-solid fa-user" style="color: var(--color-accent); margin-right: 5px;"></i>
                    Username
                </label>
                <input type="text" 
                       id="username" 
                       name="username" 
                       class="form-control" 
                       placeholder="e.g. coffee_lover" 
                       value="<?php echo e($username); ?>" 
                       required 
                       autofocus>
                <small style="color: var(--color-text-light); font-size: 0.8rem;">Letters, numbers, and underscores only</small>
            </div>

            <div class="form-group">
                <label for="email" class="form-label">
                    <i class="fa-solid fa-envelope" style="color: var(--color-accent); margin-right: 5px;"></i>
                    Email Address
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       class="form-control" 
                       placeholder="e.g. yourname@example.com" 
                       value="<?php echo e($email); ?>" 
                       required>
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
                       placeholder="Minimum 6 characters" 
                       required>
            </div>

            <div class="form-group">
                <label for="confirm_password" class="form-label">
                    <i class="fa-solid fa-shield-halved" style="color: var(--color-accent); margin-right: 5px;"></i>
                    Confirm Password
                </label>
                <input type="password" 
                       id="confirm_password" 
                       name="confirm_password" 
                       class="form-control" 
                       placeholder="Re-enter your password" 
                       required>
            </div>

            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 13px; font-size: 1rem; margin-top: 10px;">
                <i class="fa-solid fa-user-plus"></i> Register Account
            </button>
        </form>

        <div style="text-align: center; margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--color-cream-border); font-size: 0.92rem;">
            Already have an account? 
            <a href="login.php" style="color: var(--color-accent); font-weight: 700;">
                Sign in here
            </a>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
