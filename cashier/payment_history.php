<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

// Get anchor date and period for payment history
$filter_date = $_GET['date'] ?? date('Y-m-d');
$period = $_GET['period'] ?? 'day';

$start_date = $filter_date;
$end_date = $filter_date;
$anchor_ts = strtotime($filter_date);

switch ($period) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('monday this week', $anchor_ts));
        $end_date = date('Y-m-d', strtotime('sunday this week', $anchor_ts));
        break;
    case 'month':
        $start_date = date('Y-m-01', $anchor_ts);
        $end_date = date('Y-m-t', $anchor_ts);
        break;
    case 'year':
        $start_date = date('Y-01-01', $anchor_ts);
        $end_date = date('Y-12-31', $anchor_ts);
        break;
    default: // day
        $start_date = $filter_date;
        $end_date = $filter_date;
}

$range_label = '';
if ($period === 'week') {
    $range_label = date('M j', strtotime($start_date)) . ' - ' . date('M j, Y', strtotime($end_date));
} elseif ($period === 'month') {
    $range_label = date('F Y', $anchor_ts);
} elseif ($period === 'year') {
    $range_label = date('Y', $anchor_ts);
} else {
    $range_label = date('F j, Y', $anchor_ts);
}

// Fetch payment history
$payments = $mysqli->query("
    SELECT p.id, p.payment_date, p.transaction_code, p.created_at,
           o.id as order_id, o.total_amount, o.order_date,
           t.name as table_name, u.name as waiter_name,
           pt.name as payment_type
    FROM payment p
    JOIN orders o ON p.order_id = o.id
    JOIN tables t ON o.table_id = t.id
    JOIN user u ON o.user_id = u.id
    JOIN payment_type pt ON p.payment_type_id = pt.id
    WHERE DATE(p.payment_date) BETWEEN '$start_date' AND '$end_date'
    ORDER BY p.created_at DESC
")->fetch_all(MYSQLI_ASSOC);

// Calculate totals
$total_revenue = array_sum(array_column($payments, 'total_amount'));
$total_orders = count($payments);

// Payment method breakdown
$payment_methods = [];
foreach ($payments as $payment) {
    $method = $payment['payment_type'];
    if (!isset($payment_methods[$method])) {
        $payment_methods[$method] = ['count' => 0, 'amount' => 0];
    }
    $payment_methods[$method]['count']++;
    $payment_methods[$method]['amount'] += $payment['total_amount'];
}

include 'layout/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-history"></i> Payment History</h2>
                <div class="d-flex align-items-center gap-3">
                    <form class="d-flex gap-2" method="get">
                        <input type="date" name="date" value="<?= $filter_date ?>" class="form-control" onchange="this.form.submit()">
                        <select name="period" class="form-select" onchange="this.form.submit()">
                            <option value="day" <?= $period === 'day' ? 'selected' : '' ?>>Day</option>
                            <option value="week" <?= $period === 'week' ? 'selected' : '' ?>>Week</option>
                            <option value="month" <?= $period === 'month' ? 'selected' : '' ?>>Month</option>
                            <option value="year" <?= $period === 'year' ? 'selected' : '' ?>>Year</option>
                        </select>
                        <a href="?date=<?= date('Y-m-d') ?>&period=day" class="btn btn-outline-primary">Today</a>
                    </form>
                </div>
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
                            <h4 class="mb-0"><?= count($payment_methods) ?></h4>
                            <p class="mb-0">Payment Methods Used</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-credit-card fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Methods Breakdown -->
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
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- Payment History Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list"></i> Payment Details - <?= $range_label ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5>No Payments Found</h5>
                            <p class="text-muted">No payments were processed in this period.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Transaction</th>
                                        <th>Order ID</th>
                                        <th>Table</th>
                                        <th>Waiter</th>
                                        <th>Payment Method</th>
                                        <th>Amount</th>
                                        <th>Time</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($payments as $payment): ?>
                                    <tr>
                                        <td>
                                            <strong class="text-primary"><?= htmlspecialchars($payment['transaction_code']) ?></strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary">#<?= $payment['order_id'] ?></span>
                                        </td>
                                        <td><?= htmlspecialchars($payment['table_name']) ?></td>
                                        <td><?= htmlspecialchars($payment['waiter_name']) ?></td>
                                        <td>
                                            <span class="badge bg-info"><?= htmlspecialchars($payment['payment_type']) ?></span>
                                        </td>
                                        <td>
                                            <strong class="text-success">$<?= number_format($payment['total_amount'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?= date('H:i', strtotime($payment['created_at'])) ?>
                                            </small>
                                        </td>
                                        <td>
                                            <a href="order_details.php?order_id=<?= $payment['order_id'] ?>" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-eye"></i> View
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="printReceipt('<?= $payment['transaction_code'] ?>')">
                                                <i class="fas fa-print"></i> Receipt
                                            </button>
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
</div>

<script>
function printReceipt(transactionCode) {
    // Open receipt in new window for printing
    window.open('print_receipt.php?transaction=' + transactionCode, '_blank', 'width=400,height=600');
}
</script>

<?php include 'layout/footer.php'; ?> 