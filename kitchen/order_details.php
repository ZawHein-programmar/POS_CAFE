<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

if (!isset($_SESSION['kitchen_id'])) {
    header("Location: login.php");
    exit;
}

$order_id = $_GET['order_id'] ?? 0;

if (empty($order_id)) {
    header("Location: index.php");
    exit;
}

// Fetch order details first
$stmt = $mysqli->prepare("
    SELECT o.*, t.name as table_name, u.name as waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
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

// Handle status updates
if (isset($_POST['update_status'])) {
    // Check if order is already completed (paid)
    if ($order['status'] === 'completed') {
        $message = "Cannot update status - order has already been paid and completed.";
    } else {
        $new_status = $_POST['kitchen_status'];
        $notes = $_POST['kitchen_notes'] ?? '';
        
        // Additional validation for status transitions
        $current_status = $order['kitchen_status'];
        $valid_transition = true;
        
        // Prevent invalid status transitions
        if ($current_status === 'rejected' && $new_status !== 'rejected') {
            $valid_transition = false;
            $message = "Cannot change status from rejected.";
        } elseif ($current_status === 'ready' && $new_status !== 'ready') {
            $valid_transition = false;
            $message = "Cannot change status from ready.";
        }
        
        if ($valid_transition) {
            $stmt = $mysqli->prepare("UPDATE orders SET kitchen_status = ?, kitchen_notes = ? WHERE id = ?");
            $stmt->bind_param("ssi", $new_status, $notes, $order_id);
            $stmt->execute();
            
            $message = "Order status updated successfully.";
            
            // Refresh order data after update
            $stmt = $mysqli->prepare("
                SELECT o.*, t.name as table_name, u.name as waiter_name
                FROM orders o
                JOIN tables t ON o.table_id = t.id
                JOIN user u ON o.user_id = u.id
                WHERE o.id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $order = $result->fetch_assoc();
        }
    }
}



// Handle individual product actions
if (isset($_POST['product_action']) && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    $action = $_POST['product_action'];
    $new_quantity = $_POST['new_quantity'] ?? 0;
    $item_status = $_POST['item_status'] ?? 'ordered';
    
    // Validate that this is a real form submission
    if (!empty($product_id) && !empty($action)) {
        // Get product name for notification
        $stmt = $mysqli->prepare("SELECT name FROM products WHERE id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $product = $result->fetch_assoc();
        $product_name = $product['name'];
        
        if ($action === 'accept') {
            // Accept the product with specified quantity
            if ($new_quantity > 0) {
                $stmt = $mysqli->prepare("UPDATE order_items SET quantity = ?, status = 'ordered' WHERE order_id = ? AND product_id = ?");
                $stmt->bind_param("iii", $new_quantity, $order_id, $product_id);
                $stmt->execute();
                
                // Send notification to waiter (check for duplicates first)
                $notification_message = "Product '$product_name' quantity updated to $new_quantity in Order #$order_id";
                
                // Check if similar notification already exists in the last 5 minutes
                $stmt = $mysqli->prepare("
                    SELECT COUNT(*) as count FROM notifications 
                    WHERE order_id = ? AND user_id = ? AND message = ? 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ");
                $stmt->bind_param("iis", $order_id, $order['user_id'], $notification_message);
                $stmt->execute();
                $result = $stmt->get_result();
                $existing_count = $result->fetch_assoc()['count'];
                
                if ($existing_count == 0) {
                    $stmt = $mysqli->prepare("INSERT INTO notifications (order_id, user_id, message, type) VALUES (?, ?, ?, 'info')");
                    $stmt->bind_param("iis", $order_id, $order['user_id'], $notification_message);
                    $stmt->execute();
                }
                
                $message = "Product accepted successfully. Waiter notified.";
            } else {
                // Remove product if quantity is 0
                $stmt = $mysqli->prepare("DELETE FROM order_items WHERE order_id = ? AND product_id = ?");
                $stmt->bind_param("ii", $order_id, $product_id);
                $stmt->execute();
                
                // Send notification to waiter (check for duplicates first)
                $notification_message = "Product '$product_name' removed from Order #$order_id";
                
                // Check if similar notification already exists in the last 5 minutes
                $stmt = $mysqli->prepare("
                    SELECT COUNT(*) as count FROM notifications 
                    WHERE order_id = ? AND user_id = ? AND message = ? 
                    AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                ");
                $stmt->bind_param("iis", $order_id, $order['user_id'], $notification_message);
                $stmt->execute();
                $result = $stmt->get_result();
                $existing_count = $result->fetch_assoc()['count'];
                
                if ($existing_count == 0) {
                    $stmt = $mysqli->prepare("INSERT INTO notifications (order_id, user_id, message, type) VALUES (?, ?, ?, 'warning')");
                    $stmt->bind_param("iis", $order_id, $order['user_id'], $notification_message);
                    $stmt->execute();
                }
                
                $message = "Product removed from order. Waiter notified.";
            }
        } elseif ($action === 'reject') {
            // Remove product from order
            $stmt = $mysqli->prepare("DELETE FROM order_items WHERE order_id = ? AND product_id = ?");
            $stmt->bind_param("ii", $order_id, $product_id);
            $stmt->execute();
            
            // Send notification to waiter (check for duplicates first)
            $notification_message = "Product '$product_name' rejected and removed from Order #$order_id";
            
            // Check if similar notification already exists in the last 5 minutes
            $stmt = $mysqli->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE order_id = ? AND user_id = ? AND message = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $stmt->bind_param("iis", $order_id, $order['user_id'], $notification_message);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing_count = $result->fetch_assoc()['count'];
            
            if ($existing_count == 0) {
                $stmt = $mysqli->prepare("INSERT INTO notifications (order_id, user_id, message, type) VALUES (?, ?, ?, 'danger')");
                $stmt->bind_param("iis", $order_id, $order['user_id'], $notification_message);
                $stmt->execute();
            }
            
            $message = "Product rejected and removed from order. Waiter notified.";
        } elseif ($action === 'update_status') {
            // Update individual product status
            $stmt = $mysqli->prepare("UPDATE order_items SET status = ? WHERE order_id = ? AND product_id = ?");
            $stmt->bind_param("sii", $item_status, $order_id, $product_id);
            $stmt->execute();
            
            // Send notification to waiter
            $status_text = ucfirst($item_status);
            $notification_message = "Product '$product_name' status updated to '$status_text' in Order #$order_id";
            
            // Determine notification type based on status
            $notification_type = 'info';
            if ($item_status === 'ready') {
                $notification_type = 'success';
            } elseif ($item_status === 'served') {
                $notification_type = 'success';
            }
            
            // Check if similar notification already exists in the last 5 minutes
            $stmt = $mysqli->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE order_id = ? AND user_id = ? AND message = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $stmt->bind_param("iis", $order_id, $order['user_id'], $notification_message);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing_count = $result->fetch_assoc()['count'];
            
            if ($existing_count == 0) {
                $stmt = $mysqli->prepare("INSERT INTO notifications (order_id, user_id, message, type) VALUES (?, ?, ?, ?)");
                $stmt->bind_param("iiss", $order_id, $order['user_id'], $notification_message, $notification_type);
                $stmt->execute();
            }
            
            $message = "Product status updated successfully. Waiter notified.";
            
            // Update overall kitchen status based on individual product statuses
            $stmt = $mysqli->prepare("
                SELECT 
                    COUNT(*) as total_items,
                    SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as ready_items,
                    SUM(CASE WHEN status = 'served' THEN 1 ELSE 0 END) as served_items,
                    SUM(CASE WHEN status = 'preparing' THEN 1 ELSE 0 END) as preparing_items
                FROM order_items 
                WHERE order_id = ?
            ");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $status_summary = $result->fetch_assoc();
            
            // Determine overall kitchen status
            $new_kitchen_status = 'pending';
            if ($status_summary['served_items'] == $status_summary['total_items']) {
                $new_kitchen_status = 'ready';
            } elseif ($status_summary['ready_items'] > 0 || $status_summary['preparing_items'] > 0) {
                $new_kitchen_status = 'preparing';
            }
            
            // Update kitchen status if it changed
            if ($new_kitchen_status !== $order['kitchen_status']) {
                $stmt = $mysqli->prepare("UPDATE orders SET kitchen_status = ? WHERE id = ?");
                $stmt->bind_param("si", $new_kitchen_status, $order_id);
                $stmt->execute();
                
                // Send notification to cashier when order is ready for payment
                if ($new_kitchen_status === 'ready') {
                    // Get all cashier users
                    $cashiers = $mysqli->query("SELECT id FROM user WHERE role = 'cashier' AND status = 'active'")->fetch_all(MYSQLI_ASSOC);
                    
                    foreach ($cashiers as $cashier) {
                        $notification_message = "Order #$order_id is ready for payment. Table: " . $order['table_name'] . ", Amount: $" . number_format($order['total_amount'], 2);
                        
                        // Check if similar notification already exists in the last 5 minutes
                        $stmt = $mysqli->prepare("
                            SELECT COUNT(*) as count FROM notifications 
                            WHERE order_id = ? AND user_id = ? AND message LIKE ? 
                            AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
                        ");
                        $search_pattern = "Order #$order_id is ready for payment%";
                        $stmt->bind_param("iis", $order_id, $cashier['id'], $search_pattern);
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $existing_count = $result->fetch_assoc()['count'];
                        
                        if ($existing_count == 0) {
                            $stmt = $mysqli->prepare("INSERT INTO notifications (order_id, user_id, message, type) VALUES (?, ?, ?, 'success')");
                            $stmt->bind_param("iis", $order_id, $cashier['id'], $notification_message);
                            $stmt->execute();
                        }
                    }
                }
            }
        }
        
        // Recalculate order total
        $stmt = $mysqli->prepare("SELECT SUM(quantity * unit_price) as new_total FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $new_total = $result->fetch_assoc()['new_total'] ?? 0;
        
        // Update order total
        $stmt = $mysqli->prepare("UPDATE orders SET total_amount = ? WHERE id = ?");
        $stmt->bind_param("di", $new_total, $order_id);
        $stmt->execute();
        
        // Check if order has no items left
        $stmt = $mysqli->prepare("SELECT COUNT(*) as item_count FROM order_items WHERE order_id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $item_count = $result->fetch_assoc()['item_count'];
        
        if ($item_count == 0) {
            // If no items left, cancel the order
            $stmt = $mysqli->prepare("UPDATE orders SET status = 'cancelled', kitchen_status = 'cancelled' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            
            // Free the table
            $stmt = $mysqli->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
            $stmt->bind_param("i", $order['table_id']);
            $stmt->execute();
            
            // Send notification to waiter about order cancellation (check for duplicates first)
            $notification_message = "Order #$order_id has been cancelled. All products were rejected by kitchen.";
            
            // Check if similar notification already exists in the last 5 minutes
            $stmt = $mysqli->prepare("
                SELECT COUNT(*) as count FROM notifications 
                WHERE order_id = ? AND user_id = ? AND message = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
            ");
            $stmt->bind_param("iis", $order_id, $order['user_id'], $notification_message);
            $stmt->execute();
            $result = $stmt->get_result();
            $existing_count = $result->fetch_assoc()['count'];
            
            if ($existing_count == 0) {
                $stmt = $mysqli->prepare("INSERT INTO notifications (order_id, user_id, message, type) VALUES (?, ?, ?, 'danger')");
                $stmt->bind_param("iis", $order_id, $order['user_id'], $notification_message);
                $stmt->execute();
            }
            
            $message = "All products rejected. Order cancelled and table freed. Waiter notified.";
        }
        
        // Refresh order data
        $stmt = $mysqli->prepare("
            SELECT o.*, t.name as table_name, u.name as waiter_name
            FROM orders o
            JOIN tables t ON o.table_id = t.id
            JOIN user u ON o.user_id = u.id
            WHERE o.id = ?
        ");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $order = $result->fetch_assoc();
    }
}

// Fetch order items
$stmt = $mysqli->prepare("
    SELECT oi.id as order_item_id, oi.product_id, oi.quantity, oi.unit_price, oi.status as item_status, p.name as product_name, p.images
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.id ASC
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Order #<?= $order_id ?> Details</h2>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
            
            <?php if (isset($message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $message ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Order ID:</strong></td>
                            <td>#<?= $order_id ?></td>
                        </tr>
                        <tr>
                            <td><strong>Table:</strong></td>
                            <td><?= htmlspecialchars($order['table_name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Waiter:</strong></td>
                            <td><?= htmlspecialchars($order['waiter_name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Order Date:</strong></td>
                            <td><?= $order['order_date'] ?></td>
                        </tr>
                        <tr>
                            <td><strong>Order Time:</strong></td>
                            <td><?= date('H:i:s', strtotime($order['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Amount:</strong></td>
                            <td>$<?= number_format($order['total_amount'], 2) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Kitchen Status:</strong></td>
                            <td>
                                <span class="badge badge-<?= 
                                    $order['kitchen_status'] == 'pending' ? 'warning' : 
                                    ($order['kitchen_status'] == 'accepted' ? 'success' : 
                                    ($order['kitchen_status'] == 'rejected' ? 'danger' : 
                                    ($order['kitchen_status'] == 'preparing' ? 'info' : 
                                    ($order['kitchen_status'] == 'ready' ? 'primary' : 'secondary'))))
                                ?>">
                                    <?= ucfirst($order['kitchen_status']) ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Status Update -->
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Update Status</h5>
                </div>
                <div class="card-body">
                    <?php if ($order['status'] === 'completed'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i> This order has been paid and completed. Status cannot be changed.
                        </div>
                    <?php else: ?>
                        <form method="post">
                            <div class="form-group">
                                <label for="kitchen_status">Kitchen Status:</label>
                                <select name="kitchen_status" id="kitchen_status" class="form-control" required>
                                    <option value="pending" <?= $order['kitchen_status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                                    <option value="accepted" <?= $order['kitchen_status'] == 'accepted' ? 'selected' : '' ?>>Accepted</option>
                                    <option value="rejected" <?= $order['kitchen_status'] == 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                    <option value="preparing" <?= $order['kitchen_status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                    <option value="ready" <?= $order['kitchen_status'] == 'ready' ? 'selected' : '' ?>>Ready</option>
                                    <option value="cancelled" <?= $order['kitchen_status'] == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="kitchen_notes">Notes:</label>
                                <textarea name="kitchen_notes" id="kitchen_notes" class="form-control" rows="3" placeholder="Add notes about this order..."><?= htmlspecialchars($order['kitchen_notes'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" name="update_status" class="btn btn-primary btn-block">Update Status</button>
                        </form>
                        
                        <!-- Status Flow Information -->
                        <div class="mt-3">
                            <small class="text-muted">
                                <strong>Status Flow:</strong><br>
                                Pending → Accepted → Preparing → Ready<br>
                                <em>Orders can be rejected at any time before "Ready" status.</em>
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Order Items -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Order Items</h5>
                </div>
                <div class="card-body">
                    <?php if ($order['status'] !== 'completed' && $order['kitchen_status'] === 'pending'): ?>
                        <div class="alert alert-info mb-3">
                            <i class="fas fa-info-circle"></i> 
                            <strong>Product Management:</strong> You can only <strong>reduce</strong> quantities or reject products. 
                            Set quantity to 0 to remove a product completely. Order total will be recalculated automatically.
                            <br><small class="text-muted">Note: You cannot increase quantities beyond what was originally ordered.</small>
                        </div>
                    <?php endif; ?>
                    <?php if (empty($order_items)): ?>
                        <p class="text-muted">No items found for this order.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead-dark">
                                    <tr>
                                        <th><i class="fas fa-utensils"></i> Item</th>
                                        <th><i class="fas fa-sort-numeric-up"></i> Quantity</th>
                                        <th><i class="fas fa-dollar-sign"></i> Unit Price</th>
                                        <th><i class="fas fa-calculator"></i> Total</th>
                                        <th><i class="fas fa-info-circle"></i> Status</th>
                                        <th><i class="fas fa-cogs"></i> Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <?php if (!empty($item['images'])): ?>
                                                    <img src="../img/<?= htmlspecialchars($item['images']) ?>" 
                                                         alt="<?= htmlspecialchars($item['product_name']) ?>" 
                                                         class="mr-3" 
                                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                                <?php endif; ?>
                                                <div>
                                                    <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($order['status'] !== 'completed' && $order['kitchen_status'] === 'pending'): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                    <div class="input-group input-group-sm" style="width: 140px;">
                                                        <input type="number" name="new_quantity" value="<?= $item['quantity'] ?>" min="0" max="<?= $item['quantity'] ?>" class="form-control" placeholder="0-<?= $item['quantity'] ?>" title="Reduce quantity (max: <?= $item['quantity'] ?>)">
                                                        <div class="input-group-append">
                                                            <button type="submit" name="product_action" value="accept" class="btn btn-success" title="Accept with new quantity">
                                                                <i class="fas fa-check"></i>
                                                            </button>
                                                        </div>
                                                    </div>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge badge-secondary"><?= $item['quantity'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                        <td>$<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                                        <td>
                                            <?php if ($order['status'] !== 'completed'): ?>
                                                <form method="post" class="d-inline">
                                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                    <select name="item_status" class="form-control form-control-sm" style="width: 120px; display: inline-block;" onchange="this.form.submit()">
                                                        <option value="ordered" <?= $item['item_status'] == 'ordered' ? 'selected' : '' ?>>Ordered</option>
                                                        <option value="preparing" <?= $item['item_status'] == 'preparing' ? 'selected' : '' ?>>Preparing</option>
                                                        <option value="ready" <?= $item['item_status'] == 'ready' ? 'selected' : '' ?>>Ready</option>
                                                        <option value="served" <?= $item['item_status'] == 'served' ? 'selected' : '' ?>>Served</option>
                                                    </select>
                                                    <input type="hidden" name="product_action" value="update_status">
                                                </form>
                                            <?php else: ?>
                                                <span class="badge badge-<?= 
                                                    $item['item_status'] == 'ordered' ? 'warning' : 
                                                    ($item['item_status'] == 'preparing' ? 'info' : 
                                                    ($item['item_status'] == 'ready' ? 'primary' : 
                                                    ($item['item_status'] == 'served' ? 'success' : 'danger')))
                                                ?>">
                                                    <?= ucfirst($item['item_status']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($order['status'] !== 'completed' && $order['kitchen_status'] === 'pending'): ?>
                                                <form method="post" class="d-inline" onsubmit="return confirm('Are you sure you want to reject \'<?= htmlspecialchars($item['product_name']) ?>\'? This will remove it from the order.')">
                                                    <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                                                    <button type="submit" name="product_action" value="reject" class="btn btn-sm btn-outline-danger" title="Reject and remove this product">
                                                        <i class="fas fa-times"></i> Reject
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="text-muted"><i class="fas fa-lock"></i> Locked</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-right">Grand Total:</th>
                                        <th colspan="2">$<?= number_format($order['total_amount'], 2) ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Kitchen Notes -->
            <?php if (!empty($order['kitchen_notes'])): ?>
            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Kitchen Notes</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0"><?= nl2br(htmlspecialchars($order['kitchen_notes'])) ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?> 