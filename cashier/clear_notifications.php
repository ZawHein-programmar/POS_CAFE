<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

// Clear all notifications for the current user
$stmt = $mysqli->prepare("DELETE FROM notifications WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['cashier_id']);
$stmt->execute();

// Redirect back to dashboard
header("Location: index.php");
exit;
?> 