<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

$order_id = $_GET['order_id'] ?? 0;

if (empty($order_id)) {
    header("Location: index.php");
    exit;
}

// Fetch order details
$stmt = $mysqli->prepare("
    SELECT o.*, t.name as table_name, u.name as waiter_name
    FROM orders o
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order = $result->fetch_assoc();

if (!$order) {
    header("Location: index.php");
    exit;
}

// Fetch order items
$stmt = $mysqli->prepare("
    SELECT oi.quantity, oi.unit_price, oi.status as item_status, p.name as product_name
    FROM order_items oi
    JOIN products p ON oi.product_id = p.id
    WHERE oi.order_id = ?
    ORDER BY oi.created_at ASC
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();
$order_items = $result->fetch_all(MYSQLI_ASSOC);

// Fetch payment details if order is completed
$payment = null;
if ($order['status'] == 'completed') {
    $stmt = $mysqli->prepare("
        SELECT p.*, pt.name as payment_type_name
        FROM payment p
        JOIN payment_type pt ON p.payment_type_id = pt.id
        WHERE p.order_id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $payment = $result->fetch_assoc();
}

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-receipt"></i> Order #<?= $order_id ?> Details</h2>
                <a href="javascript:history.back()" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Order Information -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle"></i> Order Information</h5>
                </div>
                <div class="card-body">
                    <table class="table table-borderless">
                        <tr>
                            <td><strong>Order ID:</strong></td>
                            <td>#<?= $order_id ?></td>
                        </tr>
                        <tr>
                            <td><strong>Table:</strong></td>
                            <td><?= htmlspecialchars($order['table_name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Waiter:</strong></td>
                            <td><?= htmlspecialchars($order['waiter_name']) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Order Date:</strong></td>
                            <td><?= date('F j, Y', strtotime($order['order_date'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Order Time:</strong></td>
                            <td><?= date('H:i:s', strtotime($order['created_at'])) ?></td>
                        </tr>
                        <tr>
                            <td><strong>Total Amount:</strong></td>
                            <td><span class="text-success fs-5 fw-bold">$<?= number_format($order['total_amount'], 2) ?></span></td>
                        </tr>
                        <tr>
                            <td><strong>Order Status:</strong></td>
                            <td>
                                <span class="badge badge-<?= 
                                    $order['status'] == 'pending' ? 'warning' : 
                                    ($order['status'] == 'completed' ? 'success' : 'danger')
                                ?> fs-6">
                                    <?= ucfirst($order['status']) ?>
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <td><strong>Kitchen Status:</strong></td>
                            <td>
                                <span class="badge badge-<?= 
                                    $order['kitchen_status'] == 'pending' ? 'warning' : 
                                    ($order['kitchen_status'] == 'accepted' ? 'success' : 
                                    ($order['kitchen_status'] == 'rejected' ? 'danger' : 
                                    ($order['kitchen_status'] == 'preparing' ? 'info' : 
                                    ($order['kitchen_status'] == 'ready' ? 'primary' : 'secondary'))))
                                ?> fs-6">
                                    <?= ucfirst($order['kitchen_status']) ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>

            <!-- Payment Information -->
            <?php if ($payment): ?>
                <div class="card mt-3">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0"><i class="fas fa-credit-card"></i> Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-borderless">
                            <tr>
                                <td><strong>Transaction Code:</strong></td>
                                <td><span class="text-primary"><?= htmlspecialchars($payment['transaction_code']) ?></span></td>
                            </tr>
                            <tr>
                                <td><strong>Payment Method:</strong></td>
                                <td><?= htmlspecialchars($payment['payment_type_name']) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Payment Date:</strong></td>
                                <td><?= date('F j, Y', strtotime($payment['payment_date'])) ?></td>
                            </tr>
                            <tr>
                                <td><strong>Payment Time:</strong></td>
                                <td><?= date('H:i:s', strtotime($payment['created_at'])) ?></td>
                            </tr>
                        </table>
                        <div class="text-center mt-3">
                            <button type="button" class="btn btn-outline-primary" onclick="printReceipt('<?= $payment['transaction_code'] ?>')">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Kitchen Notes -->
            <?php if (!empty($order['kitchen_notes'])): ?>
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-sticky-note"></i> Kitchen Notes</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-0"><?= nl2br(htmlspecialchars($order['kitchen_notes'])) ?></p>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Order Items -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-utensils"></i> Order Items</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($order_items)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No Items Found</h5>
                            <p class="text-muted">No items were found for this order.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Product</th>
                                        <th>Quantity</th>
                                        <th>Unit Price</th>
                                        <th>Total</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <strong><?= htmlspecialchars($item['product_name']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary fs-6"><?= $item['quantity'] ?></span>
                                        </td>
                                        <td>$<?= number_format($item['unit_price'], 2) ?></td>
                                        <td>
                                            <strong>$<?= number_format($item['quantity'] * $item['unit_price'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge badge-<?= 
                                                $item['item_status'] == 'ordered' ? 'warning' : 
                                                ($item['item_status'] == 'preparing' ? 'info' : 
                                                ($item['item_status'] == 'ready' ? 'primary' : 
                                                ($item['item_status'] == 'served' ? 'success' : 'danger')))
                                            ?> fs-6">
                                                <?= ucfirst($item['item_status']) ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-success">
                                        <th colspan="3" class="text-end">Grand Total:</th>
                                        <th colspan="2"><strong>$<?= number_format($order['total_amount'], 2) ?></strong></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="card mt-3">
                <div class="card-body">
                    <div class="d-flex gap-2">
                        <?php if ($order['status'] == 'pending' && $order['kitchen_status'] == 'ready'): ?>
                            <a href="process_payment.php?order_id=<?= $order_id ?>" class="btn btn-success">
                                <i class="fas fa-credit-card"></i> Process Payment
                            </a>
                        <?php endif; ?>
                        
                        <?php if ($order['status'] == 'completed' && $payment): ?>
                            <button type="button" class="btn btn-outline-primary" onclick="printReceipt('<?= $payment['transaction_code'] ?>')">
                                <i class="fas fa-print"></i> Print Receipt
                            </button>
                        <?php endif; ?>
                        
                        <a href="javascript:history.back()" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function printReceipt(transactionCode) {
    window.open('print_receipt.php?transaction=' + transactionCode, '_blank', 'width=420,height=720');
}
</script>

<?php include 'layout/footer.php'; ?> 