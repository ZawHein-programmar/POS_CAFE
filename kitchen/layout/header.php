<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>POS Cafe - Kitchen Panel</title>
    <link rel="stylesheet" href="../admin/plugins/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" href="../admin/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        .order-card {
            border-left: 4px solid #007bff;
            margin-bottom: 15px;
        }
        .order-card.pending {
            border-left-color: #ffc107;
        }
        .order-card.accepted {
            border-left-color: #28a745;
        }
        .order-card.rejected {
            border-left-color: #dc3545;
        }
        .order-card.preparing {
            border-left-color: #17a2b8;
        }
        .order-card.ready {
            border-left-color: #6f42c1;
        }
        .status-badge {
            font-size: 0.8em;
            padding: 5px 10px;
        }
        .kitchen-dashboard {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
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
                       <h3><i class="fas fa-utensils"></i> Kitchen Dashboard</h3>
                    </div>
                </div>
                <div class="header-right">
                    <ul class="clearfix">
                        <li class="icons dropdown">
                            <a href="pending_orders.php" class="log-user">
                                <i class="fa fa-clock"></i> Pending Orders
                            </a>
                        </li>
                        <li class="icons dropdown">
                            <a href="all_orders.php" class="log-user">
                                <i class="fa fa-list"></i> All Orders
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