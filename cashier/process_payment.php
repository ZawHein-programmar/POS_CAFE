<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;

if ($order_id <= 0) {
    header('Location: pending_payments.php');
    exit;
}

// Fetch order and its basic info
$stmt = $mysqli->prepare("SELECT o.*, t.name AS table_name, u.name AS waiter_name FROM orders o JOIN tables t ON o.table_id = t.id JOIN user u ON o.user_id = u.id WHERE o.id = ?");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

if (!$order) {
    header('Location: pending_payments.php');
    exit;
}

// Fetch payment types
$payment_types = $mysqli->query("SELECT * FROM payment_type ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

$success_message = null;
$error_message = null;
$transaction_code = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
    $payment_type_id = (int)($_POST['payment_type_id'] ?? 0);
    if ($payment_type_id <= 0) {
        $error_message = 'Please select a payment method.';
    } else if ($order['status'] === 'completed') {
        $error_message = 'This order has already been paid.';
    } else {
        $payment_date = date('Y-m-d');
        $transaction_code = 'TRN-' . time() . '-' . rand(1000, 9999);

        // Insert payment
        $stmt = $mysqli->prepare("INSERT INTO payment (order_id, payment_type_id, payment_date, transaction_code) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $order_id, $payment_type_id, $payment_date, $transaction_code);
        if ($stmt->execute()) {
            // Update order status to completed
            $stmt = $mysqli->prepare("UPDATE orders SET status = 'completed' WHERE id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();

            // Free the table
            $stmt = $mysqli->prepare("UPDATE tables SET status = 'available' WHERE id = ?");
            $stmt->bind_param("i", $order['table_id']);
            $stmt->execute();

            $success_message = "Payment processed successfully!";
            // Refresh order info
            $stmt = $mysqli->prepare("SELECT o.*, t.name AS table_name, u.name AS waiter_name FROM orders o JOIN tables t ON o.table_id = t.id JOIN user u ON o.user_id = u.id WHERE o.id = ?");
            $stmt->bind_param("i", $order_id);
            $stmt->execute();
            $order = $stmt->get_result()->fetch_assoc();
        } else {
            $error_message = 'Error processing payment: ' . $mysqli->error;
        }
    }
}

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-credit-card"></i> Process Payment - Order #<?= $order_id ?></h2>
                <a href="order_details.php?order_id=<?= $order_id ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Order
                </a>
            </div>
        </div>
    </div>

    <?php if ($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= htmlspecialchars($success_message) ?>
            <?php if ($transaction_code): ?>
                <div class="mt-2">
                    <a class="btn btn-sm btn-outline-primary" target="_blank" href="print_receipt.php?transaction=<?= urlencode($transaction_code) ?>"><i class="fas fa-print"></i> Print Receipt</a>
                    <a class="btn btn-sm btn-primary" href="order_details.php?order_id=<?= $order_id ?>">
                        <i class="fas fa-eye"></i> View Order
                    </a>
                </div>
            <?php endif; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= htmlspecialchars($error_message) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Order Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-6"><strong>Table:</strong><br><span class="text-primary"><?= htmlspecialchars($order['table_name']) ?></span></div>
                        <div class="col-6"><strong>Waiter:</strong><br><span class="text-info"><?= htmlspecialchars($order['waiter_name']) ?></span></div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-6"><strong>Order Date:</strong><br><span class="text-muted"><?= date('F j, Y', strtotime($order['order_date'])) ?></span></div>
                        <div class="col-6"><strong>Total Amount:</strong><br><span class="text-success fs-5 fw-bold">$<?= number_format($order['total_amount'], 2) ?></span></div>
                    </div>
                    <div class="row">
                        <div class="col-6"><strong>Kitchen Status:</strong><br><span class="badge bg-<?= $order['kitchen_status'] === 'ready' ? 'primary' : 'secondary' ?>"><?= htmlspecialchars(ucfirst($order['kitchen_status'])) ?></span></div>
                        <div class="col-6"><strong>Order Status:</strong><br><span class="badge bg-<?= $order['status'] === 'completed' ? 'success' : 'warning' ?>"><?= htmlspecialchars(ucfirst($order['status'])) ?></span></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="fas fa-wallet"></i> Payment</h5>
                </div>
                <div class="card-body">
                    <?php if ($order['status'] === 'completed'): ?>
                        <div class="alert alert-info"><i class="fas fa-info-circle"></i> This order is already paid.</div>
                        <?php
                        // Fetch payment info to show receipt link
                        $stmt = $mysqli->prepare("SELECT transaction_code FROM payment WHERE order_id = ? ORDER BY id DESC LIMIT 1");
                        $stmt->bind_param("i", $order_id);
                        $stmt->execute();
                        $tx = $stmt->get_result()->fetch_assoc();
                        if ($tx): ?>
                            <a class="btn btn-outline-primary w-100" target="_blank" href="print_receipt.php?transaction=<?= urlencode($tx['transaction_code']) ?>"><i class="fas fa-print"></i> Print Receipt</a>
                        <?php endif; ?>
                    <?php else: ?>
                        <form method="post">
                            <div class="mb-3">
                                <label for="payment_type_id" class="form-label"><i class="fas fa-credit-card"></i> Payment Method</label>
                                <select name="payment_type_id" id="payment_type_id" class="form-control" required>
                                    <option value="">Select Payment Method</option>
                                    <?php foreach ($payment_types as $type): ?>
                                        <option value="<?= $type['id'] ?>"><?= htmlspecialchars($type['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> This will mark the order as completed and free the table.
                            </div>
                            <button type="submit" name="process_payment" class="btn btn-success w-100"><i class="fas fa-check"></i> Confirm Payment</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'layout/footer.php'; ?>