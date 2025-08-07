<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - POS Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
            color: white;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 12px 20px;
            border-radius: 8px;
            margin: 2px 10px;
            transition: all 0.3s ease;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }
        .sidebar .nav-link i {
            width: 20px;
            margin-right: 10px;
        }
        .main-content {
            background: #f8f9fa;
            min-height: 100vh;
        }
        .navbar {
            background: white;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .card-header {
            background: white;
            border-bottom: 1px solid #e9ecef;
            border-radius: 10px 10px 0 0 !important;
        }
        .btn {
            border-radius: 8px;
        }
        .table th {
            border-top: none;
            font-weight: 600;
        }
        .badge {
            border-radius: 6px;
        }
        .form-control {
            border-radius: 8px;
        }
        .form-select {
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 px-0">
                <div class="sidebar">
                    <div class="p-3 text-center">
                        <i class="fas fa-user fa-2x mb-2"></i>
                        <h5>User Panel</h5>
                        <small><?= htmlspecialchars($_SESSION['waiter_name'] ?? 'User') ?></small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'menu.php' ? 'active' : '' ?>" href="menu.php">
                            <i class="fas fa-utensils"></i> Menu
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'cart.php' ? 'active' : '' ?>" href="cart.php">
                            <i class="fas fa-shopping-cart"></i> Cart
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'order_history.php' ? 'active' : '' ?>" href="order_history.php">
                            <i class="fas fa-history"></i> Order History
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>" href="notifications.php">
                            <i class="fas fa-bell"></i> Notifications
                            <?php
                            // Get unread notification count
                            if (isset($_SESSION['waiter_id'])) {
                                $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
                                $stmt->bind_param("i", $_SESSION['waiter_id']);
                                $stmt->execute();
                                $result = $stmt->get_result();
                                $unread_count = $result->fetch_assoc()['count'];
                                if ($unread_count > 0) {
                                    echo "<span class='badge bg-danger ms-2'>$unread_count</span>";
                                }
                            }
                            ?>
                        </a>
                        <hr class="my-3 mx-3" style="border-color: rgba(255,255,255,0.2);">
                        <a class="nav-link" href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-10 px-0">
                <div class="main-content">
                    <!-- Top Navbar -->
                    <nav class="navbar navbar-expand-lg navbar-light">
                        <div class="container-fluid">
                            <div class="d-flex align-items-center">
                                <button class="btn btn-link d-md-none" type="button">
                                    <i class="fas fa-bars"></i>
                                </button>
                                <span class="navbar-brand mb-0 h1">POS Cafe - User</span>
                            </div>
                            
                            <div class="navbar-nav ms-auto">
                                <div class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle"></i> <?= htmlspecialchars($_SESSION['waiter_name'] ?? 'User') ?>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user"></i> Profile</a></li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </nav>
                    
                    <!-- Page Content -->
                    <div class="p-4">
