<?php
require_once("../auth/isLogin.php");
require '../require/db.php';
require '../require/common_function.php';

require './layouts/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Admin Dashboard</h4>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Users</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM user");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-users fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Products</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM products");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-box fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Orders</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM orders");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-shopping-cart fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Tables</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM tables");
                                            $stmt->execute();
                                            $result = $stmt->get_result();
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-table fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Payment report range filter (today, week, month, year)
$range = isset($_GET['range']) ? $_GET['range'] : 'today';
$allowedRanges = ['today', 'week', 'month', 'year'];
if (!in_array($range, $allowedRanges, true)) {
    $range = 'today';
}

switch ($range) {
    case 'week':
        $dateFilter = "YEARWEEK(order_date, 1) = YEARWEEK(CURDATE(), 1)"; // ISO week (Mon-Sun)
        $rangeLabel = 'This Week';
        break;
    case 'month':
        $dateFilter = "MONTH(order_date) = MONTH(CURDATE()) AND YEAR(order_date) = YEAR(CURDATE())";
        $rangeLabel = 'This Month';
        break;
    case 'year':
        $dateFilter = "YEAR(order_date) = YEAR(CURDATE())";
        $rangeLabel = 'This Year';
        break;
    case 'today':
    default:
        $dateFilter = "DATE(order_date) = CURDATE()";
        $rangeLabel = 'Today';
        break;
}
?>

<!-- Payment Report (range selectable) mirroring cashier dashboard metrics -->
<div class="row mt-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Payment Report (<?= htmlspecialchars($rangeLabel) ?>)</h4>
                <form method="get" class="d-flex align-items-center">
                    <label for="range" class="me-2 mb-0">Range</label>
                    <select id="range" name="range" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="today" <?= $range === 'today' ? 'selected' : '' ?>>Today</option>
                        <option value="week" <?= $range === 'week' ? 'selected' : '' ?>>This Week</option>
                        <option value="month" <?= $range === 'month' ? 'selected' : '' ?>>This Month</option>
                        <option value="year" <?= $range === 'year' ? 'selected' : '' ?>>This Year</option>
                    </select>
                    <noscript>
                        <button type="submit" class="btn btn-sm btn-secondary ms-2">Apply</button>
                    </noscript>
                </form>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Total Orders (range) -->
                    <div class="col-md-3 mb-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Total Orders</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE $dateFilter");
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clipboard-list fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Paid Orders (range) -->
                    <div class="col-md-3 mb-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Paid Orders</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE $dateFilter AND status = 'completed'");
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-check-circle fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Payments (range) -->
                    <div class="col-md-3 mb-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Pending Payments</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $result = $mysqli->query("SELECT COUNT(*) as count FROM orders WHERE $dateFilter AND status = 'pending' AND kitchen_status = 'ready'");
                                            echo $result->fetch_assoc()['count'];
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-clock fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Revenue (range) -->
                    <div class="col-md-3 mb-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h5 class="card-title">Revenue</h5>
                                        <h3 class="mb-0">
                                            <?php
                                            $result = $mysqli->query("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE $dateFilter AND status = 'completed'");
                                            $total = $result->fetch_assoc()['total'] ?? 0;
                                            echo '$' . number_format((float)$total, 2);
                                            ?>
                                        </h3>
                                    </div>
                                    <div class="align-self-center">
                                        <i class="fas fa-dollar-sign fa-2x"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
require './layouts/footer.php';
?>