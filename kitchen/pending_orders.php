<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

if (!isset($_SESSION['kitchen_id'])) {
    header("Location: login.php");
    exit;
}

// Handle Accept/Reject actions
if (isset($_POST['action']) && isset($_POST['order_id'])) {
    $order_id = $_POST['order_id'];
    $action = $_POST['action'];
    $notes = $_POST['notes'] ?? '';

    if ($action == 'accept') {
        $stmt = $mysqli->prepare("UPDATE orders SET kitchen_status = 'accepted', kitchen_notes = ? WHERE id = ?");
        $stmt->bind_param("si", $notes, $order_id);
        $stmt->execute();
        $message = "Order #$order_id has been accepted.";
    } elseif ($action == 'reject') {
        $stmt = $mysqli->prepare("UPDATE orders SET kitchen_status = 'rejected', kitchen_notes = ? WHERE id = ?");
        $stmt->bind_param("si", $notes, $order_id);
        $stmt->execute();
        $message = "Order #$order_id has been rejected.";
    }
}

// Fetch pending orders
$pending_orders = $mysqli->query("
    SELECT o.id, o.order_date, o.total_amount, o.created_at, t.name as table_name, u.name as waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
    WHERE o.kitchen_status = 'pending'
    ORDER BY o.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2>Pending Orders</h2>
                <a href="index.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
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

            <?php if (empty($pending_orders)): ?>
                <div class="card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                        <h4>No Pending Orders</h4>
                        <p class="text-muted">All orders have been processed!</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="row">
                    <?php foreach ($pending_orders as $order): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="card order-card pending">
                                <div class="card-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Order #<?= $order['id'] ?></h5>
                                        <span class="badge badge-warning status-badge">Pending</span>
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
                                    
                                    <div class="text-center">
                                        <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-info btn-sm mb-2">
                                            <i class="fas fa-eye"></i> View Details
                                        </a>
                                        
                                        <button type="button" class="btn btn-success btn-sm mb-2" data-toggle="modal" data-target="#acceptModal<?= $order['id'] ?>">
                                            <i class="fas fa-check"></i> Accept
                                        </button>
                                        
                                        <button type="button" class="btn btn-danger btn-sm mb-2" data-toggle="modal" data-target="#rejectModal<?= $order['id'] ?>">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Accept Modal -->
                        <div class="modal fade" id="acceptModal<?= $order['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Accept Order #<?= $order['id'] ?></h5>
                                        <button type="button" class="close" data-dismiss="modal">
                                            <span>&times;</span>
                                        </button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <p>Are you sure you want to accept this order?</p>
                                            <div class="form-group">
                                                <label for="notes">Notes (optional):</label>
                                                <textarea name="notes" class="form-control" rows="3" placeholder="Add any notes about this order..."></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <input type="hidden" name="action" value="accept">
                                            <button type="submit" class="btn btn-success">Accept Order</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Reject Modal -->
                        <div class="modal fade" id="rejectModal<?= $order['id'] ?>" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Reject Order #<?= $order['id'] ?></h5>
                                        <button type="button" class="close" data-dismiss="modal">
                                            <span>&times;</span>
                                        </button>
                                    </div>
                                    <form method="post">
                                        <div class="modal-body">
                                            <p>Are you sure you want to reject this order?</p>
                                            <div class="form-group">
                                                <label for="notes">Reason for rejection:</label>
                                                <textarea name="notes" class="form-control" rows="3" placeholder="Please provide a reason for rejection..." required></textarea>
                                            </div>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                                            <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                            <input type="hidden" name="action" value="reject">
                                            <button type="submit" class="btn btn-danger">Reject Order</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?> 