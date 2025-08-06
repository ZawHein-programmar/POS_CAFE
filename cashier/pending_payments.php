<?php
require_once '../require/db.php';
session_start();

if (!isset($_SESSION['cashier_id'])) {
    header("Location: login.php");
    exit;
}

// Handle payment processing
if (isset($_POST['process_payment'])) {
    $order_id = $_POST['order_id'];
    $payment_type_id = $_POST['payment_type_id'];
    $payment_date = date('Y-m-d');
    $transaction_code = 'TRN-' . time() . '-' . rand(1000, 9999);

    // Get order details
    $stmt = $mysqli->prepare("SELECT table_id, total_amount FROM orders WHERE id = ?");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $order = $stmt->get_result()->fetch_assoc();

    if ($order) {
        // Insert payment record
        $stmt = $mysqli->prepare("INSERT INTO payment (order_id, payment_type_id, payment_date, transaction_code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $order_id, $payment_type_id, $payment_date, $transaction_code);
        
        if ($stmt->execute()) {
            // Update order status
            $stmt = $mysqli->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            // Free the table
            $stmt = $mysqli->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
            $stmt->bind_param("i", $order['table_id']);
            $stmt->execute();

            $success_message = "Payment processed successfully! Transaction Code: $transaction_code";
        } else {
            $error_message = "Error processing payment: " . $mysqli->error;
        }
    }
}

// Fetch pending payments (orders ready for payment)
$pending_orders = $mysqli->query("
    SELECT o.id, o.order_date, o.total_amount, o.kitchen_status, t.name as table_name, u.name as waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
    WHERE o.status = 'pending' AND o.kitchen_status = 'ready'
    ORDER BY o.created_at ASC
")->fetch_all(MYSQLI_ASSOC);

// Fetch payment types
$payment_types = $mysqli->query("SELECT * FROM payment_type ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-clock"></i> Pending Payments</h2>
                <div class="text-end">
                    <span class="badge bg-warning fs-6"><?= count($pending_orders) ?> Orders Ready</span>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($success_message)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (empty($pending_orders)): ?>
        <div class="card">
            <div class="card-body text-center py-5">
                <i class="fas fa-check-circle fa-4x text-success mb-3"></i>
                <h4>No Pending Payments</h4>
                <p class="text-muted">All orders have been processed or are not ready for payment yet.</p>
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="row">
            <?php foreach ($pending_orders as $order): ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 border-warning">
                        <div class="card-header bg-warning text-white">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">
                                    <i class="fas fa-utensils"></i> Order #<?= $order['id'] ?>
                                </h5>
                                <span class="badge bg-white text-warning">Ready for Payment</span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Table:</strong><br>
                                    <span class="text-primary"><?= htmlspecialchars($order['table_name']) ?></span>
                                </div>
                                <div class="col-6">
                                    <strong>Waiter:</strong><br>
                                    <span class="text-info"><?= htmlspecialchars($order['waiter_name']) ?></span>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-6">
                                    <strong>Order Time:</strong><br>
                                    <span class="text-muted"><?= date('H:i', strtotime($order['order_date'])) ?></span>
                                </div>
                                <div class="col-6">
                                    <strong>Total Amount:</strong><br>
                                    <span class="text-success fs-5 fw-bold">$<?= number_format($order['total_amount'], 2) ?></span>
                                </div>
                            </div>

                            <hr>

                            <div class="d-grid gap-2">
                                <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-eye"></i> View Order Details
                                </a>
                                
                                <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#paymentModal<?= $order['id'] ?>">
                                    <i class="fas fa-credit-card"></i> Process Payment
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Modal -->
                <div class="modal fade" id="paymentModal<?= $order['id'] ?>" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-success text-white">
                                <h5 class="modal-title">
                                    <i class="fas fa-credit-card"></i> Process Payment - Order #<?= $order['id'] ?>
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                            </div>
                            <form method="post">
                                <div class="modal-body">
                                    <div class="alert alert-info">
                                        <strong>Order Summary:</strong><br>
                                        Table: <?= htmlspecialchars($order['table_name']) ?><br>
                                        Total Amount: <strong>$<?= number_format($order['total_amount'], 2) ?></strong>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="payment_type_id" class="form-label">
                                            <i class="fas fa-credit-card"></i> Payment Method
                                        </label>
                                        <select name="payment_type_id" id="payment_type_id" class="form-control" required>
                                            <option value="">Select Payment Method</option>
                                            <?php foreach ($payment_types as $type): ?>
                                                <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <strong>Note:</strong> This action will mark the order as completed and free the table.
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                        <i class="fas fa-times"></i> Cancel
                                    </button>
                                    <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                    <button type="submit" name="process_payment" class="btn btn-success">
                                        <i class="fas fa-check"></i> Confirm Payment
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'layout/footer.php'; ?> 