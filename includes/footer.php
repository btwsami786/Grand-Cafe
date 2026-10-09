<?php
/**
 * Grand Cafe - Footer Partial
 * Includes footer info, opening hours, contact details, toast container, and JS bundle.
 */
?>
    </main>

    <!-- Warm Cafe Footer -->
    <footer class="site-footer">
        <div class="footer-top">
            <div class="container footer-grid">
                <!-- Col 1: About Cafe -->
                <div class="footer-col">
                    <div class="brand-logo" style="color: #FFF8EE; margin-bottom: 15px;">
                        <span class="logo-icon" style="width: 36px; height: 36px; font-size: 1rem;"><i class="fa-solid fa-mug-hot"></i></span>
                        <span>Grand <span style="color: var(--color-accent);">Cafe</span></span>
                    </div>
                    <p>
                        Dedicated to the art of fine coffee. We source premium single-origin beans, hand-roast in micro-batches, and brew each cup with passion and precision.
                    </p>
                    <div style="display: flex; gap: 12px; margin-top: 15px;">
                        <a href="#" style="color: #D7CCC8; font-size: 1.1rem;"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" style="color: #D7CCC8; font-size: 1.1rem;"><i class="fa-brands fa-facebook"></i></a>
                        <a href="#" style="color: #D7CCC8; font-size: 1.1rem;"><i class="fa-brands fa-x-twitter"></i></a>
                    </div>
                </div>

                <!-- Col 2: Quick Links -->
                <div class="footer-col">
                    <h4>Quick Links</h4>
                    <ul class="footer-links">
                        <li><a href="index.php"><i class="fa-solid fa-angle-right"></i> Home</a></li>
                        <li><a href="menu.php"><i class="fa-solid fa-angle-right"></i> Coffee Menu</a></li>
                        <li><a href="my_orders.php"><i class="fa-solid fa-angle-right"></i> Order History</a></li>
                        <?php if (isAdmin()): ?>
                            <li><a href="admin.php"><i class="fa-solid fa-angle-right"></i> Admin Dashboard</a></li>
                        <?php endif; ?>
                        <li><a href="setup.php"><i class="fa-solid fa-angle-right"></i> Database Installer</a></li>
                    </ul>
                </div>

                <!-- Col 3: Opening Hours -->
                <div class="footer-col">
                    <h4>Brewing Hours</h4>
                    <div class="footer-hours-item">
                        <span>Monday – Friday</span>
                        <span>07:00 AM – 10:00 PM</span>
                    </div>
                    <div class="footer-hours-item">
                        <span>Saturday – Sunday</span>
                        <span>08:00 AM – 11:00 PM</span>
                    </div>
                    <div class="footer-hours-item">
                        <span>Takeaway & Delivery</span>
                        <span>Until 10:30 PM</span>
                    </div>
                </div>

                <!-- Col 4: Contact & Location -->
                <div class="footer-col">
                    <h4>Visit Grand Cafe</h4>
                    <p style="margin-bottom: 8px;">
                        <i class="fa-solid fa-location-dot" style="color: var(--color-accent); margin-right: 8px;"></i>
                        14 Artisan Boulevard, Heritage Square
                    </p>
                    <p style="margin-bottom: 8px;">
                        <i class="fa-solid fa-phone" style="color: var(--color-accent); margin-right: 8px;"></i>
                        +91 98765 43210
                    </p>
                    <p style="margin-bottom: 8px;">
                        <i class="fa-solid fa-envelope" style="color: var(--color-accent); margin-right: 8px;"></i>
                        orders@grandcafe.com
                    </p>
                </div>
            </div>
        </div>

        <div class="footer-bottom">
            <div class="container" style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                <p>&copy; <?php echo date('Y'); ?> Grand Cafe. All rights reserved.</p>
                <p style="color: #8D6E63;">Handcrafted with PHP &bull; MySQL &bull; Modern CSS</p>
            </div>
        </div>
    </footer>

    <!-- Toast Notification Container -->
    <div class="toast-container" id="toastContainer"></div>

    <!-- Client-side JavaScript -->
    <script src="assets/js/main.js"></script>
</body>
</html>
