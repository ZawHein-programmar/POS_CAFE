<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';
// session_start();

// if (!isset($_SESSION['waiter_id'])) {
//     header("Location: login.php");
//     exit;
// }

// Fetch all tables
$result = $mysqli->query("SELECT * FROM tables ORDER BY name ASC");
$tables = $result->fetch_all(MYSQLI_ASSOC);

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Tables</h4>
                    <div class="row">
                        <?php foreach ($tables as $table): ?>
                        <div class="col-md-3">
                            <div class="card text-center 
                                <?php 
                                    if ($table['status'] == 'occupied') echo 'bg-danger';
                                    elseif ($table['status'] == 'reserved') echo 'bg-warning';
                                    else echo 'bg-success';
                                ?>">
                                <div class="card-body">
                                    <h5 class="card-title text-white"><?= htmlspecialchars($table['name']) ?></h5>
                                    <p class="card-text text-white"><?= ucfirst($table['status']) ?></p>
                                    <?php if ($table['status'] == 'available'): ?>
                                        <a href="menu.php?table_id=<?= $table['id'] ?>" class="btn btn-light">New Order</a>
                                    <?php else: 
                                        $order_result = $mysqli->query("SELECT id, kitchen_status FROM orders WHERE table_id = {$table['id']} AND status = 'pending'");
                                        $order = $order_result->fetch_assoc();
                                        if ($order):
                                    ?>
                                        <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-light">
                                            View Order 
                                            <span class="badge badge-<?= 
                                                $order['kitchen_status'] == 'pending' ? 'warning' : 
                                                ($order['kitchen_status'] == 'accepted' ? 'success' : 
                                                ($order['kitchen_status'] == 'rejected' ? 'danger' : 
                                                ($order['kitchen_status'] == 'preparing' ? 'info' : 
                                                ($order['kitchen_status'] == 'ready' ? 'primary' : 'secondary'))))
                                            ?>">
                                                <?= ucfirst($order['kitchen_status']) ?>
                                            </span>
                                        </a>
                                    <?php endif; endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>
