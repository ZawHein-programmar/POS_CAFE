<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitchen Dashboard - POS Cafe</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #fd7e14 0%, #e55a00 100%);
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-2 px-0">
                <div class="sidebar">
                    <div class="p-3 text-center">
                        <i class="fas fa-utensils fa-2x mb-2"></i>
                        <h5>Kitchen Panel</h5>
                        <small>Kitchen Staff</small>
                    </div>
                    
                    <nav class="nav flex-column">
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active' : '' ?>" href="index.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'pending_orders.php' ? 'active' : '' ?>" href="pending_orders.php">
                            <i class="fas fa-clock"></i> Pending Orders
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'accepted_orders.php' ? 'active' : '' ?>" href="accepted_orders.php">
                            <i class="fas fa-check-circle"></i> Accepted Orders
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'preparing_orders.php' ? 'active' : '' ?>" href="preparing_orders.php">
                            <i class="fas fa-fire"></i> Preparing Orders
                        </a>
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'all_orders.php' ? 'active' : '' ?>" href="all_orders.php">
                            <i class="fas fa-list"></i> All Orders
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
                                <span class="navbar-brand mb-0 h1">POS Cafe - Kitchen</span>
                            </div>
                            
                            <div class="navbar-nav ms-auto">
                                <div class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle"></i> Kitchen Staff
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