<?php
require_once '../require/db.php';
session_start();

if (!isset($_SESSION['waiter_id'])) {
    header("Location: login.php");
    exit;
}

$waiter_id = $_SESSION['waiter_id'];

// Fetch order history for the logged-in waiter
$stmt = $mysqli->prepare("
    SELECT o.id, o.order_date, o.status, o.kitchen_status, o.total_amount, t.name as table_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    WHERE o.user_id = ?
    ORDER BY o.id DESC
");
$stmt->bind_param("i", $waiter_id);
$stmt->execute();
$result = $stmt->get_result();
$orders = $result->fetch_all(MYSQLI_ASSOC);

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Your Order History</h4>
                    <a href="index.php" class="btn btn-secondary mb-3">Back to Tables</a>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered zero-configuration">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Table</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Kitchen Status</th>
                                    <th>Total Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['table_name']) ?></td>
                                    <td><?= $order['order_date'] ?></td>
                                    <td><?= ucfirst($order['status']) ?></td>
                                    <td>
                                        <span class="badge badge-<?= 
                                            $order['kitchen_status'] == 'pending' ? 'warning' : 
                                            ($order['kitchen_status'] == 'accepted' ? 'success' : 
                                            ($order['kitchen_status'] == 'rejected' ? 'danger' : 
                                            ($order['kitchen_status'] == 'preparing' ? 'info' : 'primary')))
                                        ?>">
                                            <?= ucfirst($order['kitchen_status']) ?>
                                        </span>
                                    </td>
                                    <td>$<?= number_format($order['total_amount'], 2) ?></td>
                                    <td>
                                        <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">View Details</a>
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
