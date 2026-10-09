<?php
/**
 * Grand Cafe - Coffee Menu & Order Page
 * Displays all active coffee items with prices, category filters, and quantity selectors.
 * Features dynamic real-time shopping cart and subtotal calculation before checkout.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

$pageTitle = 'Our Coffee Menu';

// Fetch all menu items ordered by category and name
try {
    $stmt = $pdo->query("SELECT * FROM `coffee_menu` ORDER BY FIELD(category, 'Hot Coffee', 'Cold Coffee', 'Specialty Coffee'), `item_name` ASC");
    $menuItems = $stmt->fetchAll();

    // Collect distinct categories
    $categories = array_unique(array_column($menuItems, 'category'));
} catch (PDOException $e) {
    $menuItems = [];
    $categories = [];
}

// Curated cafe item descriptions
$coffeeDescriptions = [
    'Espresso'         => 'Pure, intense espresso shot extracted under 9 bars with rich velvety crema.',
    'Cappuccino'       => 'Classic Italian harmony of bold espresso, silky steamed milk, and airy froth.',
    'Cafe Latte'       => 'Smooth, comforting double shot poured through creamy micro-textured milk.',
    'Americano'        => 'Rich espresso gently extended with hot water for a crisp, clean cup.',
    'Mocha'            => 'Decadent fusion of dark roast espresso, artisan Belgian chocolate & sweet milk.',
    'Cold Coffee'      => 'Slow-chilled espresso blended with fresh dairy and creamy bourbon vanilla froth.',
    'Filter Coffee'    => 'Traditional south-Indian decoction brewed with chicory and caramelized milk.',
    'Hazelnut Coffee'  => 'Slow-roasted hazelnut essence swirled into hot espresso and velvety cream.',
    'Caramel Coffee'   => 'Golden salted butter caramel syrup melted into rich espresso and milk.'
];

require_once __DIR__ . '/includes/header.php';
?>

<!-- Menu Hero Banner -->
<section style="background: linear-gradient(135deg, #2C1810 0%, #3E2723 100%); color: #fff; padding: 50px 0 45px; text-align: center;">
    <div class="container">
        <span class="hero-tagline" style="margin-bottom: 12px; font-size: 0.8rem;">
            <i class="fa-solid fa-mug-hot"></i> Freshly Ground On Demand
        </span>
        <h1 style="color: #FFF8EE; font-size: 2.8rem; margin-bottom: 10px;">Artisan Coffee Menu</h1>
        <p style="color: #D7CCC8; max-width: 600px; margin: 0 auto; font-size: 1.05rem;">
            Select your favorite roast, customize quantities, and enjoy seamless counter pickup or table service.
        </p>
    </div>
</section>

<!-- Menu Items & Order Grid -->
<section class="section-padding">
    <div class="container">

        <!-- Controls: Category Tabs & Search Bar -->
        <div class="menu-controls">
            <div class="category-tabs">
                <button type="button" class="category-tab active" data-category="all">
                    <i class="fa-solid fa-border-all"></i> All Brews
                </button>
                <?php foreach ($categories as $cat): ?>
                    <button type="button" class="category-tab" data-category="<?php echo e($cat); ?>">
                        <?php 
                            if (stripos($cat, 'cold') !== false) {
                                echo '<i class="fa-solid fa-snowflake"></i> ';
                            } elseif (stripos($cat, 'specialty') !== false) {
                                echo '<i class="fa-solid fa-sparkles"></i> ';
                            } else {
                                echo '<i class="fa-solid fa-fire"></i> ';
                            }
                            echo e($cat); 
                        ?>
                    </button>
                <?php endforeach; ?>
            </div>

            <div class="search-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="menuSearchInput" placeholder="Search coffee (e.g. Latte, Mocha)...">
            </div>
        </div>

        <?php if (!empty($menuItems)): ?>
            <div class="coffee-grid" id="coffeeGrid">
                <?php foreach ($menuItems as $item): ?>
                    <?php 
                        $isAvailable = ($item['status'] === 'available');
                        $desc = $coffeeDescriptions[$item['item_name']] ?? 'Artisan hand-pulled brew prepared fresh to order.';
                    ?>
                    <div class="coffee-card" 
                         data-id="<?php echo e($item['id']); ?>" 
                         data-category="<?php echo e($item['category']); ?>" 
                         data-name="<?php echo e($item['item_name']); ?>">
                        
                        <div class="coffee-card-visual">
                            <span class="badge-category"><?php echo e($item['category']); ?></span>
                            <span class="badge badge-<?php echo $isAvailable ? 'available' : 'out-of-stock'; ?>">
                                <?php echo $isAvailable ? 'Available' : 'Out of Stock'; ?>
                            </span>
                            <div class="coffee-card-icon">
                                <?php 
                                    $n = strtolower($item['item_name']);
                                    if (strpos($n, 'cold') !== false) {
                                        echo '🧊';
                                    } elseif (strpos($n, 'espresso') !== false) {
                                        echo '☕';
                                    } elseif (strpos($n, 'caramel') !== false || strpos($n, 'hazelnut') !== false) {
                                        echo '✨';
                                    } elseif (strpos($n, 'filter') !== false) {
                                        echo '🫖';
                                    } else {
                                        echo '☕';
                                    }
                                ?>
                            </div>
                        </div>

                        <div class="coffee-card-body">
                            <h3 class="coffee-card-title"><?php echo e($item['item_name']); ?></h3>
                            <p class="coffee-card-desc"><?php echo e($desc); ?></p>

                            <div class="coffee-card-footer">
                                <div class="coffee-price"><?php echo formatPrice($item['price']); ?></div>

                                <?php if ($isAvailable): ?>
                                    <div style="display: flex; align-items: center; gap: 8px;">
                                        <div class="qty-control">
                                            <button type="button" class="qty-btn qty-minus">-</button>
                                            <input type="text" class="qty-input" value="1" readonly>
                                            <button type="button" class="qty-btn qty-plus">+</button>
                                        </div>
                                        <button type="button" 
                                                class="btn btn-accent btn-sm"
                                                onclick="
                                                    const parent = this.closest('.coffee-card');
                                                    const qty = parseInt(parent.querySelector('.qty-input').value, 10) || 1;
                                                    addToCart(<?php echo $item['id']; ?>, '<?php echo addslashes($item['item_name']); ?>', <?php echo $item['price']; ?>, qty, '<?php echo addslashes($item['category']); ?>');
                                                ">
                                            <i class="fa-solid fa-plus"></i> Add
                                        </button>
                                    </div>
                                <?php else: ?>
                                    <button type="button" class="btn btn-sm btn-outline disabled" disabled>
                                        Unavailable
                                    </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px; border: 1px dashed var(--color-cream-border);">
                <div style="font-size: 3rem; margin-bottom: 15px;">☕</div>
                <h3>No Menu Items Available</h3>
                <p style="color: var(--color-text-muted); margin-bottom: 20px;">The coffee menu is currently being updated or has not been initialized.</p>
                <a href="setup.php" class="btn btn-accent">Initialize Database Menu</a>
            </div>
        <?php endif; ?>

        <!-- Bottom Floating Order Bar (Visible when items in cart) -->
        <div id="stickyOrderBar" style="margin-top: 50px; background: #FFFFFF; border: 2px solid var(--color-cream-border); border-radius: var(--radius-lg); padding: 20px 30px; box-shadow: var(--shadow-md); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 15px;">
            <div style="display: flex; align-items: center; gap: 15px;">
                <div style="width: 48px; height: 48px; background: var(--color-accent-light); color: var(--color-accent); border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem;">
                    <i class="fa-solid fa-bag-shopping"></i>
                </div>
                <div>
                    <h4 style="font-size: 1.1rem; margin-bottom: 2px;">Ready to Place Your Order?</h4>
                    <p style="color: var(--color-text-muted); font-size: 0.9rem;">Review your items and proceed directly to payment.</p>
                </div>
            </div>

            <div style="display: flex; align-items: center; gap: 15px;">
                <button type="button" class="btn btn-outline" onclick="openCartDrawer()">
                    <i class="fa-solid fa-cart-shopping"></i> Review Cart
                </button>
                <button type="button" class="btn btn-accent" onclick="proceedToCheckout()">
                    <i class="fa-solid fa-arrow-right"></i> Checkout Now
                </button>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
