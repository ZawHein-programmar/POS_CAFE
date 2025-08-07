<?php
require_once("../auth/isLogin.php");
require_once '../require/db.php';

// Fetch all payments with order and payment type information
$result = $mysqli->query("
    SELECT p.id, p.payment_date, p.transaction_code, o.id as order_id, pt.name as payment_type_name
    FROM payment p
    JOIN orders o ON p.order_id = o.id
    JOIN payment_type pt ON p.payment_type_id = pt.id
    ORDER BY p.id DESC
");
$payments = $result->fetch_all(MYSQLI_ASSOC);

// Include header
include 'layouts/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title">Payments</h4>
                    
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
                                <?php foreach ($payments as $payment): ?>
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
