/**
 * Grand Cafe - Core Client-Side Logic
 * Handles interactive shopping cart, quantity controls, search & category filtering,
 * modal dialogs, toast notifications, and checkout payment mode interactions.
 */

const STORAGE_KEY = 'grand_cafe_cart';
const CURRENCY = '₹';

// ----------------------------------------------------
// Cart Operations (localStorage backed)
// ----------------------------------------------------
function getCart() {
    try {
        const raw = localStorage.getItem(STORAGE_KEY);
        return raw ? JSON.parse(raw) : {};
    } catch (e) {
        console.error('Failed to parse cart storage:', e);
        return {};
    }
}

function saveCart(cart) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(cart));
        updateCartBadge();
        renderCartDrawer();
    } catch (e) {
        console.error('Failed to save cart storage:', e);
    }
}

function clearCart() {
    localStorage.removeItem(STORAGE_KEY);
    updateCartBadge();
    renderCartDrawer();
}

function addToCart(id, name, price, qty = 1, category = '') {
    qty = parseInt(qty, 10) || 1;
    if (qty <= 0) return;

    const cart = getCart();
    const itemId = String(id);

    if (cart[itemId]) {
        cart[itemId].quantity += qty;
    } else {
        cart[itemId] = {
            id: Number(id),
            name: name,
            price: parseFloat(price),
            quantity: qty,
            category: category
        };
    }

    saveCart(cart);
    showToast(`Added ${qty}x ${name} to order!`, 'success');
}

function updateCartItemQty(id, newQty) {
    const cart = getCart();
    const itemId = String(id);
    newQty = parseInt(newQty, 10);

    if (newQty <= 0) {
        delete cart[itemId];
    } else if (cart[itemId]) {
        cart[itemId].quantity = newQty;
    }

    saveCart(cart);
}

function removeFromCart(id) {
    const cart = getCart();
    const itemId = String(id);
    if (cart[itemId]) {
        const name = cart[itemId].name;
        delete cart[itemId];
        saveCart(cart);
        showToast(`Removed ${name} from order.`, 'info');
    }
}

function getCartTotals() {
    const cart = getCart();
    let totalItems = 0;
    let subtotal = 0;

    Object.values(cart).forEach(item => {
        totalItems += item.quantity;
        subtotal += item.price * item.quantity;
    });

    return { totalItems, subtotal };
}

function updateCartBadge() {
    const { totalItems } = getCartTotals();
    const badges = document.querySelectorAll('.cart-badge');
    badges.forEach(badge => {
        badge.textContent = totalItems;
        badge.style.display = totalItems > 0 ? 'inline-flex' : 'none';
    });
}

// ----------------------------------------------------
// Cart Drawer UI Rendering
// ----------------------------------------------------
function renderCartDrawer() {
    const cartItemsList = document.getElementById('cartItemsList');
    const cartSubtotalEl = document.getElementById('cartSubtotal');
    const cartTotalEl = document.getElementById('cartTotal');
    const checkoutBtn = document.getElementById('cartCheckoutBtn');
    
    if (!cartItemsList) return;

    const cart = getCart();
    const items = Object.values(cart);
    const { subtotal } = getCartTotals();

    if (items.length === 0) {
        cartItemsList.innerHTML = `
            <div class="cart-empty-state">
                <div class="empty-icon">☕</div>
                <h4>Your order is empty</h4>
                <p>Explore our menu and add your favorite brews!</p>
            </div>
        `;
        if (cartSubtotalEl) cartSubtotalEl.textContent = `${CURRENCY}0.00`;
        if (cartTotalEl) cartTotalEl.textContent = `${CURRENCY}0.00`;
        if (checkoutBtn) checkoutBtn.classList.add('disabled');
        return;
    }

    let html = '';
    items.forEach(item => {
        const itemLineTotal = (item.price * item.quantity).toFixed(2);
        html += `
            <div class="cart-item-row" data-id="${item.id}">
                <div class="cart-item-info">
                    <div class="cart-item-name">${escapeHtml(item.name)}</div>
                    <div class="cart-item-price">${CURRENCY}${item.price.toFixed(2)} each</div>
                </div>
                <div class="cart-item-actions">
                    <div class="qty-control">
                        <button type="button" class="qty-btn" onclick="updateCartItemQty(${item.id}, ${item.quantity - 1})">-</button>
                        <input type="text" class="qty-input" value="${item.quantity}" readonly>
                        <button type="button" class="qty-btn" onclick="updateCartItemQty(${item.id}, ${item.quantity + 1})">+</button>
                    </div>
                    <button type="button" class="cart-remove-btn" onclick="removeFromCart(${item.id})" title="Remove">✕</button>
                </div>
            </div>
        `;
    });

    cartItemsList.innerHTML = html;
    if (cartSubtotalEl) cartSubtotalEl.textContent = `${CURRENCY}${subtotal.toFixed(2)}`;
    if (cartTotalEl) cartTotalEl.textContent = `${CURRENCY}${subtotal.toFixed(2)}`;
    if (checkoutBtn) checkoutBtn.classList.remove('disabled');
}

