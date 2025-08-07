<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

if (!isset($_SESSION['kitchen_id'])) {
    header("Location: login.php");
    exit;
}

// Fetch accepted orders
$accepted_orders = $mysqli->query("
    SELECT o.id, o.order_date, o.total_amount, o.created_at, o.kitchen_notes, t.name as table_name, u.name as waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
    WHERE o.kitchen_status = 'accepted'
    ORDER BY o.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

include 'layout/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Accepted Orders</h4>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($accepted_orders)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-info-circle fa-3x text-info mb-3"></i>
                        <h4>No Accepted Orders</h4>
                        <p class="text-muted">No orders have been accepted yet.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($accepted_orders as $order): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card order-card accepted">
                                    <div class="card-header">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0">Order #<?= $order['id'] ?></h5>
                                            <span class="badge bg-success status-badge">Accepted</span>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <strong>Table:</strong><br>
                                                <?= htmlspecialchars($order['table_name']) ?>
                                            </div>
                                            <div class="col-6">
                                                <strong>Waiter:</strong><br>
                                                <?= htmlspecialchars($order['waiter_name']) ?>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-6">
                                                <strong>Date:</strong><br>
                                                <?= $order['order_date'] ?>
                                            </div>
                                            <div class="col-6">
                                                <strong>Amount:</strong><br>
                                                $<?= number_format($order['total_amount'], 2) ?>
                                            </div>
                                        </div>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <strong>Order Time:</strong><br>
                                                <?= date('H:i:s', strtotime($order['created_at'])) ?>
                                            </div>
                                        </div>
                                        
                                        <?php if (!empty($order['kitchen_notes'])): ?>
                                        <div class="row mb-3">
                                            <div class="col-12">
                                                <strong>Notes:</strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($order['kitchen_notes']) ?></small>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-center">
                                            <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-info btn-sm mb-2">
                                                <i class="fas fa-eye"></i> View Details
                                            </a>
                                            
                                            <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-warning btn-sm mb-2">
                                                <i class="fas fa-fire"></i> Start Preparing
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?> 