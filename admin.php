<?php
/**
 * Grand Cafe - Administrator Control Center
 * Full CRUD dashboard for Coffee Menu Management and Customer Orders Management.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth_helper.php';

// Strict Admin Access Guard
requireAdmin();

$pageTitle = 'Admin Dashboard';
$currentUser = currentUser();

// =========================================================================
// 1. Process Menu Actions (Create, Update, Delete, Toggle Status)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['menu_action'])) {
    $action = $_POST['menu_action'];

    // ADD NEW MENU ITEM
    if ($action === 'add') {
        $itemName = trim($_POST['item_name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? 'Hot Coffee');
        $status = in_array($_POST['status'] ?? '', ['available', 'out_of_stock']) ? $_POST['status'] : 'available';

        if (empty($itemName) || $price <= 0) {
            setFlash('error', 'Please provide a valid coffee item name and price.');
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO `coffee_menu` (`item_name`, `price`, `category`, `status`) VALUES (?, ?, ?, ?)");
                $stmt->execute([$itemName, $price, $category, $status]);
                setFlash('success', "Added '{$itemName}' to the coffee menu.");
            } catch (PDOException $e) {
                setFlash('error', 'Failed to add item: ' . $e->getMessage());
            }
        }
    }

    // EDIT EXISTING MENU ITEM
    elseif ($action === 'edit') {
        $id = (int)($_POST['item_id'] ?? 0);
        $itemName = trim($_POST['item_name'] ?? '');
        $price = (float)($_POST['price'] ?? 0);
        $category = trim($_POST['category'] ?? 'Hot Coffee');
        $status = in_array($_POST['status'] ?? '', ['available', 'out_of_stock']) ? $_POST['status'] : 'available';

        if ($id <= 0 || empty($itemName) || $price <= 0) {
            setFlash('error', 'Invalid menu item data provided.');
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE `coffee_menu` SET `item_name` = ?, `price` = ?, `category` = ?, `status` = ? WHERE `id` = ?");
                $stmt->execute([$itemName, $price, $category, $status, $id]);
                setFlash('success', "Updated '{$itemName}' successfully.");
            } catch (PDOException $e) {
                setFlash('error', 'Failed to update item: ' . $e->getMessage());
            }
        }
    }

    // TOGGLE AVAILABILITY STATUS
    elseif ($action === 'toggle_status') {
        $id = (int)($_POST['item_id'] ?? 0);
        try {
            $stmt = $pdo->prepare("SELECT `status`, `item_name` FROM `coffee_menu` WHERE `id` = ?");
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if ($item) {
                $newStatus = ($item['status'] === 'available') ? 'out_of_stock' : 'available';
                $upd = $pdo->prepare("UPDATE `coffee_menu` SET `status` = ? WHERE `id` = ?");
                $upd->execute([$newStatus, $id]);
                setFlash('info', "'{$item['item_name']}' is now marked as {$newStatus}.");
            }
        } catch (PDOException $e) {
            setFlash('error', 'Failed to update status: ' . $e->getMessage());
        }
    }

    // DELETE MENU ITEM
    elseif ($action === 'delete') {
        $id = (int)($_POST['item_id'] ?? 0);
        try {
            // First check if item is referenced in any orders
            $check = $pdo->prepare("SELECT COUNT(*) FROM `order_items` WHERE `coffee_id` = ?");
            $check->execute([$id]);
            $orderCount = $check->fetchColumn();

            if ($orderCount > 0) {
                // To preserve historical receipts, mark out_of_stock instead of deleting if referenced
                $upd = $pdo->prepare("UPDATE `coffee_menu` SET `status` = 'out_of_stock' WHERE `id` = ?");
                $upd->execute([$id]);
                setFlash('warning', "Item is referenced in {$orderCount} past order(s). To protect historical records, it was marked as 'Out of Stock' instead of deleted.");
            } else {
                $stmt = $pdo->prepare("DELETE FROM `coffee_menu` WHERE `id` = ?");
                $stmt->execute([$id]);
                setFlash('success', 'Coffee item deleted successfully.');
            }
        } catch (PDOException $e) {
            setFlash('error', 'Failed to delete item: ' . $e->getMessage());
        }
    }

    header('Location: admin.php?tab=menu');
    exit;
}

// =========================================================================
// 2. Process Order Actions (Update Status, Cancel, Delete)
// =========================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['order_action'])) {
    $action = $_POST['order_action'];
    $orderId = (int)($_POST['order_id'] ?? 0);

    // UPDATE STATUS
    if ($action === 'update_status') {
        $newStatus = $_POST['order_status'] ?? 'Pending';
        if (in_array($newStatus, ['Pending', 'Confirmed', 'Cancelled'])) {
            try {
                $stmt = $pdo->prepare("UPDATE `orders` SET `order_status` = ? WHERE `id` = ?");
                $stmt->execute([$newStatus, $orderId]);
                setFlash('success', "Order #GC-" . str_pad($orderId, 5, '0', STR_PAD_LEFT) . " status updated to '{$newStatus}'.");
            } catch (PDOException $e) {
                setFlash('error', 'Failed to update order status: ' . $e->getMessage());
            }
        }
    }

    // CANCEL ORDER
    elseif ($action === 'cancel') {
        try {
            $stmt = $pdo->prepare("UPDATE `orders` SET `order_status` = 'Cancelled' WHERE `id` = ?");
            $stmt->execute([$orderId]);
            setFlash('info', "Order #GC-" . str_pad($orderId, 5, '0', STR_PAD_LEFT) . " has been marked as Cancelled.");
        } catch (PDOException $e) {
            setFlash('error', 'Failed to cancel order: ' . $e->getMessage());
        }
    }

    // DELETE ORDER RECORD
    elseif ($action === 'delete') {
        try {
            $stmt = $pdo->prepare("DELETE FROM `orders` WHERE `id` = ?");
            $stmt->execute([$orderId]);
            setFlash('success', "Order #GC-" . str_pad($orderId, 5, '0', STR_PAD_LEFT) . " permanently removed.");
        } catch (PDOException $e) {
            setFlash('error', 'Failed to delete order: ' . $e->getMessage());
        }
    }

    header('Location: admin.php?tab=orders');
    exit;
}

// =========================================================================
// 3. Fetch Metrics and Data
// =========================================================================
$totalRevenue = 0.00;
$totalOrders = 0;
$pendingOrders = 0;
$totalMenuItems = 0;

try {
    // Total Revenue (all non-cancelled orders)
    $stmtRev = $pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM `orders` WHERE `order_status` != 'Cancelled'");
    $totalRevenue = (float)$stmtRev->fetchColumn();

    // Total orders count
    $stmtOrdCount = $pdo->query("SELECT COUNT(*) FROM `orders`");
    $totalOrders = (int)$stmtOrdCount->fetchColumn();

    // Pending orders count
    $stmtPend = $pdo->query("SELECT COUNT(*) FROM `orders` WHERE `order_status` = 'Pending'");
    $pendingOrders = (int)$stmtPend->fetchColumn();

    // Menu items count
    $stmtMenuCount = $pdo->query("SELECT COUNT(*) FROM `coffee_menu`");
    $totalMenuItems = (int)$stmtMenuCount->fetchColumn();

    // Fetch all menu items
    $stmtMenu = $pdo->query("SELECT * FROM `coffee_menu` ORDER BY `category`, `item_name` ASC");
    $allMenuItems = $stmtMenu->fetchAll();

    // Fetch all orders with user info
    $stmtOrders = $pdo->query("
        SELECT o.*, u.username, u.email 
        FROM `orders` o 
        JOIN `users` u ON o.user_id = u.id 
        ORDER BY o.`created_at` DESC
    ");
    $allOrders = $stmtOrders->fetchAll();

    // Fetch order items mapping
    if (!empty($allOrders)) {
        $orderIds = array_column($allOrders, 'id');
        $ph = implode(',', array_fill(0, count($orderIds), '?'));
        $stmtItems = $pdo->prepare("
            SELECT oi.*, cm.item_name 
            FROM `order_items` oi 
            JOIN `coffee_menu` cm ON oi.coffee_id = cm.id 
            WHERE oi.order_id IN ($ph)
        ");
        $stmtItems->execute($orderIds);
        $fetchedItems = $stmtItems->fetchAll();

        $orderItemsMap = [];
        foreach ($fetchedItems as $fi) {
            $orderItemsMap[$fi['order_id']][] = $fi;
        }

        foreach ($allOrders as &$ord) {
            $ord['items'] = $orderItemsMap[$ord['id']] ?? [];
        }
        unset($ord);
    }

} catch (PDOException $e) {
    setFlash('error', 'Data retrieval error: ' . $e->getMessage());
    $allMenuItems = [];
    $allOrders = [];
}

$activeTab = $_GET['tab'] ?? 'menu';

require_once __DIR__ . '/includes/header.php';
?>

<div class="container section-padding">
    <div style="display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; flex-wrap: wrap; gap: 15px;">
        <div>
            <span class="section-subtitle">Management Console</span>
            <h2 class="section-title">Admin Dashboard</h2>
            <p style="color: var(--color-text-muted); margin: 0;">
                Oversee cafe menu offerings, review real-time orders, and manage statuses.
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-accent" onclick="openAddMenuModal()">
                <i class="fa-solid fa-plus"></i> Add New Coffee Item
            </button>
        </div>
    </div>

    <!-- Key Metrics Summary Row -->
    <div class="admin-metrics-grid">
        <div class="metric-card">
            <div class="metric-icon"><i class="fa-solid fa-coins"></i></div>
            <div class="metric-info">
                <h4><?php echo formatPrice($totalRevenue); ?></h4>
                <p>Gross Sales</p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon"><i class="fa-solid fa-bag-shopping"></i></div>
            <div class="metric-info">
                <h4><?php echo $totalOrders; ?></h4>
                <p>Total Orders</p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon" style="background: var(--color-warning-bg); color: var(--color-warning);">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div class="metric-info">
                <h4><?php echo $pendingOrders; ?></h4>
                <p>Pending Orders</p>
            </div>
        </div>

        <div class="metric-card">
            <div class="metric-icon"><i class="fa-solid fa-mug-hot"></i></div>
            <div class="metric-info">
                <h4><?php echo $totalMenuItems; ?></h4>
                <p>Menu Offerings</p>
            </div>
        </div>
    </div>

    <!-- Tab Buttons -->
    <div class="admin-tabs">
        <button type="button" 
                class="admin-tab-btn <?php echo ($activeTab === 'menu') ? 'active' : ''; ?>" 
                onclick="switchTab('menu')">
            <i class="fa-solid fa-list-check"></i> Manage Menu (<?php echo count($allMenuItems); ?>)
        </button>
        <button type="button" 
                class="admin-tab-btn <?php echo ($activeTab === 'orders') ? 'active' : ''; ?>" 
                onclick="switchTab('orders')">
            <i class="fa-solid fa-receipt"></i> Customer Orders (<?php echo count($allOrders); ?>)
            <?php if ($pendingOrders > 0): ?>
                <span class="badge badge-out-of-stock" style="position: static; vertical-align: middle; margin-left: 6px; font-size: 0.7rem;">
                    <?php echo $pendingOrders; ?> Pending
                </span>
            <?php endif; ?>
        </button>
    </div>

    <!-- TAB 1: MANAGE COFFEE MENU -->
    <div id="tabContentMenu" style="display: <?php echo ($activeTab === 'menu') ? 'block' : 'none'; ?>;">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Coffee Name</th>
                        <th>Category</th>
                        <th>Unit Price</th>
                        <th>Status</th>
                        <th style="text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($allMenuItems)): ?>
                        <?php foreach ($allMenuItems as $item): ?>
                            <tr>
                                <td>#<?php echo $item['id']; ?></td>
                                <td>
                                    <strong><?php echo e($item['item_name']); ?></strong>
                                </td>
                                <td>
                                    <span style="background: var(--color-cream-bg); padding: 4px 10px; border-radius: 4px; font-size: 0.82rem; border: 1px solid var(--color-cream-border);">
                                        <?php echo e($item['category']); ?>
                                    </span>
                                </td>
                                <td style="font-weight: 700; font-family: var(--font-heading); color: var(--color-primary); font-size: 1.05rem;">
                                    <?php echo formatPrice($item['price']); ?>
                                </td>
                                <td>
                                    <span class="badge badge-<?php echo ($item['status'] === 'available') ? 'available' : 'out-of-stock'; ?>" style="position: static;">
                                        <?php echo ($item['status'] === 'available') ? 'Available' : 'Out of Stock'; ?>
                                    </span>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                                        <!-- Quick Status Toggle -->
                                        <form method="POST" action="admin.php" style="display: inline;">
                                            <input type="hidden" name="menu_action" value="toggle_status">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" 
                                                    class="btn btn-outline btn-sm" 
                                                    title="<?php echo ($item['status'] === 'available') ? 'Mark Out of Stock' : 'Mark Available'; ?>">
                                                <i class="fa-solid fa-power-off"></i>
                                            </button>
                                        </form>

                                        <!-- Edit Modal Button -->
                                        <button type="button" 
                                                class="btn btn-primary btn-sm" 
                                                onclick="openEditMenuModal(<?php echo htmlspecialchars(json_encode($item)); ?>)" 
                                                title="Edit Item">
                                            <i class="fa-solid fa-pen-to-square"></i> Edit
                                        </button>

                                        <!-- Delete Item Button -->
                                        <form method="POST" action="admin.php" style="display: inline;" onsubmit="return confirm('Delete \'<?php echo addslashes($item['item_name']); ?>\' from menu?');">
                                            <input type="hidden" name="menu_action" value="delete">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <button type="submit" class="btn btn-danger-outline btn-sm" title="Remove Item">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" style="text-align: center; padding: 40px; color: var(--color-text-muted);">
                                No items found in the coffee menu. Click "Add New Coffee Item" to add your first beverage.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 2: MANAGE ORDERS -->
    <div id="tabContentOrders" style="display: <?php echo ($activeTab === 'orders') ? 'block' : 'none'; ?>;">
        <div class="data-table-container">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Order Ref</th>
                        <th>Customer</th>
                        <th>Date & Time</th>
                        <th>Ordered Items</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                        <th style="text-align: right;">Order Controls</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($allOrders)): ?>
                        <?php foreach ($allOrders as $ord): ?>
                            <tr>
                                <td>
                                    <strong>#GC-<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?></strong>
                                </td>
                                <td>
                                    <div style="font-weight: 600; color: var(--color-primary);"><?php echo e($ord['username']); ?></div>
                                    <small style="color: var(--color-text-muted);"><?php echo e($ord['email']); ?></small>
                                </td>
                                <td>
                                    <div><?php echo date('M d, Y', strtotime($ord['created_at'])); ?></div>
                                    <small style="color: var(--color-text-muted);"><?php echo date('h:i A', strtotime($ord['created_at'])); ?></small>
                                </td>
                                <td>
                                    <div style="max-width: 280px;">
                                        <?php foreach ($ord['items'] as $it): ?>
                                            <div style="font-size: 0.85rem; margin-bottom: 2px;">
                                                &bull; <strong><?php echo $it['quantity']; ?>x</strong> <?php echo e($it['item_name']); ?>
                                                <span style="color: var(--color-text-muted);">(<?php echo formatPrice($it['unit_price']); ?>)</span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                                <td style="font-weight: 700; font-family: var(--font-heading); color: var(--color-primary); font-size: 1.05rem;">
                                    <?php echo formatPrice($ord['total_amount']); ?>
                                </td>
                                <td>
                                    <span style="font-size: 0.85rem;">
                                        <i class="fa-solid fa-credit-card" style="color: var(--color-accent); margin-right: 4px;"></i>
                                        <?php echo e($ord['payment_mode']); ?>
                                    </span>
                                </td>
                                <td>
                                    <!-- Status Update Form -->
                                    <form method="POST" action="admin.php" style="display: flex; align-items: center; gap: 6px;">
                                        <input type="hidden" name="order_action" value="update_status">
                                        <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                        <select name="order_status" onchange="this.form.submit()" style="padding: 5px 8px; border-radius: var(--radius-sm); border: 1px solid var(--color-cream-border); font-size: 0.82rem; font-weight: 600; background: #fff; cursor: pointer;">
                                            <option value="Pending" <?php echo ($ord['order_status'] === 'Pending') ? 'selected' : ''; ?>>⏳ Pending</option>
                                            <option value="Confirmed" <?php echo ($ord['order_status'] === 'Confirmed') ? 'selected' : ''; ?>>✓ Confirmed</option>
                                            <option value="Cancelled" <?php echo ($ord['order_status'] === 'Cancelled') ? 'selected' : ''; ?>>✕ Cancelled</option>
                                        </select>
                                    </form>
                                </td>
                                <td style="text-align: right;">
                                    <div style="display: inline-flex; align-items: center; gap: 8px;">
                                        <!-- View Receipt -->
                                        <a href="payment.php?view_receipt=<?php echo $ord['id']; ?>" class="btn btn-outline btn-sm" title="View Digital Receipt">
                                            <i class="fa-solid fa-receipt"></i>
                                        </a>

                                        <!-- Delete Order -->
                                        <form method="POST" action="admin.php" style="display: inline;" onsubmit="return confirm('Permanently delete Order #GC-<?php echo str_pad($ord['id'], 5, '0', STR_PAD_LEFT); ?>?');">
                                            <input type="hidden" name="order_action" value="delete">
                                            <input type="hidden" name="order_id" value="<?php echo $ord['id']; ?>">
                                            <button type="submit" class="btn btn-danger-outline btn-sm" title="Delete Order">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" style="text-align: center; padding: 40px; color: var(--color-text-muted);">
                                No orders recorded yet.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Add New Menu Item                                                  -->
<!-- ========================================================================= -->
<div class="modal-overlay" id="addMenuModal">
    <div class="modal-content">
        <form method="POST" action="admin.php">
            <input type="hidden" name="menu_action" value="add">
            
            <div class="modal-header">
                <h3><i class="fa-solid fa-mug-hot" style="color: var(--color-accent);"></i> Add New Coffee Item</h3>
                <button type="button" class="close-drawer-btn" onclick="closeModal('addMenuModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Item Name</label>
                    <input type="text" name="item_name" class="form-control" placeholder="e.g. Irish Velvet Roast" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Price (<?php echo CAFE_CURRENCY; ?>)</label>
                    <input type="number" step="0.50" min="1" name="price" class="form-control" placeholder="e.g. 135.00" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" class="form-control" required>
                        <option value="Hot Coffee">Hot Coffee</option>
                        <option value="Cold Coffee">Cold Coffee</option>
                        <option value="Specialty Coffee">Specialty Coffee</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Availability Status</label>
                    <select name="status" class="form-control" required>
                        <option value="available">Available</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addMenuModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Save Coffee Item</button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================================================= -->
<!-- Modal: Edit Menu Item                                                     -->
<!-- ========================================================================= -->
<div class="modal-overlay" id="editMenuModal">
    <div class="modal-content">
        <form method="POST" action="admin.php">
            <input type="hidden" name="menu_action" value="edit">
            <input type="hidden" name="item_id" id="editItemId">
            
            <div class="modal-header">
                <h3><i class="fa-solid fa-pen-to-square" style="color: var(--color-accent);"></i> Edit Coffee Item</h3>
                <button type="button" class="close-drawer-btn" onclick="closeModal('editMenuModal')">&times;</button>
            </div>

            <div class="modal-body">
                <div class="form-group">
                    <label class="form-label">Item Name</label>
                    <input type="text" name="item_name" id="editItemName" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Price (<?php echo CAFE_CURRENCY; ?>)</label>
                    <input type="number" step="0.50" min="1" name="price" id="editItemPrice" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Category</label>
                    <select name="category" id="editItemCategory" class="form-control" required>
                        <option value="Hot Coffee">Hot Coffee</option>
                        <option value="Cold Coffee">Cold Coffee</option>
                        <option value="Specialty Coffee">Specialty Coffee</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Availability Status</label>
                    <select name="status" id="editItemStatus" class="form-control" required>
                        <option value="available">Available</option>
                        <option value="out_of_stock">Out of Stock</option>
                    </select>
                </div>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editMenuModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fa-solid fa-check"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
function switchTab(tabName) {
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);

    document.querySelectorAll('.admin-tab-btn').forEach(b => b.classList.remove('active'));
    document.getElementById('tabContentMenu').style.display = 'none';
    document.getElementById('tabContentOrders').style.display = 'none';

    if (tabName === 'menu') {
        document.querySelectorAll('.admin-tab-btn')[0].classList.add('active');
        document.getElementById('tabContentMenu').style.display = 'block';
    } else {
        document.querySelectorAll('.admin-tab-btn')[1].classList.add('active');
        document.getElementById('tabContentOrders').style.display = 'block';
    }
}

function openAddMenuModal() {
    openModal('addMenuModal');
}

function openEditMenuModal(item) {
    document.getElementById('editItemId').value = item.id;
    document.getElementById('editItemName').value = item.item_name;
    document.getElementById('editItemPrice').value = item.price;
    document.getElementById('editItemCategory').value = item.category;
    document.getElementById('editItemStatus').value = item.status;
    openModal('editMenuModal');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