function openCartDrawer() {
    const overlay = document.getElementById('cartOverlay');
    const drawer = document.getElementById('cartDrawer');
    if (overlay && drawer) {
        renderCartDrawer();
        overlay.classList.add('active');
        drawer.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeCartDrawer() {
    const overlay = document.getElementById('cartOverlay');
    const drawer = document.getElementById('cartDrawer');
    if (overlay && drawer) {
        overlay.classList.remove('active');
        drawer.classList.remove('active');
        document.body.style.overflow = '';
    }
}

function proceedToCheckout() {
    const { totalItems } = getCartTotals();
    if (totalItems <= 0) {
        showToast('Please add items to your cart before checkout.', 'warning');
        return;
    }

    const cart = getCart();
    // Prepare form to submit cart JSON to payment.php
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'payment.php';

    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'cart_payload';
    input.value = JSON.stringify(cart);

    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

// ----------------------------------------------------
// Toast Notifications
// ----------------------------------------------------
function showToast(message, type = 'success') {
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    
    let icon = '✓';
    if (type === 'warning') icon = '⚠';
    if (type === 'error' || type === 'danger') icon = '✕';
    if (type === 'info') icon = 'ℹ';

    toast.innerHTML = `<span style="font-weight: bold;">${icon}</span><span>${escapeHtml(message)}</span>`;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3200);
}

// ----------------------------------------------------
// Modal Dialogs
// ----------------------------------------------------
function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
}

function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ----------------------------------------------------
// Helper Utilities
// ----------------------------------------------------
function escapeHtml(str) {
    return String(str || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ----------------------------------------------------
// Initializations on DOMContentLoaded
// ----------------------------------------------------
document.addEventListener('DOMContentLoaded', () => {
    // 1. Initialize Cart Badge & Drawer
    updateCartBadge();
    renderCartDrawer();

    // 2. Mobile Menu Toggle
    const mobileToggle = document.getElementById('mobileMenuToggle');
    const navMenu = document.getElementById('navMenu');
    if (mobileToggle && navMenu) {
        mobileToggle.addEventListener('click', () => {
            navMenu.classList.toggle('show');
        });
    }

    // 3. Cart Drawer Events
    const cartToggleBtns = document.querySelectorAll('.cart-toggle-btn');
    cartToggleBtns.forEach(btn => btn.addEventListener('click', openCartDrawer));

    const closeDrawerBtn = document.getElementById('closeCartDrawer');
    if (closeDrawerBtn) closeDrawerBtn.addEventListener('click', closeCartDrawer);

    const cartOverlay = document.getElementById('cartOverlay');
    if (cartOverlay) cartOverlay.addEventListener('click', closeCartDrawer);

    const checkoutBtn = document.getElementById('cartCheckoutBtn');
    if (checkoutBtn) checkoutBtn.addEventListener('click', proceedToCheckout);

    // 4. Menu Filtering & Searching
    const categoryTabs = document.querySelectorAll('.category-tab');
    const coffeeCards = document.querySelectorAll('.coffee-card');
    const menuSearchInput = document.getElementById('menuSearchInput');

    function filterMenuItems() {
        const activeTab = document.querySelector('.category-tab.active');
        const selectedCategory = activeTab ? activeTab.getAttribute('data-category') : 'all';
        const searchQuery = menuSearchInput ? menuSearchInput.value.toLowerCase().trim() : '';

        coffeeCards.forEach(card => {
            const cardCat = card.getAttribute('data-category') || '';
            const cardName = (card.getAttribute('data-name') || '').toLowerCase();

            const matchesCategory = (selectedCategory === 'all' || cardCat.toLowerCase() === selectedCategory.toLowerCase());
            const matchesSearch = cardName.includes(searchQuery);

            if (matchesCategory && matchesSearch) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    categoryTabs.forEach(tab => {
        tab.addEventListener('click', () => {
            categoryTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');
            filterMenuItems();
        });
    });

    if (menuSearchInput) {
        menuSearchInput.addEventListener('input', filterMenuItems);
    }

    // 5. Quantity Steppers on Menu Cards
    document.querySelectorAll('.qty-control').forEach(control => {
        const input = control.querySelector('.qty-input');
        const minusBtn = control.querySelector('.qty-minus');
        const plusBtn = control.querySelector('.qty-plus');

        if (input && minusBtn && plusBtn) {
            minusBtn.addEventListener('click', () => {
                let current = parseInt(input.value, 10) || 1;
                if (current > 1) input.value = current - 1;
            });
            plusBtn.addEventListener('click', () => {
                let current = parseInt(input.value, 10) || 1;
                input.value = current + 1;
            });
        }
    });

    // 6. Flash Alert Dismiss Buttons
    document.querySelectorAll('.flash-close').forEach(btn => {
        btn.addEventListener('click', (e) => {
            const alert = e.target.closest('.flash-alert');
            if (alert) alert.remove();
        });
    });

    // 7. Payment Mode Selector Interactions
    const paymentOptions = document.querySelectorAll('.payment-option');
    paymentOptions.forEach(opt => {
        opt.addEventListener('click', () => {
            paymentOptions.forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            const radio = opt.querySelector('input[type="radio"]');
            if (radio) radio.checked = true;

            const mode = opt.getAttribute('data-mode');
            document.querySelectorAll('.payment-interactive-panel').forEach(panel => {
                panel.classList.remove('active');
            });
            const activePanel = document.getElementById(`panel-${mode}`);
            if (activePanel) activePanel.classList.add('active');
        });
    });
});
