<?php
require_once '../require/db.php';
session_start();

if (!isset($_SESSION['waiter_id'])) {
    header("Location: login.php");
    exit;
}

$order_id = $_GET['order_id'] ?? 0;

if (empty($order_id)) {
    header("Location: index.php");
    exit;
}

// Fetch order details first
$stmt = $mysqli->prepare("SELECT * FROM orders WHERE id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Handle Payment - Only allowed when food is ready
if (isset($_POST['process_payment'])) {
    // Check if kitchen status is 'ready' before allowing payment
    if ($order['kitchen_status'] !== 'ready') {
        $payment_error = "Payment can only be processed when the food is ready to serve.";
    } else {
        $payment_type_id = $_POST['payment_type_id'];
        $payment_date = date('Y-m-d');
        $transaction_code = 'TRN-' . time(); // Example transaction code

        // Insert into payment table
        $stmt = $mysqli->prepare("INSERT INTO payment (order_id, payment_type_id, payment_date, transaction_code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $order_id, $payment_type_id, $payment_date, $transaction_code);
        $stmt->execute();

        // Update order status
        $stmt = $mysqli->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        // Update table status
        $order_result = $mysqli->query("SELECT table_id FROM orders WHERE id = $order_id");
        $order = $order_result->fetch_assoc();
        $table_id = $order['table_id'];
        $stmt = $mysqli->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
        $stmt->bind_param("i", $table_id);
        $stmt->execute();

        header("Location: order_history.php");
        exit;
    }
}

// Handle Order Cancellation - Only allowed when kitchen hasn't started preparing
if (isset($_POST['cancel_order'])) {
    if ($order['kitchen_status'] === 'pending' || $order['kitchen_status'] === 'accepted') {
        // Update order status to cancelled
        $stmt = $mysqli->prepare("UPDATE orders SET status = 'cancelled', kitchen_status = 'cancelled' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        // Update table status
        $stmt = $mysqli->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
        $stmt->bind_param("i", $order_id);
        $stmt->execute();

        header("Location: order_history.php");
        exit;
    } else {
        $cancel_error = "Order cannot be cancelled once kitchen has started preparing.";
    }
}




// Fetch order items
$stmt = $mysqli->prepare("
    SELECT oi.quantity, oi.unit_price, p.name as product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);

// Fetch payment types
$payment_types_result = $mysqli->query("SELECT * FROM payment_type ORDER BY name ASC");
$payment_types = $payment_types_result->fetch_all(MYSQLI_ASSOC);


include 'layout/header.php';
?>

<div class="container-fluid">
    <?php if (isset($notifications_cleared) && $notifications_cleared): ?>
        <div class="alert alert-info alert-dismissible fade show" role="alert">
            <i class="fas fa-bell"></i> Order notifications have been cleared automatically.
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Order #<?= $order_id ?> Details</h4>
                    <p><strong>Status:</strong> <?= ucfirst($order['status']) ?></p>
                    <p><strong>Kitchen Status:</strong> 
                        <span class="badge badge-<?= 
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
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                <tr>
                                    <td><?= htmlspecialchars($item['product_name']) ?></td>
                                    <td><?= $item['quantity'] ?></td>
                                    <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                    <td>$<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-right">Grand Total:</th>
                                    <th>$<?= number_format($order['total_amount'], 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Order Actions</h4>
                    
                    <?php if (isset($payment_error)): ?>
                        <div class="alert alert-danger"><?= $payment_error ?></div>
                    <?php endif; ?>
                    
                    <?php if (isset($cancel_error)): ?>
                        <div class="alert alert-danger"><?= $cancel_error ?></div>
                    <?php endif; ?>
                    
                    <!-- Order Status Information -->
                    <div class="mb-3">
                        <h6>Order Status:</h6>
                        <span class="badge badge-<?= 
                            $order['status'] == 'pending' ? 'warning' : 
                            ($order['status'] == 'completed' ? 'success' : 'danger')
                        ?>">
                            <?= ucfirst($order['status']) ?>
                        </span>
                    </div>
                    
                    <!-- Kitchen Status Information -->
                    <div class="mb-3">
                        <h6>Kitchen Status:</h6>
                        <span class="badge badge-<?= 
                            $order['kitchen_status'] == 'pending' ? 'warning' : 
                            ($order['kitchen_status'] == 'accepted' ? 'success' : 
                            ($order['kitchen_status'] == 'rejected' ? 'danger' : 
                            ($order['kitchen_status'] == 'preparing' ? 'info' : 'primary')))
                        ?>">
                            <?= ucfirst($order['kitchen_status']) ?>
                        </span>
                    </div>
                    
                    <!-- Payment Section - Only show when food is ready -->
                    <?php if ($order['status'] == 'pending' && $order['kitchen_status'] == 'ready'): ?>
                        <div class="mb-3">
                            <h6>Payment</h6>
                            <form action="order_details.php?order_id=<?= $order_id ?>" method="post">
                                <div class="form-group">
                                    <label for="payment_type_id">Payment Method</label>
                                    <select name="payment_type_id" id="payment_type_id" class="form-control" required>
                                        <option value="">Select Payment Method</option>
                                        <?php foreach ($payment_types as $type): ?>
                                            <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <button type="submit" name="process_payment" class="btn btn-success btn-block">
                                    <i class="fas fa-credit-card"></i> Process Payment
                                </button>
                            </form>
                        </div>
                    <?php elseif ($order['status'] == 'pending' && $order['kitchen_status'] != 'ready'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-clock"></i> Payment will be available once the food is ready to serve.
                        </div>
                    <?php elseif ($order['status'] == 'completed'): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i> This order has been paid and completed.
                        </div>
                    <?php endif; ?>
                    
                    <!-- Cancel Order - Only when kitchen hasn't started preparing -->
                    <?php if ($order['status'] == 'pending' && ($order['kitchen_status'] == 'pending' || $order['kitchen_status'] == 'accepted')): ?>
                        <div class="mb-3">
                            <h6>Cancel Order</h6>
                            <form action="order_details.php?order_id=<?= $order_id ?>" method="post" onsubmit="return confirm('Are you sure you want to cancel this order?')">
                                <button type="submit" name="cancel_order" class="btn btn-danger btn-block">
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

<?php include 'layout/footer.php'; ?>
