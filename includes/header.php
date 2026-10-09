<?php
/**
 * Grand Cafe - Header Partial
 * Includes HTML head, fonts, global navigation, user status, and flash alerts.
 */

if (!defined('DB_NAME')) {
    require_once __DIR__ . '/../db.php';
}
require_once __DIR__ . '/auth_helper.php';

$currentPage = basename($_SERVER['PHP_SELF']);
$user = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? e($pageTitle) . ' | Grand Cafe' : 'Grand Cafe | Artisan Coffee & Fresh Brews'; ?></title>
    
    <!-- Google Fonts: Playfair Display (Serif) & Plus Jakarta Sans (Sans) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:ital,wght@0,600;0,700;1,400&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- FontAwesome 6 Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    
    <!-- Main Cafe Stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Sticky Navigation Bar -->
    <header class="site-header">
        <div class="container nav-container">
            <!-- Brand Logo -->
            <a href="index.php" class="brand-logo" title="Grand Cafe">
                <span class="logo-icon"><i class="fa-solid fa-mug-hot"></i></span>
                <span>Grand <span>Cafe</span></span>
            </a>

            <!-- Navigation Links -->
            <nav>
                <ul class="nav-menu" id="navMenu">
                    <li>
                        <a href="index.php" class="nav-link <?php echo ($currentPage === 'index.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-house"></i> Home
                        </a>
                    </li>
                    <li>
                        <a href="menu.php" class="nav-link <?php echo ($currentPage === 'menu.php' || $currentPage === 'order.php') ? 'active' : ''; ?>">
                            <i class="fa-solid fa-mug-saucer"></i> Menu & Order
                        </a>
                    </li>
                    <?php if (isLoggedIn()): ?>
                        <li>
                            <a href="my_orders.php" class="nav-link <?php echo ($currentPage === 'my_orders.php') ? 'active' : ''; ?>">
                                <i class="fa-solid fa-receipt"></i> My Orders
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (isAdmin()): ?>
                        <li>
                            <a href="admin.php" class="nav-link <?php echo ($currentPage === 'admin.php') ? 'active' : ''; ?>" style="color: var(--color-accent); font-weight: 700;">
                                <i class="fa-solid fa-shield-halved"></i> Admin Dashboard
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>

            <!-- User Status & Actions -->
            <div class="nav-actions">
                <!-- Cart Button -->
                <button type="button" class="cart-toggle-btn" title="View Order">
                    <i class="fa-solid fa-bag-shopping"></i>
                    <span class="cart-badge" style="display: none;">0</span>
                </button>

                <?php if (isLoggedIn()): ?>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 0.9rem; font-weight: 600; color: var(--color-primary); display: flex; align-items: center; gap: 5px;">
                            <i class="fa-regular fa-user" style="color: var(--color-accent);"></i>
                            <?php echo e($user['username']); ?>
                            <?php if (isAdmin()): ?>
                                <span style="font-size: 0.7rem; background: var(--color-primary); color: #fff; padding: 2px 6px; border-radius: 4px; text-transform: uppercase;">Admin</span>
                            <?php endif; ?>
                        </span>
                        <a href="logout.php" class="btn btn-outline btn-sm" title="Log Out">
                            <i class="fa-solid fa-arrow-right-from-bracket"></i> Logout
                        </a>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-outline btn-sm">
                        <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
                    </a>
                    <a href="register.php" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-user-plus"></i> Register
                    </a>
                <?php endif; ?>

                <!-- Mobile Hamburger Toggle -->
                <button type="button" class="mobile-menu-toggle" id="mobileMenuToggle" aria-label="Toggle Navigation">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- Global Flash Notification Banners -->
    <div class="container flash-container">
        <?php foreach (getFlashes() as $flash): ?>
            <div class="flash-alert flash-<?php echo e($flash['type']); ?>">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <?php if ($flash['type'] === 'success'): ?>
                        <i class="fa-solid fa-circle-check"></i>
                    <?php elseif ($flash['type'] === 'warning'): ?>
                        <i class="fa-solid fa-triangle-exclamation"></i>
                    <?php elseif ($flash['type'] === 'error' || $flash['type'] === 'danger'): ?>
                        <i class="fa-solid fa-circle-xmark"></i>
                    <?php else: ?>
                        <i class="fa-solid fa-circle-info"></i>
                    <?php endif; ?>
                    <span><?php echo e($flash['message']); ?></span>
                </div>
                <button type="button" class="flash-close">&times;</button>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Slide-in Cart Drawer Overlay & Drawer Container -->
    <div class="cart-drawer-overlay" id="cartOverlay"></div>
    <aside class="cart-drawer" id="cartDrawer" aria-label="Order Cart">
        <div class="cart-drawer-header">
            <h3><i class="fa-solid fa-bag-shopping" style="color: var(--color-accent);"></i> Your Coffee Order</h3>
            <button type="button" class="close-drawer-btn" id="closeCartDrawer">&times;</button>
        </div>

        <div class="cart-items-list" id="cartItemsList">
            <!-- Dynamically populated via main.js -->
        </div>

        <div class="cart-drawer-footer">
            <div class="cart-summary-row">
                <span>Subtotal</span>
                <span id="cartSubtotal"><?php echo CAFE_CURRENCY; ?>0.00</span>
            </div>
            <div class="cart-summary-row">
                <span>Taxes & Cafe Service</span>
                <span style="color: var(--color-success); font-weight: 600;">Included</span>
            </div>
            <div class="cart-summary-total">
                <span>Total Amount</span>
                <span id="cartTotal"><?php echo CAFE_CURRENCY; ?>0.00</span>
            </div>
            <button type="button" class="btn btn-accent" id="cartCheckoutBtn" style="width: 100%;">
                <i class="fa-solid fa-credit-card"></i> Proceed to Checkout
            </button>
        </div>
    </aside>

    <!-- Main Page Content Body Starts Here -->
    <main>
