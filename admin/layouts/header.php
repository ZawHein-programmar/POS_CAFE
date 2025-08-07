<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - POS Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #6c757d 0%, #495057 100%);
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
                        <i class="fas fa-user-shield fa-2x mb-2"></i>
                        <h5>Admin Panel</h5>
                        <small>Administrator</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'users.php' ? 'active' : '' ?>" href="users.php">
                            <i class="fas fa-users"></i> Users
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'products.php' ? 'active' : '' ?>" href="products.php">
                            <i class="fas fa-box"></i> Products
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'main_categories.php' ? 'active' : '' ?>" href="main_categories.php">
                            <i class="fas fa-tags"></i> Main Categories
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'second_categories.php' ? 'active' : '' ?>" href="second_categories.php">
                            <i class="fas fa-tag"></i> Sub Categories
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'tables.php' ? 'active' : '' ?>" href="tables.php">
                            <i class="fas fa-table"></i> Tables
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'orders.php' ? 'active' : '' ?>" href="orders.php">
                            <i class="fas fa-shopping-cart"></i> Orders
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'payments.php' ? 'active' : '' ?>" href="payments.php">
                            <i class="fas fa-credit-card"></i> Payments
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'discounts.php' ? 'active' : '' ?>" href="discounts.php">
                            <i class="fas fa-percent"></i> Discounts
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
                                <span class="navbar-brand mb-0 h1">POS Cafe - Admin</span>
                            </div>
                            
                            <div class="navbar-nav ms-auto">
                                <div class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle"></i> Administrator
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