<?php
require_once '../require/db.php';
session_start();

if (!isset($_SESSION['cashier_id'])) {
    header("Location: login.php");
    exit;
}

// Clear notifications for this cashier
$stmt = $mysqli->prepare("DELETE FROM notifications WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['cashier_id']);
$stmt->execute();

// Redirect back to dashboard
header("Location: index.php");
exit;
?> 