<?php
require_once '../require/db.php';
session_start();

if (!isset($_SESSION['cashier_id'])) {
    header("Location: login.php");
    exit;
}

// Get statistics
$stats = [];

// Total orders today
$result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE()");
$stats['total_orders_today'] = $result->fetch_assoc()['count'];

// Paid orders today
$result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE DATE(order_date) = CURDATE() AND status = 'completed'");
$stats['paid_orders_today'] = $result->fetch_assoc()['count'];

// Pending payments
$result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE status = 'pending' AND kitchen_status = 'ready'");
$stats['pending_payments'] = $result->fetch_assoc()['count'];

// Total revenue today
$result = $mysqli->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE DATE(order_date) = CURDATE() AND status = 'completed'");
$stats['revenue_today'] = $result->fetch_assoc()['total'];

// Recent orders
$recent_orders = $mysqli->query("
    SELECT o.id, o.order_date, o.status, o.kitchen_status, o.total_amount, t.name as table_name, u.name as waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
    WHERE DATE(o.order_date) = CURDATE()
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-cash-register"></i> Cashier Dashboard</h2>
                <div class="text-end">
                    <small class="text-muted">Welcome, <?= htmlspecialchars($_SESSION['cashier_name']) ?></small>
                </div>
            </div>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['total_orders_today'] ?></h4>
                            <p class="mb-0">Total Orders Today</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-list fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['paid_orders_today'] ?></h4>
                            <p class="mb-0">Paid Orders Today</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $stats['pending_payments'] ?></h4>
                            <p class="mb-0">Pending Payments</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0">$<?= number_format($stats['revenue_today'], 2) ?></h4>
                            <p class="mb-0">Revenue Today</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-bolt"></i> Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <a href="pending_payments.php" class="btn btn-warning btn-lg btn-block w-100">
                                <i class="fas fa-clock"></i><br>
                                Pending Payments<br>
                                <small>(<?= $stats['pending_payments'] ?>)</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="all_orders.php" class="btn btn-primary btn-lg btn-block w-100">
                                <i class="fas fa-list"></i><br>
                                All Orders<br>
                                <small>View All</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="payment_history.php" class="btn btn-success btn-lg btn-block w-100">
                                <i class="fas fa-history"></i><br>
                                Payment History<br>
                                <small>Today's Payments</small>
                            </a>
                        </div>
                        <div class="col-md-3 mb-3">
                            <a href="reports.php" class="btn btn-info btn-lg btn-block w-100">
                                <i class="fas fa-chart-bar"></i><br>
                                Reports<br>
                                <small>Analytics</small>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Notifications -->
    <?php
    // Fetch recent notifications for the cashier
    $notifications = $mysqli->query("
        SELECT n.*, o.total_amount, t.name as table_name
        FROM notifications n
        JOIN orders o ON n.order_id = o.id
        JOIN tables t ON o.table_id = t.id
        WHERE n.user_id = {$_SESSION['cashier_id']}
        ORDER BY n.created_at DESC
        LIMIT 5
    ")->fetch_all(MYSQLI_ASSOC);
    ?>
    
    <?php if (!empty($notifications)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-bell"></i> Recent Notifications</h5>
                        <a href="clear_notifications.php" class="btn btn-sm btn-outline-secondary" onclick="return confirm('Clear all notifications?')">
                            <i class="fas fa-trash"></i> Clear All
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action border-left border-3 border-<?= $notification['type'] ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <span class="badge badge-<?= $notification['type'] ?> me-2">
                                                <i class="fas fa-<?= 
                                                    $notification['type'] == 'danger' ? 'exclamation-triangle' : 
                                                    ($notification['type'] == 'warning' ? 'exclamation-circle' : 
                                                    ($notification['type'] == 'success' ? 'check-circle' : 'info-circle'))
                                                ?>"></i>
                                                <?= ucfirst($notification['type']) ?>
                                            </span>
                                            Order #<?= $notification['order_id'] ?>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="fas fa-clock"></i> <?= date('M j, g:i A', strtotime($notification['created_at'])) ?>
                                        </small>
                                    </div>
                                    <p class="mb-1"><?= htmlspecialchars($notification['message']) ?></p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small class="text-muted">
                                            Table: <?= htmlspecialchars($notification['table_name']) ?> | 
                                            Amount: $<?= number_format($notification['total_amount'], 2) ?>
                                        </small>
                                        <div>
                                            <a href="order_details.php?order_id=<?= $notification['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View Order
                                            </a>
                                            <?php if (strpos($notification['message'], 'ready for payment') !== false): ?>
                                                <a href="process_payment.php?order_id=<?= $notification['order_id'] ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-credit-card"></i> Process Payment
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Recent Orders -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-clock"></i> Recent Orders Today</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($recent_orders)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No Orders Today</h5>
                            <p class="text-muted">No orders have been placed today.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Order ID</th>
                                        <th>Table</th>
                                        <th>Waiter</th>
                                        <th>Time</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?= $order['id'] ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($order['table_name']) ?></td>
                                        <td><?= htmlspecialchars($order['waiter_name']) ?></td>
                                        <td><?= date('H:i', strtotime($order['order_date'])) ?></td>
                                        <td><strong>$<?= number_format($order['total_amount'], 2) ?></strong></td>
                                        <td>
                                            <span class="badge badge-<?= 
                                                $order['status'] == 'pending' ? 'warning' : 
                                                ($order['status'] == 'completed' ? 'success' : 'danger')
                                            ?>">
                                                <?= ucfirst($order['status']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if ($order['status'] == 'pending' && $order['kitchen_status'] == 'ready'): ?>
                                                <span class="badge badge-success">Ready for Payment</span>
                                            <?php elseif ($order['status'] == 'completed'): ?>
                                                <span class="badge badge-success">Paid</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Not Ready</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <?php if ($order['status'] == 'pending' && $order['kitchen_status'] == 'ready'): ?>
                                                <a href="process_payment.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-success">
                                                    <i class="fas fa-credit-card"></i> Pay
                                                </a>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?> 