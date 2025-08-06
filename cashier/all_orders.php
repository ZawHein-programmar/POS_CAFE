<?php
require_once '../require/db.php';
session_start();

if (!isset($_SESSION['cashier_id'])) {
    header("Location: login.php");
    exit;
}

// Filter options
$status_filter = $_GET['status'] ?? '';
$date_filter = $_GET['date'] ?? date('Y-m-d');

// Build query
$where_conditions = ["DATE(o.order_date) = '$date_filter'"];
if ($status_filter) {
    $where_conditions[] = "o.status = '$status_filter'";
}
$where_clause = implode(' AND ', $where_conditions);

// Fetch all orders
$all_orders = $mysqli->query("
    SELECT o.id, o.order_date, o.status, o.kitchen_status, o.total_amount, o.created_at,
           t.name as table_name, u.name as waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
    WHERE $where_clause
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Calculate statistics
$total_orders = count($all_orders);
$total_revenue = array_sum(array_column($all_orders, 'total_amount'));
$pending_orders = array_filter($all_orders, function($order) { return $order['status'] == 'pending'; });
$completed_orders = array_filter($all_orders, function($order) { return $order['status'] == 'completed'; });

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-list"></i> All Orders</h2>
                <div class="d-flex align-items-center gap-3">
                    <form class="d-flex gap-2">
                        <input type="date" name="date" value="<?= $date_filter ?>" class="form-control" onchange="this.form.submit()">
                        <select name="status" class="form-control" onchange="this.form.submit()">
                            <option value="">All Status</option>
                            <option value="pending" <?= $status_filter == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="completed" <?= $status_filter == 'completed' ? 'selected' : '' ?>>Completed</option>
                            <option value="cancelled" <?= $status_filter == 'cancelled' ? 'selected' : '' ?>>Cancelled</option>
                        </select>
                        <a href="?date=<?= date('Y-m-d') ?>" class="btn btn-outline-primary">Today</a>
                    </form>
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
                            <h4 class="mb-0"><?= $total_orders ?></h4>
                            <p class="mb-0">Total Orders</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-clipboard-list fa-2x"></i>
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
                            <h4 class="mb-0"><?= count($pending_orders) ?></h4>
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
                            <h4 class="mb-0"><?= count($completed_orders) ?></h4>
                            <p class="mb-0">Completed Orders</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
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
                            <h4 class="mb-0">$<?= number_format($total_revenue, 2) ?></h4>
                            <p class="mb-0">Total Revenue</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-dollar-sign fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Orders Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-table"></i> Orders - <?= date('F j, Y', strtotime($date_filter)) ?>
                        <?php if ($status_filter): ?>
                            <span class="badge bg-secondary"><?= ucfirst($status_filter) ?> Only</span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($all_orders)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No Orders Found</h5>
                            <p class="text-muted">No orders match the current filter criteria.</p>
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
                                        <th>Order Status</th>
                                        <th>Kitchen Status</th>
                                        <th>Payment Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($all_orders as $order): ?>
                                    <tr>
                                        <td>
                                            <strong>#<?= $order['id'] ?></strong>
                                        </td>
                                        <td><?= htmlspecialchars($order['table_name']) ?></td>
                                        <td><?= htmlspecialchars($order['waiter_name']) ?></td>
                                        <td><?= date('H:i', strtotime($order['created_at'])) ?></td>
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
                                            <?php if ($order['status'] == 'pending' && $order['kitchen_status'] == 'ready'): ?>
                                                <span class="badge badge-success">Ready for Payment</span>
                                            <?php elseif ($order['status'] == 'completed'): ?>
                                                <span class="badge badge-success">Paid</span>
                                            <?php else: ?>
                                                <span class="badge badge-secondary">Not Ready</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <?php if ($order['status'] == 'pending' && $order['kitchen_status'] == 'ready'): ?>
                                                    <a href="process_payment.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-success">
                                                        <i class="fas fa-credit-card"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
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