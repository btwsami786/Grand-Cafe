<?php
/**
 * Grand Cafe - Customer Order History & Cancellation
 * Allows logged-in customers to review past orders, view receipts, and cancel pending orders.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

requireLogin('my_orders.php');

$currentUser = currentUser();
$pageTitle = 'My Coffee Orders';

// ----------------------------------------------------
// Handle Order Cancellation Request
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel_order') {
    $orderId = (int)($_POST['order_id'] ?? 0);

    try {
        // Verify order exists, belongs to user, and is still in 'Pending' status
        $checkStmt = $pdo->prepare("SELECT `id`, `order_status` FROM `orders` WHERE `id` = ? AND `user_id` = ? LIMIT 1");
        $checkStmt->execute([$orderId, $currentUser['id']]);
        $order = $checkStmt->fetch();

        if (!$order) {
            setFlash('error', 'Order not found or permission denied.');
        } elseif ($order['order_status'] !== 'Pending') {
            setFlash('warning', "Order #GC-{$orderId} cannot be cancelled because it is already {$order['order_status']}.");
        } else {
            // Cancel the order
            $updateStmt = $pdo->prepare("UPDATE `orders` SET `order_status` = 'Cancelled' WHERE `id` = ? AND `user_id` = ?");
            $updateStmt->execute([$orderId, $currentUser['id']]);
            setFlash('success', "Order #GC-" . str_pad($orderId, 5, '0', STR_PAD_LEFT) . " has been successfully cancelled.");
        }
    } catch (PDOException $e) {
        setFlash('error', 'Error cancelling order: ' . $e->getMessage());
    }

    header('Location: my_orders.php');
    exit;
}

// ----------------------------------------------------
// Fetch User's Orders with Item Details
// ----------------------------------------------------
$userOrders = [];
try {
    $ordersStmt = $pdo->prepare("SELECT * FROM `orders` WHERE `user_id` = ? ORDER BY `created_at` DESC");
    $ordersStmt->execute([$currentUser['id']]);
    $userOrders = $ordersStmt->fetchAll();

    if (!empty($userOrders)) {
        // Collect order IDs to fetch items in a single query
        $orderIds = array_column($userOrders, 'id');
        $placeholders = implode(',', array_fill(0, count($orderIds), '?'));

        $itemsStmt = $pdo->prepare("
            SELECT oi.*, cm.item_name, cm.category 
            FROM `order_items` oi 
            JOIN `coffee_menu` cm ON oi.coffee_id = cm.id 
            WHERE oi.order_id IN ($placeholders)
            ORDER BY oi.id ASC
        ");
        $itemsStmt->execute($orderIds);
        $allItems = $itemsStmt->fetchAll();

        // Group items by order_id
        $itemsByOrder = [];
        foreach ($allItems as $item) {
            $itemsByOrder[$item['order_id']][] = $item;
        }

        // Attach to userOrders
        foreach ($userOrders as &$ord) {
            $ord['items'] = $itemsByOrder[$ord['id']] ?? [];
        }
        unset($ord);
    }
} catch (PDOException $e) {
    setFlash('error', 'Failed to retrieve order history: ' . $e->getMessage());
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding">
    <div class="section-header" style="text-align: left; margin-bottom: 30px;">
        <span class="section-subtitle">Account Dashboard</span>
        <h2 class="section-title">My Coffee Orders</h2>
        <p style="color: var(--color-text-muted);">
            View your current brews, inspect invoices, or manage pending requests.
        </p>
    </div>

    <?php if (!empty($userOrders)): ?>
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order Ref</th>
                        <th>Date & Time</th>
                        <th>Coffee Items</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($userOrders as $ord): ?>
                        <tr>
                            <td>
                                <strong>#GC-<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                            </td>
                            <td>
                                <div style="font-weight: 500; color: var(--color-primary);">
                                    <?php echo date('M d, Y', strtotime($ord['created_at'])); ?>
                                </div>
                                <small style="color: var(--color-text-muted);">
                                    <?php echo date('h:i A', strtotime($ord['created_at'])); ?>
                                </small>
                            </td>
                            <td>
                                <div style="max-width: 320px;">
                                    <?php foreach ($ord['items'] as $it): ?>
                                        <div style="font-size: 0.88rem; margin-bottom: 3px;">
                                            &bull; <strong><?php echo e($it['quantity']); ?>x</strong> <?php echo e($it['item_name']); ?>
                                            <span style="color: var(--color-text-muted);">(<?php echo formatPrice($it['unit_price']); ?>)</span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </td>
                            <td style="font-weight: 700; font-family: var(--font-heading); font-size: 1.05rem; color: var(--color-primary);">
                                <?php echo formatPrice($ord['total_amount']); ?>
                            </td>
                            <td>
                                <span style="font-size: 0.85rem; color: var(--color-text-muted);">
                                    <i class="fa-solid fa-credit-card" style="color: var(--color-accent); margin-right: 4px;"></i>
                                    <?php echo e($ord['payment_mode']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="status-badge status-<?php echo strtolower($ord['order_status']); ?>">
                                    <?php 
                                        if ($ord['order_status'] === 'Pending') echo '<i class="fa-solid fa-clock"></i> ';
                                        elseif ($ord['order_status'] === 'Confirmed') echo '<i class="fa-solid fa-check"></i> ';
                                        else echo '<i class="fa-solid fa-ban"></i> ';
                                        echo e($ord['order_status']); 
                                    ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: inline-flex; align-items: center; gap: 8px;">
                                    <!-- View Receipt Button -->
                                    <a href="payment.php?view_receipt=<?php echo $ord['id']; ?>" class="btn btn-outline btn-sm" title="View Digital Receipt">
                                        <i class="fa-solid fa-receipt"></i> Receipt
                                    </a>

                                    <!-- Cancel Order Button (Available only if Pending) -->
                                    <?php if ($ord['order_status'] === 'Pending'): ?>
                                        <form method="POST" action="my_orders.php" style="display: inline;" onsubmit="return confirm('Are you sure you want to cancel Order #GC-<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?>?');">
                                            <input type="hidden" name="action" value="cancel_order">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <button type="submit" class="btn btn-danger-outline btn-sm" title="Cancel this pending order">
                                                <i class="fa-solid fa-xmark"></i> Cancel
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <div style="text-align: center; background: #FFFFFF; padding: 60px 20px; border-radius: var(--radius-lg); border: 1px dashed var(--color-cream-border); box-shadow: var(--shadow-sm); max-width: 600px; margin: 40px auto;">
            <div style="font-size: 3.5rem; margin-bottom: 15px;">📜</div>
            <h3>No Orders Placed Yet</h3>
            <p style="color: var(--color-text-muted); margin-bottom: 25px;">
                You haven't placed any coffee orders with us yet. Start your journey with our freshly roasted espresso or creamy lattes!
            </p>
            <a href="menu.php" class="btn btn-accent">
                <i class="fa-solid fa-mug-saucer"></i> Explore Coffee Menu
            </a>
        </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
