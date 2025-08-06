<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Cafe - User Panel</title>
    <link rel="stylesheet" href="../admin/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../admin/css/style.css">
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
                       <h3>POS Cafe</h3>
                    </div>
                </div>
                <div class="header-right">
                    <ul class="clearfix">
                        <li class="icons dropdown">
                            <a href="notifications.php" class="log-user">
                                <i class="fa fa-bell"></i> Notifications
                                <?php
                                // Get unread notification count
                                if (isset($_SESSION['waiter_id'])) {
                                    $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
                                    $stmt->bind_param("i", $_SESSION['waiter_id']);
                                    $stmt->execute();
                                    $result = $stmt->get_result();
                                    $unread_count = $result->fetch_assoc()['count'];
                                    if ($unread_count > 0) {
                                        echo "<span class='badge badge-danger'>$unread_count</span>";
                                    }
                                }
                                ?>
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
