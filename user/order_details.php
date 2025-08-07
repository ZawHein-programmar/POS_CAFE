<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

$order_id = $_GET['order_id'] ?? 0;

if (empty($order_id)) {
    header("Location: index.php");
    exit;
}

// Fetch order details first
$stmt = $mysqli->prepare("
    SELECT o.*, t.name as table_name 
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Handle Add Product to Order
if (isset($_POST['add_product'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    
    // Check if product already exists in order
    $stmt = $mysqli->prepare("SELECT id, quantity FROM order_items WHERE order_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $order_id, $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $existing_item = $result->fetch_assoc();
    
    if ($existing_item) {
        // Update quantity
        $new_quantity = $existing_item['quantity'] + $quantity;
        $stmt = $mysqli->prepare("UPDATE order_items SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $existing_item['id']);
        $stmt->execute();
    } else {
        // Add new item
        $stmt = $mysqli->prepare("INSERT INTO order_items (order_id, product_id, quantity, unit_price, status) VALUES (?, ?, ?, ?, 'ordered')");
        $stmt->bind_param("iiid", $order_id, $product_id, $quantity, $unit_price);
        $stmt->execute();
    }
    
    // Update order total - exclude cancelled items
    $stmt = $mysqli->prepare("
        UPDATE orders SET total_amount = (
            SELECT COALESCE(SUM(quantity * unit_price), 0) 
            FROM order_items 
            WHERE order_id = ? AND status != 'cancelled'
        ) WHERE id = ?
    ");
    $stmt->bind_param("ii", $order_id, $order_id);
    $stmt->execute();
    
    // Reset kitchen status to pending if it was ready
    if ($order['kitchen_status'] == 'ready') {
        $stmt = $mysqli->prepare("UPDATE orders SET kitchen_status = 'pending' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
    }
    
    header("Location: order_details.php?order_id=" . $order_id);
    exit;
}

// Handle Update Quantity
if (isset($_POST['update_quantity'])) {
    $item_id = $_POST['item_id'];
    $new_quantity = $_POST['new_quantity'];
    
    if ($new_quantity > 0) {
        $stmt = $mysqli->prepare("UPDATE order_items SET quantity = ? WHERE id = ? AND order_id = ?");
        $stmt->bind_param("iii", $new_quantity, $item_id, $order_id);
        $stmt->execute();
    } else {
        // Remove item if quantity is 0
        $stmt = $mysqli->prepare("DELETE FROM order_items WHERE id = ? AND order_id = ?");
        $stmt->bind_param("ii", $item_id, $order_id);
        $stmt->execute();
    }
    
    // Update order total - exclude cancelled items
    $stmt = $mysqli->prepare("
        UPDATE orders SET total_amount = (
            SELECT COALESCE(SUM(quantity * unit_price), 0) 
            FROM order_items 
            WHERE order_id = ? AND status != 'cancelled'
        ) WHERE id = ?
    ");
    $stmt->bind_param("ii", $order_id, $order_id);
    $stmt->execute();
    
    header("Location: order_details.php?order_id=" . $order_id);
    exit;
}

// Payment processing is now handled by cashier only
// Removed payment processing logic from waiter interface

// Handle Order Cancellation - Only allowed when kitchen hasn't started preparing
if (isset($_POST['cancel_order'])) {
    if ($order['kitchen_status'] === 'pending' || $order['kitchen_status'] === 'accepted') {
        // Update order status to cancelled
        $stmt = $mysqli->prepare("UPDATE orders SET status = 'cancelled', kitchen_status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        // Update table status
        $stmt = $mysqli->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
        $stmt->bind_param("i", $order['table_id']);
        $stmt->execute();

        header("Location: order_history.php");
        exit;
    } else {
        $cancel_error = "Order cannot be cancelled once kitchen has started preparing.";
    }
}

// Refresh order data after updates
$stmt = $mysqli->prepare("
    SELECT o.*, t.name as table_name 
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

// Fetch order items with product details and individual status
$stmt = $mysqli->prepare("
    SELECT oi.id, oi.quantity, oi.unit_price, oi.status as item_status, p.name as product_name, p.id as product_id
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.created_at ASC
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);

// Fetch all products for adding to order
$products_result = $mysqli->query("
    SELECT p.id, p.name, p.original_price,
        d.percent AS discount_percent,
        d.start_date, d.end_date
    FROM products p
    LEFT JOIN discounts d ON d.product_id = p.id
        AND d.start_date <= CURDATE() AND d.end_date >= CURDATE()
    WHERE p.status = 'active'
    ORDER BY p.name ASC
");
$products = [];
while ($row = $products_result->fetch_assoc()) {
    if ($row['discount_percent'] !== null) {
        $row['final_price'] = round($row['original_price'] * (1 - $row['discount_percent'] / 100), 2);
    } else {
        $row['final_price'] = $row['original_price'];
    }
    $products[] = $row;
}

// Payment types are no longer needed in waiter interface
// Payment processing is handled by cashier

include 'layout/header.php';
?>

<?php if (isset($notifications_cleared) && $notifications_cleared): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-bell"></i> Order notifications have been cleared automatically.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Order #<?= $order_id ?> Details</h4>
                </div>
                <div class="card-body">
                    <p><strong>Status:</strong> <?= ucfirst($order['status']) ?></p>
                    <p><strong>Kitchen Status:</strong> 
                        <span class="badge bg-<?= 
                            $order['kitchen_status'] == 'pending' ? 'warning' : 
                            ($order['kitchen_status'] == 'accepted' ? 'success' : 
                            ($order['kitchen_status'] == 'rejected' ? 'danger' : 
                            ($order['kitchen_status'] == 'preparing' ? 'info' : 
                            ($order['kitchen_status'] == 'ready' ? 'primary' : 'secondary'))))
                        ?>">
                            <?= ucfirst($order['kitchen_status']) ?>
                        </span>
                    </p>
                    <?php if (!empty($order['kitchen_notes'])): ?>
                        <p><strong>Kitchen Notes:</strong> <?= htmlspecialchars($order['kitchen_notes']) ?></p>
                    <?php endif; ?>
                    
                    <!-- Add Product to Order Section -->
                    <?php if ($order['status'] == 'pending'): ?>
                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="mb-0">Add Products to Order</h5>
                            </div>
                            <div class="card-body">
                                <form action="order_details.php?order_id=<?= $order_id ?>" method="post">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label for="product_id" class="form-label">Product</label>
                                                <select name="product_id" id="product_id" class="form-select" required>
                                                    <option value="">Select Product</option>
                                                    <?php foreach ($products as $product): ?>
                                                        <option value="<?= $product['id'] ?>" data-price="<?= $product['final_price'] ?>">
                                                            <?= htmlspecialchars($product['name']) ?> - $<?= number_format($product['final_price'], 2) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="quantity" class="form-label">Quantity</label>
                                                <input type="number" name="quantity" id="quantity" class="form-control" value="1" min="1" required>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="unit_price" class="form-label">Unit Price</label>
                                                <input type="number" name="unit_price" id="unit_price" class="form-control" step="0.01" readonly>
                                            </div>
                                        </div>
                                    </div>
                                    <input type="hidden" name="unit_price" id="hidden_unit_price">
                                    <button type="submit" name="add_product" class="btn btn-success">
                                        <i class="fas fa-plus"></i> Add to Order
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="table-responsive mt-3">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Status</th>
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <th>Actions</th>
                                    <?php endif; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td>
                                        <?php if ($order['status'] == 'pending' && $item['item_status'] != 'served'): ?>
                                            <form action="order_details.php?order_id=<?= $order_id ?>" method="post" style="display: inline;">
                                                <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                <input type="number" name="new_quantity" value="<?= $item['quantity'] ?>" min="0" class="form-control form-control-sm" style="width: 80px; display: inline-block;">
                                                <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-primary ms-1">
                                                    <i class="fas fa-save"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <?= $item['quantity'] ?>
                                        <?php endif; ?>
                                    </td>
                                    <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                    <td>$<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $item['item_status'] == 'ordered' ? 'warning' : 
                                            ($item['item_status'] == 'served' ? 'success' : 'danger')
                                        ?>">
                                            <?= ucfirst($item['item_status']) ?>
                                        </span>
                                    </td>
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <td>
                                            <?php if ($item['item_status'] == 'served'): ?>
                                                <span class="text-success">
                                                    <i class="fas fa-check-circle"></i> Served
                                                </span>
                                            <?php else: ?>
                                                <form action="order_details.php?order_id=<?= $order_id ?>" method="post" style="display: inline;" onsubmit="return confirm('Remove this item?')">
                                                    <input type="hidden" name="item_id" value="<?= $item['id'] ?>">
                                                    <input type="hidden" name="new_quantity" value="0">
                                                    <button type="submit" name="update_quantity" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">Grand Total:</th>
                                    <th>$<?= number_format($order['total_amount'], 2) ?></th>
                                    <th></th>
                                    <?php if ($order['status'] == 'pending'): ?>
                                        <th></th>
                                    <?php endif; ?>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Order Actions</h4>
                </div>
                <div class="card-body">
                    
                    <?php if (isset($payment_error)): ?>
                        <div class="alert alert-danger"><?= $payment_error ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($cancel_error)): ?>
                        <div class="alert alert-danger"><?= $cancel_error ?></div>
                    <?php endif; ?>
                    
                    <!-- Order Status Information -->
                    <div class="mb-3">
                        <h6>Order Status:</h6>
                        <span class="badge bg-<?= 
                            $order['status'] == 'pending' ? 'warning' : 
                            ($order['status'] == 'completed' ? 'success' : 'danger')
                        ?>">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>
                    
                    <!-- Kitchen Status Information -->
                    <div class="mb-3">
                        <h6>Kitchen Status:</h6>
                        <span class="badge bg-<?= 
                            $order['kitchen_status'] == 'pending' ? 'warning' : 
                            ($order['kitchen_status'] == 'accepted' ? 'success' : 
                            ($order['kitchen_status'] == 'rejected' ? 'danger' : 
                            ($order['kitchen_status'] == 'preparing' ? 'info' : 'primary')))
                        ?>">
                            <?= ucfirst($order['kitchen_status']) ?>
                        </span>
                    </div>
                    
                    <!-- Payment Section - Cashier handles payments -->
                    <?php if ($order['status'] == 'pending' && $order['kitchen_status'] == 'ready'): ?>
                        <div class="mb-3">
                            <h6>Payment</h6>
                            <div class="alert alert-warning">
                                <i class="fas fa-cash-register"></i> 
                                <strong>Order Ready for Payment!</strong><br>
                                This order is ready to be served. Please direct the customer to the cashier for payment processing.
                            </div>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Cashier Information:</strong><br>
                                • Order ID: <strong>#<?= $order_id ?></strong><br>
                                • Total Amount: <strong>$<?= number_format($order['total_amount'], 2) ?></strong><br>
                                • Table: <strong><?= htmlspecialchars($order['table_name'] ?? 'N/A') ?></strong>
                            </div>
                        </div>
                    <?php elseif ($order['status'] == 'pending' && $order['kitchen_status'] != 'ready'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-clock"></i> Payment will be available once the food is ready to serve.
                        </div>
                    <?php elseif ($order['status'] == 'completed'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> This order has been paid and completed by the cashier.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Cancel Order - Only when kitchen hasn't started preparing -->
                    <?php if ($order['status'] == 'pending' && ($order['kitchen_status'] == 'pending' || $order['kitchen_status'] == 'accepted')): ?>
                        <div class="mb-3">
                            <h6>Cancel Order</h6>
                            <form action="order_details.php?order_id=<?= $order_id ?>" method="post" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                <button type="submit" name="cancel_order" class="btn btn-danger w-100">
                                    <i class="fas fa-times"></i> Cancel Order
                                </button>
                            </form>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Kitchen Notes -->
                    <?php if (!empty($order['kitchen_notes'])): ?>
                        <div class="mt-3">
                            <h6>Kitchen Notes:</h6>
                            <div class="alert alert-warning">
                                <i class="fas fa-sticky-note"></i> <?= htmlspecialchars($order['kitchen_notes']) ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- Order Notifications -->
                    <?php
                    // Mark notifications as read and delete them when waiter views order details
                    $stmt = $mysqli->prepare("
                        SELECT * FROM notifications 
                        WHERE order_id = ? AND user_id = ? 
                        ORDER BY created_at DESC 
                        LIMIT 5
                    ");
                    $stmt->bind_param("ii", $order_id, $_SESSION['waiter_id']);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $order_notifications = $result->fetch_all(MYSQLI_ASSOC);
                    
                    // Mark these notifications as read and delete them
                    $notifications_cleared = false;
                    if (!empty($order_notifications)) {
                        $notification_ids = array_column($order_notifications, 'id');
                        $placeholders = str_repeat('?,', count($notification_ids) - 1) . '?';
                        $stmt = $mysqli->prepare("DELETE FROM notifications WHERE id IN ($placeholders) AND user_id = ?");
                        $params = array_merge($notification_ids, [$_SESSION['waiter_id']]);
                        $types = str_repeat('i', count($params));
                        $stmt->bind_param($types, ...$params);
                        $stmt->execute();
                        $notifications_cleared = true;
                        
                        // Debug: Log the deletion
                        error_log("Deleted " . count($notification_ids) . " notifications for order #$order_id");
                    }
                    ?>
                    
                    <?php if (!empty($order_notifications)): ?>
                        <div class="mt-3">
                            <h6><i class="fas fa-bell"></i> Recent Updates:</h6>
                            <div class="list-group">
                                <?php foreach ($order_notifications as $notification): ?>
                                    <div class="list-group-item list-group-item-action border-left border-3 border-<?= $notification['type'] ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                <span class="badge badge-<?= $notification['type'] ?> mr-2">
                                                    <i class="fas fa-<?= 
                                                        $notification['type'] == 'danger' ? 'exclamation-triangle' : 
                                                        ($notification['type'] == 'warning' ? 'exclamation-circle' : 
                                                        ($notification['type'] == 'success' ? 'check-circle' : 'info-circle'))
                                                    ?>"></i>
                                                    <?= ucfirst($notification['type']) ?>
                                                </span>
                                                Kitchen Update
                                            </h6>
                                            <small class="text-muted">
                                                <i class="fas fa-clock"></i> <?= date('M j, g:i A', strtotime($notification['created_at'])) ?>
                                            </small>
                                        </div>
                                        <p class="mb-1 text-muted"><?= htmlspecialchars($notification['message']) ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-2">
                                <a href="notifications.php" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-bell"></i> View All Notifications
                                </a>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="mt-3">
                            <h6><i class="fas fa-bell"></i> Order Updates:</h6>
                            <div class="alert alert-light text-center">
                                <i class="fas fa-check-circle text-success"></i>
                                <p class="mb-0">No pending notifications for this order.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Auto-fill unit price when product is selected
document.getElementById('product_id').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    const price = selectedOption.getAttribute('data-price');
    document.getElementById('unit_price').value = price;
    document.getElementById('hidden_unit_price').value = price;
});
</script>

<?php include 'layout/footer.php'; ?>
