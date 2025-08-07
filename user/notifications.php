<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';

// Mark notification as read
if (isset($_POST['mark_read'])) {
    $notification_id = $_POST['notification_id'];
    $stmt = $mysqli->prepare("UPDATE notifications SET is_read = TRUE WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notification_id, $_SESSION['waiter_id']);
    $stmt->execute();
}

// Mark all notifications as read
if (isset($_POST['mark_all_read'])) {
    $stmt = $mysqli->prepare("UPDATE notifications SET is_read = TRUE WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['waiter_id']);
    $stmt->execute();
}

// Fetch notifications for the logged-in waiter
$stmt = $mysqli->prepare("
    SELECT n.*, o.id as order_id, t.name as table_name
    FROM notifications n
    JOIN orders o ON n.order_id = o.id
    JOIN tables t ON o.table_id = t.id
    WHERE n.user_id = ?
    ORDER BY n.created_at DESC
");
$stmt->bind_param("i", $_SESSION['waiter_id']);
$stmt->execute();
$result = $stmt->get_result();
$notifications = $result->fetch_all(MYSQLI_ASSOC);

// Count unread notifications
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->bind_param("i", $_SESSION['waiter_id']);
$stmt->execute();
$result = $stmt->get_result();
$unread_count = $result->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - POS Cafe</title>
    <link rel="stylesheet" href="../admin/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .notification-card {
            transition: all 0.3s ease;
            border-left: 4px solid #007bff;
        }
        .notification-card.unread {
            background-color: #f8f9fa;
            border-left-color: #dc3545;
        }
        .notification-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .notification-badge {
            font-size: 0.75em;
            padding: 4px 8px;
        }
        .notification-time {
            font-size: 0.85em;
            color: #6c757d;
        }
        .notification-message {
            font-size: 0.95em;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div id="main-wrapper">
        <div class="header">
            <div class="header-content clearfix">
                <div class="nav-control">
                    <div class="hamburger">
                        <span class="toggle-icon"><i class="icon-menu"></i></span>
                    </div>
                </div>
                <div class="header-left">
                    <div class="input-group icons">
                       <h3><i class="fas fa-bell"></i> Notifications</h3>
                    </div>
                </div>
                <div class="header-right">
                    <ul class="clearfix">
                        <li class="icons dropdown">
                            <a href="index.php" class="log-user">
                                <i class="fa fa-home"></i> Back to Tables
                            </a>
                        </li>
                        <li class="icons dropdown">
                            <a href="order_history.php" class="log-user">
                                <i class="fa fa-history"></i> Order History
                            </a>
                        </li>
                        <li class="icons dropdown">
                            <a href="logout.php" class="log-user">
                                <i class="fa fa-sign-out"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="content-body">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div>
                                <h2 class="mb-0">Notifications</h2>
                                <small class="text-muted">Manage your order updates and kitchen communications</small>
                            </div>
                            <div>
                                <?php if ($unread_count > 0): ?>
                                    <form method="post" class="d-inline">
                                        <button type="submit" name="mark_all_read" class="btn btn-info">
                                            <i class="fas fa-check-double"></i> Mark All Read
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>

                <?php if (empty($notifications)): ?>
                    <div class="alert alert-info text-center py-5">
                        <i class="fas fa-bell-slash fa-3x mb-3 text-muted"></i>
                        <h5>No notifications found</h5>
                        <p class="text-muted">You'll see order updates and kitchen communications here.</p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($notifications as $notification): ?>
                            <div class="col-12 mb-3">
                                <div class="card notification-card <?= $notification['is_read'] ? '' : 'unread' ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <div class="d-flex align-items-center mb-2">
                                                    <span class="badge notification-badge badge-<?= $notification['type'] ?> mr-2">
                                                        <i class="fas fa-<?= 
                                                            $notification['type'] == 'danger' ? 'exclamation-triangle' : 
                                                            ($notification['type'] == 'warning' ? 'exclamation-circle' : 
                                                            ($notification['type'] == 'success' ? 'check-circle' : 'info-circle'))
                                                        ?>"></i>
                                                        <?= ucfirst($notification['type']) ?>
                                                    </span>
                                                    <span class="notification-time">
                                                        <i class="fas fa-clock"></i> 
                                                        <?= date('M j, Y g:i A', strtotime($notification['created_at'])) ?>
                                                    </span>
                                                </div>
                                                <p class="notification-message mb-2"><?= htmlspecialchars($notification['message']) ?></p>
                                                <div class="d-flex align-items-center">
                                                    <span class="badge badge-light mr-2">
                                                        <i class="fas fa-table"></i> <?= htmlspecialchars($notification['table_name']) ?>
                                                    </span>
                                                    <span class="badge badge-light">
                                                        <i class="fas fa-receipt"></i> Order #<?= $notification['order_id'] ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="ml-3 d-flex flex-column">
                                                <?php if (!$notification['is_read']): ?>
                                                    <form method="post" class="mb-2">
                                                        <input type="hidden" name="notification_id" value="<?= $notification['id'] ?>">
                                                        <button type="submit" name="mark_read" class="btn btn-sm btn-outline-success btn-block">
                                                            <i class="fas fa-check"></i> Mark Read
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="badge badge-success mb-2">
                                                        <i class="fas fa-check"></i> Read
                                                    </span>
                                                <?php endif; ?>
                                                <a href="order_details.php?order_id=<?= $notification['order_id'] ?>" class="btn btn-sm btn-primary btn-block">
                                                    <i class="fas fa-eye"></i> View Order
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="../admin/plugins/common/common.min.js"></script>
<script src="../admin/js/custom.min.js"></script>
<script src="../admin/js/settings.js"></script>
<script src="../admin/js/gleek.js"></script>
<script src="../admin/js/styleSwitcher.js"></script>
</body>
</html> 