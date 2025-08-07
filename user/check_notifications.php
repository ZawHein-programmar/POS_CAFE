<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

// Check if user is logged in as waiter
if (!isset($_SESSION['waiter_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

// Get unread notifications count
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->bind_param("i", $_SESSION['waiter_id']);
$stmt->execute();
$result = $stmt->get_result();
$unread_count = $result->fetch_assoc()['count'];

// Get count of new notifications in the last 5 minutes
$stmt = $mysqli->prepare("
    SELECT COUNT(*) as count 
    FROM notifications 
    WHERE user_id = ? 
    AND created_at > DATE_SUB(NOW(), INTERVAL 5 MINUTE)
");
$stmt->bind_param("i", $_SESSION['waiter_id']);
$stmt->execute();
$result = $stmt->get_result();
$new_notifications = $result->fetch_assoc()['count'];

// Get count of orders ready for payment for this waiter
$stmt = $mysqli->prepare("
    SELECT COUNT(*) as count 
    FROM orders 
    WHERE user_id = ? 
    AND status = 'pending' 
    AND kitchen_status = 'ready'
");
$stmt->bind_param("i", $_SESSION['waiter_id']);
$stmt->execute();
$result = $stmt->get_result();
$orders_ready = $result->fetch_assoc()['count'];

// Return JSON response
header('Content-Type: application/json');
echo json_encode([
    'unread_count' => $unread_count,
    'new_notifications' => $new_notifications,
    'orders_ready' => $orders_ready,
    'timestamp' => date('Y-m-d H:i:s')
]);
?> 