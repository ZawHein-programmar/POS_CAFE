<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

if (!isset($_SESSION['kitchen_id'])) {
    header("Location: login.php");
    exit;
}

// Filter by status
$status_filter = $_GET['status'] ?? 'all';

// Build query based on filter
$where_clause = "";
if ($status_filter != 'all') {
    $where_clause = "WHERE o.kitchen_status = '$status_filter'";
}

// Fetch all orders
$all_orders = $mysqli->query("
    SELECT o.id, o.order_date, o.kitchen_status, o.total_amount, o.created_at, o.kitchen_notes, t.name as table_name, u.name as waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
    $where_clause
    ORDER BY o.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

include 'layout/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">All Orders</h4>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <!-- Filter Buttons -->
                <div class="mb-4">
                    <div class="btn-group" role="group">
                        <a href="?status=all" class="btn btn-<?= $status_filter == 'all' ? 'primary' : 'outline-primary' ?>">All</a>
                        <a href="?status=pending" class="btn btn-<?= $status_filter == 'pending' ? 'warning' : 'outline-warning' ?>">Pending</a>
                        <a href="?status=accepted" class="btn btn-<?= $status_filter == 'accepted' ? 'success' : 'outline-success' ?>">Accepted</a>
                        <a href="?status=rejected" class="btn btn-<?= $status_filter == 'rejected' ? 'danger' : 'outline-danger' ?>">Rejected</a>
                        <a href="?status=preparing" class="btn btn-<?= $status_filter == 'preparing' ? 'info' : 'outline-info' ?>">Preparing</a>
                        <a href="?status=ready" class="btn btn-<?= $status_filter == 'ready' ? 'primary' : 'outline-primary' ?>">Ready</a>
                    </div>
                </div>

                <?php if (empty($all_orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-list fa-3x text-muted mb-3"></i>
                        <h4>No Orders Found</h4>
                        <p class="text-muted">No orders match the current filter.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Table</th>
                                    <th>Waiter</th>
                                    <th>Date</th>
                                    <th>Time</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Notes</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_orders as $order): ?>
                                <tr>
                                    <td><strong>#<?= $order['id'] ?></strong></td>
                                    <td><?= htmlspecialchars($order['table_name']) ?></td>
                                    <td><?= htmlspecialchars($order['waiter_name']) ?></td>
                                    <td><?= $order['order_date'] ?></td>
                                    <td><?= date('H:i:s', strtotime($order['created_at'])) ?></td>
                                    <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                    <td>
                                        <span class="badge bg-<?= 
                                            $order['kitchen_status'] == 'pending' ? 'warning' : 
                                            ($order['kitchen_status'] == 'accepted' ? 'success' : 
                                            ($order['kitchen_status'] == 'rejected' ? 'danger' : 
                                            ($order['kitchen_status'] == 'preparing' ? 'info' : 'primary')))
                                        ?>">
                                            <?= ucfirst($order['kitchen_status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($order['kitchen_notes'])): ?>
                                            <small class="text-muted"><?= htmlspecialchars($order['kitchen_notes']) ?></small>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> View
                                        </a>
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

<?php include 'layout/footer.php'; ?> 