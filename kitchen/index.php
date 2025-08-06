<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

if (!isset($_SESSION['kitchen_id'])) {
    header("Location: login.php");
    exit;
}

// Get order statistics
$stats = [];

// Pending orders
$result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE kitchen_status = 'pending'");
$stats['pending'] = $result->fetch_assoc()['count'];

// Accepted orders
$result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE kitchen_status = 'accepted'");
$stats['accepted'] = $result->fetch_assoc()['count'];

// Preparing orders
$result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE kitchen_status = 'preparing'");
$stats['preparing'] = $result->fetch_assoc()['count'];

// Ready orders
$result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE kitchen_status = 'ready'");
$stats['ready'] = $result->fetch_assoc()['count'];

// Recent orders
$recent_orders = $mysqli->query("
    SELECT o.id, o.order_date, o.kitchen_status, o.total_amount, t.name as table_name, u.name as waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
    WHERE o.kitchen_status IN ('pending', 'accepted', 'preparing')
    ORDER BY o.created_at DESC
    LIMIT 10
")->fetch_all(MYSQLI_ASSOC);

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <h2 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION['kitchen_name']) ?>!</h2>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= $stats['pending'] ?></h4>
                            <p class="mb-0">Pending Orders</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clock fa-2x"></i>
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
                            <h4><?= $stats['accepted'] ?></h4>
                            <p class="mb-0">Accepted Orders</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check fa-2x"></i>
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
                            <h4><?= $stats['preparing'] ?></h4>
                            <p class="mb-0">Preparing</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-fire fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4><?= $stats['ready'] ?></h4>
                            <p class="mb-0">Ready to Serve</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-bell fa-2x"></i>
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
                <div class="card-body">
                    <h5 class="card-title">Quick Actions</h5>
                    <div class="row">
                        <div class="col-md-3">
                            <a href="pending_orders.php" class="btn btn-warning btn-lg btn-block">
                                <i class="fas fa-clock"></i><br>
                                View Pending Orders
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="accepted_orders.php" class="btn btn-success btn-lg btn-block">
                                <i class="fas fa-check"></i><br>
                                View Accepted Orders
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="preparing_orders.php" class="btn btn-info btn-lg btn-block">
                                <i class="fas fa-fire"></i><br>
                                View Preparing Orders
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="all_orders.php" class="btn btn-secondary btn-lg btn-block">
                                <i class="fas fa-list"></i><br>
                                View All Orders
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Recent Orders</h5>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Table</th>
                                    <th>Waiter</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_orders as $order): ?>
                                <tr>
                                    <td>#<?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['table_name']) ?></td>
                                    <td><?= htmlspecialchars($order['waiter_name']) ?></td>
                                    <td><?= $order['order_date'] ?></td>
                                    <td>$<?= number_format($order['total_amount'], 2) ?></td>
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
                                    <td>
                                        <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?> 