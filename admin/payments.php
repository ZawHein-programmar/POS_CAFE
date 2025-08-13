<?php
require_once("../auth/isLogin.php");
require_once '../require/db.php';

// Date range for reports
$start_date = $_GET['start_date'] ?? date('Y-m-d', strtotime('-7 days'));
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Payments in selected date range (with amounts)
$result = $mysqli->query("
    SELECT
        p.id,
        p.payment_date,
        p.transaction_code,
        o.id AS order_id,
        o.total_amount,
        pt.name AS payment_type_name
    FROM payment p
    JOIN orders o ON p.order_id = o.id
    JOIN payment_type pt ON p.payment_type_id = pt.id
    WHERE DATE(p.payment_date) BETWEEN '$start_date' AND '$end_date'
    ORDER BY p.payment_date DESC
");
$payments = $result->fetch_all(MYSQLI_ASSOC);

// All payments for full table view (unfiltered)
$resultAll = $mysqli->query("
    SELECT p.id, p.payment_date, p.transaction_code, o.id as order_id, pt.name as payment_type_name
    FROM payment p
    JOIN orders o ON p.order_id = o.id
    JOIN payment_type pt ON p.payment_type_id = pt.id
    ORDER BY p.id DESC
");
$all_payments = $resultAll->fetch_all(MYSQLI_ASSOC);

// Calculate totals for report
$total_revenue = array_sum(array_map(function($p) { return (float)$p['total_amount']; }, $payments));
$total_orders = count($payments);

// Payment method breakdown
$payment_methods = [];
foreach ($payments as $p) {
    $method = $p['payment_type_name'];
    if (!isset($payment_methods[$method])) {
        $payment_methods[$method] = ['count' => 0, 'amount' => 0];
    }
    $payment_methods[$method]['count']++;
    $payment_methods[$method]['amount'] += (float)$p['total_amount'];
}

// Include header
include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="card-title mb-0">Payments Report</h4>
                <form class="d-flex gap-2" method="get" action="payments.php">
                    <input type="date" name="start_date" value="<?= $start_date ?>" class="form-control">
                    <span class="align-self-center">to</span>
                    <input type="date" name="end_date" value="<?= $end_date ?>" class="form-control">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card bg-success text-white">
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
        <div class="col-md-4">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $total_orders ?></h4>
                            <p class="mb-0">Orders Completed</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 class="mb-0"><?= $total_orders > 0 ? number_format($total_revenue / $total_orders, 2) : '0.00' ?></h4>
                            <p class="mb-0">Average Order Value</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-calculator fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($payment_methods)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-chart-pie"></i> Payment Methods Breakdown</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach ($payment_methods as $method => $data): ?>
                        <div class="col-md-3 mb-3">
                            <div class="border rounded p-3 text-center">
                                <h6 class="text-primary"><?= htmlspecialchars($method) ?></h6>
                                <div class="row">
                                    <div class="col-6">
                                        <strong><?= $data['count'] ?></strong><br>
                                        <small class="text-muted">Orders</small>
                                    </div>
                                    <div class="col-6">
                                        <strong>$<?= number_format($data['amount'], 2) ?></strong><br>
                                        <small class="text-muted">Amount</small>
                                    </div>
                                </div>
                                <div class="mt-2">
                                    <small class="text-muted"><?= $total_revenue > 0 ? number_format(($data['amount'] / $total_revenue) * 100, 1) : '0' ?>% of total</small>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Recent Payments (selected range) -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list"></i> Recent Payments (<?= date('M j', strtotime($start_date)) ?> - <?= date('M j', strtotime($end_date)) ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No Payments Found</h5>
                            <p class="text-muted">No payments were processed in the selected date range.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Date</th>
                                        <th>Transaction</th>
                                        <th>Payment Method</th>
                                        <th>Amount</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach (array_slice($payments, 0, 20) as $payment): ?>
                                    <tr>
                                        <td><?= date('M j, Y', strtotime($payment['payment_date'])) ?></td>
                                        <td><span class="text-primary"><?= htmlspecialchars($payment['transaction_code']) ?></span></td>
                                        <td><span class="badge bg-info"><?= htmlspecialchars($payment['payment_type_name']) ?></span></td>
                                        <td><strong class="text-success">$<?= number_format((float)$payment['total_amount'], 2) ?></strong></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if (count($payments) > 20): ?>
                        <div class="text-center mt-3">
                            <small class="text-muted">Showing first 20 payments. Total: <?= count($payments) ?> payments.</small>
                        </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Full Payments Table (original) -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">All Payments</h4>
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered zero-configuration">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Order ID</th>
                                    <th>Payment Type</th>
                                    <th>Payment Date</th>
                                    <th>Transaction Code</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($all_payments as $payment): ?>
                                <tr>
                                    <td><?= $payment['id'] ?></td>
                                    <td><a href="order_items.php?order_id=<?= $payment['order_id'] ?>"><?= $payment['order_id'] ?></a></td>
                                    <td><?= htmlspecialchars($payment['payment_type_name']) ?></td>
                                    <td><?= $payment['payment_date'] ?></td>
                                    <td><?= htmlspecialchars($payment['transaction_code']) ?></td>
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
