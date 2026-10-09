<?php
/**
 * Grand Cafe - Checkout & Payment Processing
 * Reviews order summary, verifies prices from MySQL, accepts payment methods (Cash, UPI, Card),
 * commits order transaction, and generates official digital receipt / invoice view.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

// Checkout requires authentication
requireLogin('payment.php');

$currentUser = currentUser();
$pageTitle = 'Order Summary & Payment';
$error = '';
$receiptOrder = null;
$receiptItems = [];

// -------------------------------------------------------------------------
// Case 1: Viewing an existing receipt (e.g. ?view_receipt=123)
// -------------------------------------------------------------------------
if (isset($_GET['view_receipt'])) {
    $viewOrderId = (int)$_GET['view_receipt'];
    try {
        $stmtOrder = $pdo->prepare("SELECT o.*, u.username, u.email FROM `orders` o JOIN `users` u ON o.user_id = u.id WHERE o.id = ? AND (o.user_id = ? OR ? = 'admin') LIMIT 1");
        $stmtOrder->execute([$viewOrderId, $currentUser['id'], $currentUser['role']]);
        $receiptOrder = $stmtOrder->fetch();

        if ($receiptOrder) {
            $stmtItems = $pdo->prepare("SELECT oi.*, cm.item_name, cm.category FROM `order_items` oi JOIN `coffee_menu` cm ON oi.coffee_id = cm.id WHERE oi.order_id = ?");
            $stmtItems->execute([$viewOrderId]);
            $receiptItems = $stmtItems->fetchAll();
            $pageTitle = 'Receipt #' . $receiptOrder['id'];
        } else {
            setFlash('error', 'Order receipt not found or access denied.');
            header('Location: my_orders.php');
            exit;
        }
    } catch (PDOException $e) {
        setFlash('error', 'Error fetching receipt: ' . $e->getMessage());
        header('Location: my_orders.php');
        exit;
    }
}

// -------------------------------------------------------------------------
// Case 2: Placing / Confirming the Order (POST)
// -------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'confirm_order') {
    $rawCartJson = $_POST['cart_data'] ?? '';
    $paymentMode = $_POST['payment_mode'] ?? 'Cash';
    $cartItems = json_decode($rawCartJson, true);

    $validModes = ['Cash', 'UPI', 'Card'];
    if (!in_array($paymentMode, $validModes)) {
        $paymentMode = 'Cash';
    }

    if (empty($cartItems) || !is_array($cartItems)) {
        $error = 'Your cart is empty. Please select coffee items before confirming.';
    } else {
        try {
            // Verify items & official prices directly from the database
            $verifiedItems = [];
            $computedTotal = 0.00;

            $coffeeIds = array_keys($cartItems);
            $placeholders = implode(',', array_fill(0, count($coffeeIds), '?'));
            
            $stmtCheck = $pdo->prepare("SELECT `id`, `item_name`, `price`, `status`, `category` FROM `coffee_menu` WHERE `id` IN ($placeholders)");
            $stmtCheck->execute(array_values($coffeeIds));
            $dbCoffees = $stmtCheck->fetchAll();

            $dbCoffeeMap = [];
            foreach ($dbCoffees as $row) {
                $dbCoffeeMap[$row['id']] = $row;
            }

            foreach ($cartItems as $itemId => $itemData) {
                $itemId = (int)$itemId;
                $quantity = max(1, (int)($itemData['quantity'] ?? 1));

                if (!isset($dbCoffeeMap[$itemId])) {
                    throw new Exception("One of your selected coffee items is no longer available.");
                }

                $dbItem = $dbCoffeeMap[$itemId];
                if ($dbItem['status'] !== 'available') {
                    throw new Exception("Item '{$dbItem['item_name']}' is currently out of stock.");
                }

                $unitPrice = (float)$dbItem['price'];
                $lineTotal = $unitPrice * $quantity;
                $computedTotal += $lineTotal;

                $verifiedItems[] = [
                    'coffee_id'   => $itemId,
                    'item_name'   => $dbItem['item_name'],
                    'category'    => $dbItem['category'],
                    'quantity'    => $quantity,
                    'unit_price'  => $unitPrice,
                    'line_total'  => $lineTotal
                ];
            }

            if (empty($verifiedItems)) {
                throw new Exception("No valid items in order.");
            }

            // Begin Atomic Database Transaction
            $pdo->beginTransaction();

            $insertOrderStmt = $pdo->prepare("INSERT INTO `orders` (`user_id`, `total_amount`, `payment_mode`, `order_status`) VALUES (?, ?, ?, 'Pending')");
            $insertOrderStmt->execute([
                $currentUser['id'],
                $computedTotal,
                $paymentMode
            ]);
            $newOrderId = (int)$pdo->lastInsertId();

            $insertItemStmt = $pdo->prepare("INSERT INTO `order_items` (`order_id`, `coffee_id`, `quantity`, `unit_price`) VALUES (?, ?, ?, ?)");
            foreach ($verifiedItems as $vItem) {
                $insertItemStmt->execute([
                    $newOrderId,
                    $vItem['coffee_id'],
                    $vItem['quantity'],
                    $vItem['unit_price']
                ]);
            }

            // Commit Transaction
            $pdo->commit();

            // Set up variables to render the Digital Receipt immediately
            $receiptOrder = [
                'id'           => $newOrderId,
                'user_id'      => $currentUser['id'],
                'username'     => $currentUser['username'],
                'email'        => $currentUser['email'],
                'total_amount' => $computedTotal,
                'payment_mode' => $paymentMode,
                'order_status' => 'Pending',
                'created_at'   => date('Y-m-d H:i:s')
            ];
            $receiptItems = $verifiedItems;
            $isNewlyPlaced = true;

        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

// -------------------------------------------------------------------------
// Case 3: Initial Checkout View (Items passed from cart_payload)
// -------------------------------------------------------------------------
$previewItems = [];
$previewSubtotal = 0.0;

if (!$receiptOrder) {
    $cartPayload = $_POST['cart_payload'] ?? '';
    if (!empty($cartPayload)) {
        $parsed = json_decode($cartPayload, true);
        if (is_array($parsed)) {
            // Fetch live prices from DB to ensure preview integrity
            $ids = array_keys($parsed);
            if (!empty($ids)) {
                $placeholders = implode(',', array_fill(0, count($ids), '?'));
                $stmtPrev = $pdo->prepare("SELECT * FROM `coffee_menu` WHERE `id` IN ($placeholders)");
                $stmtPrev->execute($ids);
                $rows = $stmtPrev->fetchAll();

                $map = [];
                foreach ($rows as $r) {
                    $map[$r['id']] = $r;
                }

                foreach ($parsed as $cid => $cdata) {
                    if (isset($map[$cid])) {
                        $qty = max(1, (int)($cdata['quantity'] ?? 1));
                        $unitPrice = (float)$map[$cid]['price'];
                        $lineTotal = $unitPrice * $qty;
                        $previewSubtotal += $lineTotal;

                        $previewItems[] = [
                            'id'         => $cid,
                            'item_name'  => $map[$cid]['item_name'],
                            'category'   => $map[$cid]['category'],
                            'price'      => $unitPrice,
                            'quantity'   => $qty,
                            'line_total' => $lineTotal
                        ];
                    }
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding">

    <!-- IF DISPLAYING RECEIPT / INVOICE VIEW -->
    <?php if ($receiptOrder): ?>
        <div class="receipt-wrapper">
            <div class="receipt-card">
                <div class="receipt-header">
                    <div style="font-size: 2.8rem; margin-bottom: 5px;">☕</div>
                    <h2 style="font-size: 1.8rem; margin-bottom: 4px;">Grand Cafe</h2>
                    <p style="color: var(--color-text-muted); font-size: 0.88rem; margin-bottom: 12px;">
                        Artisan Roastery & Barista Bar &bull; Order Receipt
                    </p>
                    <span class="status-badge status-<?php echo strtolower($receiptOrder['order_status']); ?>" style="font-size: 0.85rem; padding: 6px 16px;">
                        <i class="fa-solid fa-clock"></i> Status: <?php echo e($receiptOrder['order_status']); ?>
                    </span>
                </div>

                <div class="receipt-meta-grid">
                    <div class="receipt-meta-item">
                        <strong>Order Reference</strong>
                        <span>#GC-<?php echo str_pad($receiptOrder['id'], 5, '0', STR_PAD_LEFT); ?></span>
                    </div>
                    <div class="receipt-meta-item">
                        <strong>Date & Time</strong>
                        <span><?php echo date('M d, Y &bull; h:i A', strtotime($receiptOrder['created_at'])); ?></span>
                    </div>
                    <div class="receipt-meta-item">
                        <strong>Customer Name</strong>
                        <span><?php echo e($receiptOrder['username'] ?? $currentUser['username']); ?></span>
                    </div>
                    <div class="receipt-meta-item">
                        <strong>Payment Method</strong>
                        <span><i class="fa-solid fa-credit-card"></i> <?php echo e($receiptOrder['payment_mode']); ?></span>
                    </div>
                </div>

                <table class="receipt-table">
                    <thead>
                        <tr>
                            <th>Item Description</th>
                            <th style="text-align: center;">Qty</th>
                            <th class="text-right">Unit Price</th>
                            <th class="text-right">Line Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($receiptItems as $rItem): ?>
                            <tr>
                                <td>
                                    <strong><?php echo e($rItem['item_name']); ?></strong>
                                    <div style="font-size: 0.8rem; color: var(--color-text-muted);">
                                        <?php echo e($rItem['category'] ?? 'Coffee'); ?>
                                    </div>
                                </td>
                                <td style="text-align: center;"><?php echo e($rItem['quantity']); ?></td>
                                <td class="text-right"><?php echo formatPrice($rItem['unit_price']); ?></td>
                                <td class="text-right" style="font-weight: 600;">
                                    <?php echo formatPrice($rItem['quantity'] * $rItem['unit_price']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="receipt-total-box">
                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 8px; color: var(--color-text-muted);">
                        <span>Subtotal</span>
                        <span><?php echo formatPrice($receiptOrder['total_amount']); ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 0.95rem; margin-bottom: 12px; color: var(--color-text-muted);">
                        <span>Applicable GST / Taxes</span>
                        <span style="color: var(--color-success); font-weight: 600;">Included (0.00)</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; font-size: 1.35rem; font-weight: 700; color: var(--color-primary); font-family: var(--font-heading); padding-top: 10px; border-top: 2px dashed var(--color-cream-border);">
                        <span>Total Paid</span>
                        <span><?php echo formatPrice($receiptOrder['total_amount']); ?></span>
                    </div>
                </div>

                <div style="text-align: center; margin-top: 30px; padding: 15px; background: var(--color-cream-bg); border-radius: var(--radius-md);">
                    <p style="font-size: 0.88rem; color: var(--color-text-muted); margin: 0;">
                        ☕ Thank you for choosing Grand Cafe! Please present this receipt or Order ID at the counter for pickup.
                    </p>
                </div>

                <div class="receipt-actions">
                    <button type="button" class="btn btn-outline" onclick="window.print()" style="flex: 1;">
                        <i class="fa-solid fa-print"></i> Print Invoice
                    </button>
                    <a href="my_orders.php" class="btn btn-primary" style="flex: 1;">
                        <i class="fa-solid fa-list-check"></i> Track in My Orders
                    </a>
                    <a href="menu.php" class="btn btn-accent" style="flex: 1;">
                        <i class="fa-solid fa-mug-saucer"></i> Order More
                    </a>
                </div>
            </div>
        </div>

        <?php if (!empty($isNewlyPlaced)): ?>
            <!-- Trigger clearing the client-side cart upon successful placement -->
            <script>
                document.addEventListener('DOMContentLoaded', () => {
                    clearCart();
                    showToast('Order placed successfully! Your barista has received it.', 'success');
                });
            </script>
        <?php endif; ?>

    <!-- IF CHECKOUT / ORDER REVIEW FORM -->
    <?php else: ?>

        <div class="section-header">
            <span class="section-subtitle">Review & Confirm</span>
            <h2 class="section-title">Order Summary & Payment</h2>
        </div>

        <?php if (!empty($error)): ?>
            <div class="flash-alert flash-error" style="max-width: 800px; margin: 0 auto 30px;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-triangle-exclamation"></i>
                    <span><?php echo e($error); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!empty($previewItems)): ?>
            <form method="POST" action="payment.php" id="orderConfirmForm">
                <input type="hidden" name="action" value="confirm_order">
                <input type="hidden" name="cart_data" id="cartDataInput" value='<?php echo json_encode(array_column($previewItems, null, 'id')); ?>'>

                <div class="checkout-grid">
                    <!-- Left Column: Itemized Order Summary -->
                    <div class="checkout-card">
                        <div class="checkout-card-header">
                            <h3><i class="fa-solid fa-receipt" style="color: var(--color-accent);"></i> Order Review</h3>
                        </div>

                        <div style="margin-bottom: 20px;">
                            <?php foreach ($previewItems as $pItem): ?>
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid var(--color-cream-border);">
                                    <div>
                                        <h4 style="font-size: 1rem; margin-bottom: 2px; color: var(--color-primary);">
                                            <?php echo e($pItem['item_name']); ?>
                                        </h4>
                                        <small style="color: var(--color-text-muted);">
                                            <?php echo formatPrice($pItem['price']); ?> &times; <?php echo e($pItem['quantity']); ?>
                                        </small>
                                    </div>
                                    <div style="font-weight: 700; color: var(--color-primary); font-family: var(--font-heading);">
                                        <?php echo formatPrice($pItem['line_total']); ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="background: var(--color-cream-bg); padding: 18px; border-radius: var(--radius-md); border: 1px solid var(--color-cream-border);">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 8px; color: var(--color-text-muted); font-size: 0.95rem;">
                                <span>Subtotal</span>
                                <span><?php echo formatPrice($previewSubtotal); ?></span>
                            </div>
                            <div style="display: flex; justify-content: space-between; margin-bottom: 12px; color: var(--color-text-muted); font-size: 0.95rem;">
                                <span>Taxes & Service Charge</span>
                                <span style="color: var(--color-success); font-weight: 600;">Complimentary</span>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 1.3rem; font-weight: 700; color: var(--color-primary); font-family: var(--font-heading); padding-top: 10px; border-top: 2px dashed var(--color-cream-border);">
                                <span>Total Payable</span>
                                <span><?php echo formatPrice($previewSubtotal); ?></span>
                            </div>
                        </div>

                        <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid var(--color-cream-border); display: flex; align-items: center; gap: 12px;">
                            <div style="width: 40px; height: 40px; background: var(--color-accent-light); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--color-accent);">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div>
                                <strong style="font-size: 0.9rem; color: var(--color-primary);">Ordering As:</strong>
                                <div style="font-size: 0.85rem; color: var(--color-text-muted);">
                                    <?php echo e($currentUser['username']); ?> (<?php echo e($currentUser['email']); ?>)
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Payment Method Selection -->
                    <div class="checkout-card">
                        <div class="checkout-card-header">
                            <h3><i class="fa-solid fa-wallet" style="color: var(--color-accent);"></i> Payment Method</h3>
                        </div>

                        <p style="color: var(--color-text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                            Choose your preferred payment option to complete your coffee order:
                        </p>

                        <div class="payment-modes-list">
                            <!-- Option 1: Cash -->
                            <label class="payment-option selected" data-mode="Cash">
                                <input type="radio" name="payment_mode" value="Cash" checked>
                                <div class="payment-option-details">
                                    <h4><i class="fa-solid fa-money-bill-wave" style="color: var(--color-success); margin-right: 6px;"></i> Cash on Counter</h4>
                                    <p>Pay with cash directly at the barista counter upon pickup.</p>
                                </div>
                            </label>

                            <!-- Option 2: UPI -->
                            <label class="payment-option" data-mode="UPI">
                                <input type="radio" name="payment_mode" value="UPI">
                                <div class="payment-option-details">
                                    <h4><i class="fa-solid fa-qrcode" style="color: #6366F1; margin-right: 6px;"></i> Instant UPI Payment</h4>
                                    <p>Scan QR code with Google Pay, PhonePe, Paytm, or BHIM.</p>
                                </div>
                            </label>

                            <!-- Option 3: Card -->
                            <label class="payment-option" data-mode="Card">
                                <input type="radio" name="payment_mode" value="Card">
                                <div class="payment-option-details">
                                    <h4><i class="fa-solid fa-credit-card" style="color: var(--color-accent); margin-right: 6px;"></i> Credit / Debit Card</h4>
                                    <p>Pay securely via Visa, MasterCard, or RuPay card.</p>
                                </div>
                            </label>
                        </div>

                        <!-- Interactive Panel: Cash -->
                        <div class="payment-interactive-panel active" id="panel-Cash">
                            <h4 style="color: var(--color-primary); font-size: 0.95rem; margin-bottom: 6px;">
                                <i class="fa-solid fa-circle-check" style="color: var(--color-success);"></i> Counter Order Selected
                            </h4>
                            <p style="font-size: 0.85rem; color: var(--color-text-muted); margin: 0;">
                                Your order will be queued immediately. Please have <?php echo formatPrice($previewSubtotal); ?> ready at pickup.
                            </p>
                        </div>

                        <!-- Interactive Panel: UPI -->
                        <div class="payment-interactive-panel" id="panel-UPI">
                            <h4 style="color: var(--color-primary); font-size: 0.95rem; margin-bottom: 10px;">
                                <i class="fa-solid fa-qrcode"></i> Scan to Pay <?php echo formatPrice($previewSubtotal); ?>
                            </h4>
                            <div style="display: flex; align-items: center; gap: 20px;">
                                <div style="background: #fff; padding: 10px; border-radius: 8px; border: 1px solid var(--color-cream-border); text-align: center;">
                                    <!-- Stylized Simulated QR Code -->
                                    <svg width="100" height="100" viewBox="0 0 100 100" style="display: block;">
                                        <rect width="100" height="100" fill="#ffffff" />
                                        <rect x="5" y="5" width="30" height="30" fill="#2C1810" />
                                        <rect x="10" y="10" width="20" height="20" fill="#ffffff" />
                                        <rect x="15" y="15" width="10" height="10" fill="#2C1810" />
                                        
                                        <rect x="65" y="5" width="30" height="30" fill="#2C1810" />
                                        <rect x="70" y="10" width="20" height="20" fill="#ffffff" />
                                        <rect x="75" y="15" width="10" height="10" fill="#2C1810" />
                                        
                                        <rect x="5" y="65" width="30" height="30" fill="#2C1810" />
                                        <rect x="10" y="70" width="20" height="20" fill="#ffffff" />
                                        <rect x="15" y="75" width="10" height="10" fill="#2C1810" />

                                        <circle cx="50" cy="50" r="10" fill="#C68B59" />
                                    </svg>
                                    <small style="font-size: 0.7rem; color: var(--color-text-muted);">Grand Cafe UPI</small>
                                </div>
                                <div>
                                    <div style="font-size: 0.85rem; font-weight: 600; color: var(--color-primary);">UPI ID:</div>
                                    <code style="background: #fff; padding: 4px 8px; border-radius: 4px; border: 1px solid #ddd; font-size: 0.85rem;">grandcafe@upi</code>
                                    <div style="margin-top: 10px;">
                                        <input type="text" class="form-control" placeholder="Enter UPI Ref / Transaction ID (Optional)" style="font-size: 0.85rem; padding: 8px 12px;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Interactive Panel: Card -->
                        <div class="payment-interactive-panel" id="panel-Card">
                            <h4 style="color: var(--color-primary); font-size: 0.95rem; margin-bottom: 12px;">
                                <i class="fa-solid fa-lock"></i> Card Details (Simulation)
                            </h4>
                            <div class="form-group" style="margin-bottom: 12px;">
                                <input type="text" class="form-control" placeholder="Cardholder Name" value="<?php echo e($currentUser['username']); ?>" style="padding: 8px 12px; font-size: 0.9rem;">
                            </div>
                            <div class="form-group" style="margin-bottom: 12px;">
                                <input type="text" class="form-control" placeholder="Card Number (e.g. 4532 &bull;&bull;&bull;&bull; &bull;&bull;&bull;&bull; 1024)" maxlength="19" style="padding: 8px 12px; font-size: 0.9rem;">
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                                <input type="text" class="form-control" placeholder="MM/YY" maxlength="5" style="padding: 8px 12px; font-size: 0.9rem;">
                                <input type="password" class="form-control" placeholder="CVV" maxlength="4" style="padding: 8px 12px; font-size: 0.9rem;">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-accent" style="width: 100%; padding: 14px; font-size: 1.05rem; margin-top: 10px;">
                            <i class="fa-solid fa-circle-check"></i> Place Order &bull; <?php echo formatPrice($previewSubtotal); ?>
                        </button>

                        <div style="text-align: center; margin-top: 15px;">
                            <a href="menu.php" style="color: var(--color-text-muted); font-size: 0.88rem;">
                                <i class="fa-solid fa-arrow-left"></i> Return to menu to add more items
                            </a>
                        </div>
                    </div>
                </div>
            </form>
        <?php else: ?>
            <!-- Fallback if user lands here with no cart -->
            <div style="text-align: center; max-width: 500px; margin: 40px auto; background: #fff; padding: 40px; border-radius: var(--radius-lg); box-shadow: var(--shadow-sm); border: 1px solid var(--color-cream-border);">
                <div style="font-size: 3.5rem; margin-bottom: 15px;">🛒</div>
                <h3>Your Order is Empty</h3>
                <p style="color: var(--color-text-muted); margin-bottom: 25px;">
                    You don't have any coffee items selected for checkout right now.
                </p>
                <a href="menu.php" class="btn btn-accent">
                    <i class="fa-solid fa-mug-saucer"></i> Browse Menu & Add Coffee
                </a>
            </div>
        <?php endif; ?>

    <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
