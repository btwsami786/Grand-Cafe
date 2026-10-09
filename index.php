<?php
/**
 * Grand Cafe - Landing / Home Page
 * Features hero banner, cafe branding, quick links, and featured coffee menu items.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

$pageTitle = 'Artisan Coffee & Fresh Brews';

// Fetch featured items from coffee_menu
$featuredItems = [];
try {
    $stmt = $pdo->query("SELECT * FROM `coffee_menu` WHERE `status` = 'available' ORDER BY `id` ASC LIMIT 6");
    $featuredItems = $stmt->fetchAll();
} catch (PDOException $e) {
    // Graceful fallback if database connection has temporary issue
    $featuredItems = [];
}

// Curated high-resolution cafe photography
$coffeePhotos = [
    'Espresso'         => 'https://images.unsplash.com/photo-1514432324607-a09d9b4aefdd?auto=format&fit=crop&w=600&q=80',
    'Cappuccino'       => 'https://images.unsplash.com/photo-1572442388796-11668a67e53d?auto=format&fit=crop&w=600&q=80',
    'Cafe Latte'       => 'https://images.unsplash.com/photo-1570968915860-54d5c301fa9f?auto=format&fit=crop&w=600&q=80',
    'Americano'        => 'https://images.unsplash.com/photo-1551030173-122aabc4489c?auto=format&fit=crop&w=600&q=80',
    'Mocha'            => 'https://images.unsplash.com/photo-1578314675249-a6910f80cc4e?auto=format&fit=crop&w=600&q=80',
    'Cold Coffee'      => 'https://images.unsplash.com/photo-1517701550927-30cf4ba1dba5?auto=format&fit=crop&w=600&q=80',
    'Filter Coffee'    => 'https://images.unsplash.com/photo-1589396575653-c09c794ff6a6?auto=format&fit=crop&w=600&q=80',
    'Hazelnut Coffee'  => 'https://images.unsplash.com/photo-1541167760496-1628856ab772?auto=format&fit=crop&w=600&q=80',
    'Caramel Coffee'   => 'https://images.unsplash.com/photo-1485808191679-5f86510681a2?auto=format&fit=crop&w=600&q=80',
];
$defaultCoffeePhoto = 'https://images.unsplash.com/photo-1509042239860-f550ce710b93?auto=format&fit=crop&w=600&q=80';

require_once __DIR__ . '/includes/header.php';
?>

<!-- Hero Banner Section -->
<section class="hero-section">
    <div class="container hero-grid">
        <div class="hero-content">
            <span class="hero-tagline">
                <i class="fa-solid fa-mug-hot"></i> Handcrafted Daily Since 2018
            </span>
            <h1 class="hero-title">
                Artisan Coffee Crafted with <span>Passion & Soul</span>
            </h1>
            <p class="hero-description">
                Welcome to Grand Cafe. Immerse yourself in the rich aromas of freshly ground Arabica beans, delicate hand-poured lattes, and comforting artisanal brews made to inspire your day.
            </p>
            <div class="hero-buttons">
                <a href="menu.php" class="btn btn-accent" style="padding: 14px 28px; font-size: 1rem;">
                    <i class="fa-solid fa-mug-saucer"></i> Order Coffee Now
                </a>
                <?php if (!isLoggedIn()): ?>
                    <a href="register.php" class="btn btn-outline" style="border-color: #D7CCC8; color: #FFF8EE; padding: 14px 28px;">
                        <i class="fa-solid fa-user-plus"></i> Join Grand Rewards
                    </a>
                <?php else: ?>
                    <a href="my_orders.php" class="btn btn-outline" style="border-color: #D7CCC8; color: #FFF8EE; padding: 14px 28px;">
                        <i class="fa-solid fa-receipt"></i> View My Orders
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <div class="hero-visual">
            <div class="hero-cup-card">
                <div class="hero-cup-icon">☕</div>
                <h3 style="color: #FFF8EE; font-size: 1.4rem; margin-bottom: 6px;">Freshly Brewed Excellence</h3>
                <p style="color: #D7CCC8; font-size: 0.9rem;">From crop to cup, experience perfection in every single sip.</p>
                
                <div class="hero-cup-stats">
                    <div class="stat-box">
                        <h4>100%</h4>
                        <p>Arabica Blends</p>
                    </div>
                    <div class="stat-box">
                        <h4>4.9 ★</h4>
                        <p>Guest Rating</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Cafe Values / Highlights -->
<section class="features-section">
    <div class="container">
        <div class="feature-grid">
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-fire-burner"></i></div>
                <h3>Small-Batch Roasting</h3>
                <p>We source ethically cultivated green coffee beans and roast them gently in micro-batches every morning.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-wand-magic-sparkles"></i></div>
                <h3>Master Baristas</h3>
                <p>Every espresso extraction and silky microfoam is calibrated with artisan precision and deep passion.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-bolt"></i></div>
                <h3>Swift Pickup & Delivery</h3>
                <p>Place your order online in seconds. Pay seamlessly with Cash, UPI QR, or Card for prompt service.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon"><i class="fa-solid fa-heart"></i></div>
                <h3>Warm Welcoming Vibe</h3>
                <p>A tranquil haven designed with warm amber accents, cozy corners, and the soothing hum of good music.</p>
            </div>
        </div>
    </div>
</section>

<!-- Featured Brews Menu Showcase -->
<section class="section-padding">
    <div class="container">
        <div class="section-header">
            <span class="section-subtitle">Our Signature Creations</span>
            <h2 class="section-title">Popular Brews from the Barista Bar</h2>
            <p style="color: var(--color-text-muted); max-width: 550px; margin: 10px auto 0;">
                Handpicked customer favorites crafted to brighten your morning or revitalize your afternoon.
            </p>
        </div>

        <?php if (!empty($featuredItems)): ?>
            <div class="coffee-grid">
                <?php foreach ($featuredItems as $item): ?>
                    <div class="coffee-card" data-id="<?php echo e($item['id']); ?>" data-category="<?php echo e($item['category']); ?>" data-name="<?php echo e($item['item_name']); ?>">
                        <?php 
                            $photoUrl = $coffeePhotos[$item['item_name']] ?? $defaultCoffeePhoto;
                        ?>
                        <div class="coffee-card-visual">
                            <img src="<?php echo e($photoUrl); ?>" alt="<?php echo e($item['item_name']); ?>" class="coffee-card-img" loading="lazy">
                            <div class="coffee-card-overlay"></div>
                            <span class="badge-category"><?php echo e($item['category']); ?></span>
                            <span class="badge badge-<?php echo ($item['status'] === 'available') ? 'available' : 'out-of-stock'; ?>">
                                <?php echo ($item['status'] === 'available') ? 'Available' : 'Sold Out'; ?>
                            </span>
                        </div>

                        <div class="coffee-card-body">
                            <h3 class="coffee-card-title"><?php echo e($item['item_name']); ?></h3>
                            <p class="coffee-card-desc">
                                <?php 
                                    $descriptions = [
                                        'Espresso' => 'Intense, rich shot with thick hazelnut-colored crema.',
                                        'Cappuccino' => 'Equal balance of espresso, steamed velvety milk & foam.',
                                        'Cafe Latte' => 'Silky espresso poured over lightly textured steamed milk.',
                                        'Americano' => 'Double espresso diluted with hot water for a smooth brew.',
                                        'Mocha' => 'Rich espresso infused with artisan Belgian chocolate & cream.',
                                        'Cold Coffee' => 'Chilled espresso blended with cold milk & creamy vanilla froth.',
                                        'Filter Coffee' => 'Traditional decoction brewed with fresh chicory blend.',
                                        'Hazelnut Coffee' => 'Aromatic roasted hazelnut infusion with smooth cream.',
                                        'Caramel Coffee' => 'Gourmet salted caramel swirl blended into fresh espresso.'
                                    ];
                                    echo $descriptions[$item['item_name']] ?? 'Artisan brew prepared with our signature cafe beans.';
                                ?>
                            </p>

                            <div class="coffee-card-footer">
                                <div class="coffee-price"><?php echo formatPrice($item['price']); ?></div>

                                <?php if ($item['status'] === 'available'): ?>
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
                                    <button type="button" class="btn btn-sm btn-outline disabled" disabled>Sold Out</button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="text-align: center; margin-top: 45px;">
                <a href="menu.php" class="btn btn-primary" style="padding: 13px 30px; font-size: 1rem;">
                    <i class="fa-solid fa-list-check"></i> View Full Menu & Custom Orders
                </a>
            </div>
        <?php else: ?>
            <div style="text-align: center; padding: 40px; background: #fff; border-radius: 12px; border: 1px dashed var(--color-cream-border);">
                <p style="color: var(--color-text-muted); font-size: 1.1rem;">Menu items are loading or database is initializing.</p>
                <a href="setup.php" class="btn btn-accent" style="margin-top: 15px;">Run Database Setup</a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- Cafe Heritage Story Section -->
<section style="background: var(--color-cream-dark); padding: 80px 0;">
    <div class="container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 50px; align-items: center;">
        <div>
            <span class="section-subtitle">Our Heritage</span>
            <h2 class="section-title" style="margin-bottom: 20px;">Brewing Memories One Cup At A Time</h2>
            <p style="color: var(--color-text-muted); margin-bottom: 15px; line-height: 1.7;">
                Grand Cafe was founded on a simple principle: extraordinary coffee should be an everyday luxury. What began as a passionate dream in an intimate corner store has flourished into a sanctuary for coffee purists and casual sippers alike.
            </p>
            <p style="color: var(--color-text-muted); margin-bottom: 25px; line-height: 1.7;">
                From the crisp morning hiss of our Italian espresso machine to the late-evening hum of lively conversation, we celebrate community, craft, and comforting warmth.
            </p>
            <div style="display: flex; gap: 20px;">
                <div>
                    <h4 style="font-size: 1.5rem; color: var(--color-primary); font-family: var(--font-heading);">50,000+</h4>
                    <p style="color: var(--color-text-muted); font-size: 0.85rem;">Cups Poured</p>
                </div>
                <div style="border-left: 2px solid var(--color-cream-border); padding-left: 20px;">
                    <h4 style="font-size: 1.5rem; color: var(--color-primary); font-family: var(--font-heading);">12+</h4>
                    <p style="color: var(--color-text-muted); font-size: 0.85rem;">Signature Blends</p>
                </div>
                <div style="border-left: 2px solid var(--color-cream-border); padding-left: 20px;">
                    <h4 style="font-size: 1.5rem; color: var(--color-primary); font-family: var(--font-heading);">100%</h4>
                    <p style="color: var(--color-text-muted); font-size: 0.85rem;">Ethical Sourcing</p>
                </div>
            </div>
        </div>

        <div style="background: #FFFFFF; border-radius: var(--radius-lg); padding: 35px; box-shadow: var(--shadow-md); border: 1px solid var(--color-cream-border);">
            <div style="font-size: 2.2rem; color: var(--color-accent); margin-bottom: 15px;">“</div>
            <p style="font-size: 1.1rem; font-style: italic; color: var(--color-primary); line-height: 1.7; margin-bottom: 20px;">
                The Cappuccino and Hazelnut Coffee at Grand Cafe are simply unmatched. The warm ambiance and prompt online ordering make it my favorite daily routine!
            </p>
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--color-accent-light); color: var(--color-accent); display: flex; align-items: center; justify-content: center; font-weight: bold;">
                    SR
                </div>
                <div>
                    <h4 style="font-size: 0.95rem; margin-bottom: 2px;">Sarah Reynolds</h4>
                    <small style="color: var(--color-text-muted);">Verified Regular & Coffee Enthusiast</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Call to Action Banner -->
<section style="background: var(--color-primary); color: #fff; padding: 60px 0; text-align: center;">
    <div class="container-narrow">
        <h2 style="color: #FFF8EE; font-size: 2.2rem; margin-bottom: 12px;">Ready for your daily caffeine boost?</h2>
        <p style="color: #D7CCC8; font-size: 1.05rem; margin-bottom: 28px;">
            Order your favorite espresso, latte, or cold brew online. Fast counter pickup & table service ready in minutes.
        </p>
        <div style="display: flex; justify-content: center; gap: 15px; flex-wrap: wrap;">
            <a href="menu.php" class="btn btn-accent" style="padding: 13px 30px; font-size: 1rem;">
                <i class="fa-solid fa-mug-saucer"></i> Browse Menu & Order
            </a>
            <?php if (!isLoggedIn()): ?>
                <a href="login.php" class="btn btn-outline" style="border-color: #D7CCC8; color: #FFF8EE; padding: 13px 30px;">
                    <i class="fa-solid fa-arrow-right-to-bracket"></i> Sign In to Account
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
