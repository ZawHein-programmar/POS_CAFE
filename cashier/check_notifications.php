<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

// Check if user is logged in as cashier
if (!isset($_SESSION['cashier_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get unread notifications count
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->bind_param("i", $_SESSION['cashier_id']);
$stmt->execute();
$result = $stmt->get_result();
$unread_count = $result->fetch_assoc()['count'];

// Get count of new orders ready for payment (orders that became ready in the last 5 minutes)
$stmt = $mysqli->prepare("
    SELECT COUNT(*) as count 
    FROM orders o 
    WHERE o.status = 'pending' 
    AND o.kitchen_status = 'ready' 
    AND o.updated_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
");
$stmt->execute();
$result = $stmt->get_result();
$new_orders_ready = $result->fetch_assoc()['count'];

// Get pending payments count
$stmt = $mysqli->prepare("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE status = 'pending' AND kitchen_status = 'ready'
");
$stmt->execute();
$result = $stmt->get_result();
$pending_payments = $result->fetch_assoc()['count'];

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'unread_count' => $unread_count,
    'new_orders_ready' => $new_orders_ready,
    'pending_payments' => $pending_payments,
    'timestamp' => date('Y-m-d H:i:s')
]);
?> 