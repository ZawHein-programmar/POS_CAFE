<?php
require_once("../auth/isLogin.php");
require_once '../require/db.php';

// Fetch all orders with user and table information
$result = $mysqli->query("
    SELECT o.id, o.order_date, o.status, o.total_amount, t.name as table_name, u.name as user_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
    ORDER BY o.id DESC
");
$orders = $result->fetch_all(MYSQLI_ASSOC);

// Include header
include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Orders</h4>
                    
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered zero-configuration">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Table</th>
                                    <th>User</th>
                                    <th>Order Date</th>
                                    <th>Status</th>
                                    <th>Total Amount</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                <tr>
                                    <td><?= $order['id'] ?></td>
                                    <td><?= htmlspecialchars($order['table_name']) ?></td>
                                    <td><?= htmlspecialchars($order['user_name']) ?></td>
                                    <td><?= $order['order_date'] ?></td>
                                    <td><?= ucfirst($order['status']) ?></td>
                                    <td><?= $order['total_amount'] ?></td>
                                    <td>
                                        <a href="order_items.php?order_id=<?= $order['id'] ?>" class="btn btn-sm btn-primary">View Items</a>
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

<?php
// Include footer
include 'layouts/footer.php';
?>
