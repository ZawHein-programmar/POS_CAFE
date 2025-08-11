<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

$transaction = $_GET['transaction'] ?? '';
if (!$transaction) {
    die('Missing transaction code');
}

$stmt = $mysqli->prepare("SELECT p.transaction_code, p.payment_date, p.created_at, o.id AS order_id, o.total_amount, o.order_date, t.name AS table_name, u.name AS waiter_name, pt.name AS payment_method
FROM payment p
JOIN orders o ON p.order_id = o.id
JOIN tables t ON o.table_id = t.id
JOIN user u ON o.user_id = u.id
JOIN payment_type pt ON p.payment_type_id = pt.id
WHERE p.transaction_code = ? LIMIT 1");
$stmt->bind_param("s", $transaction);
$stmt->execute();
$receipt = $stmt->get_result()->fetch_assoc();

if (!$receipt) {
    die('Invalid transaction code');
}

// Fetch order items
$stmt = $mysqli->prepare("SELECT oi.quantity, oi.unit_price, p.name AS product_name FROM order_items oi JOIN products p ON oi.product_id = p.id WHERE oi.order_id = ? ORDER BY oi.created_at ASC");
$stmt->bind_param("i", $receipt['order_id']);
$stmt->execute();
$items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />
<title>Receipt <?= htmlspecialchars($receipt['transaction_code']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet" />
<style>
    body { background: #f5f5f5; }
    .receipt { width: 380px; margin: 20px auto; background: #fff; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,.08); padding: 16px; }
    .brand { text-align: center; }
    .brand h4 { margin: 0; }
    .small { font-size: 12px; color: #6c757d; }
    .items td { padding: 6px 0; font-size: 14px; }
    .total-row { border-top: 1px dashed #ccc; font-weight: 700; }
    .footer-note { font-size: 12px; text-align: center; color: #6c757d; margin-top: 10px; }
    @media print {
        .no-print { display: none !important; }
        body { background: #fff; }
        .receipt { box-shadow: none; }
    }
</style>
</head>
<body>
<div class="no-print text-center mt-3">
    <a href="javascript:window.print()" class="btn btn-outline-secondary btn-sm"><i class="fas fa-print"></i> Print</a>
    <button id="downloadPdf" class="btn btn-primary btn-sm"><i class="fas fa-file-pdf"></i> Download PDF</button>
</div>
<div id="receipt" class="receipt">
    <div class="brand mb-2">
        <h4>POS Cafe</h4>
        <div class="small">Thank you for your visit!</div>
    </div>
    <div class="d-flex justify-content-between small mb-2">
        <div>
            <div><strong>Trans:</strong> <?= htmlspecialchars($receipt['transaction_code']) ?></div>
            <div><strong>Order #:</strong> <?= $receipt['order_id'] ?></div>
        </div>
        <div class="text-end">
            <div><?= date('Y-m-d', strtotime($receipt['payment_date'])) ?></div>
            <div><?= date('H:i', strtotime($receipt['created_at'])) ?></div>
        </div>
    </div>
    <div class="small mb-2">
        <div><strong>Table:</strong> <?= htmlspecialchars($receipt['table_name']) ?></div>
        <div><strong>Waiter:</strong> <?= htmlspecialchars($receipt['waiter_name']) ?></div>
        <div><strong>Payment:</strong> <?= htmlspecialchars($receipt['payment_method']) ?></div>
    </div>
    <table class="w-100 items">
        <tbody>
        <?php foreach ($items as $it): ?>
            <tr>
                <td><?= htmlspecialchars($it['product_name']) ?> x<?= (int)$it['quantity'] ?></td>
                <td class="text-end">$<?= number_format($it['unit_price'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
        <tr class="total-row">
            <td>Total</td>
            <td class="text-end">$<?= number_format($receipt['total_amount'], 2) ?></td>
        </tr>
        </tbody>
    </table>
    <div class="footer-note">No returns without receipt. Come again!</div>
</div>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/js/all.min.js"></script>
<script>
const { jsPDF } = window.jspdf || window.jspdf || {};
const btn = document.getElementById('downloadPdf');
btn?.addEventListener('click', async () => {
  const el = document.getElementById('receipt');
  const canvas = await html2canvas(el, { scale: 2, backgroundColor: '#ffffff' });
  const imgData = canvas.toDataURL('image/png');
  const pdf = new jsPDF({ orientation: 'portrait', unit: 'pt', format: [el.offsetWidth + 20, el.offsetHeight + 20] });
  pdf.addImage(imgData, 'PNG', 10, 10, el.offsetWidth, el.offsetHeight, undefined, 'FAST');
  pdf.save('receipt-<?= preg_replace('/[^A-Za-z0-9_-]/','', $receipt['transaction_code']) ?>.pdf');
});
</script>
</body>
</html>