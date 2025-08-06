<?php
require_once 'require/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>POS Cafe - Order System Test</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <div class='row'>
        <div class='col-12'>
            <h1 class='text-center mb-4'>
                <i class='fas fa-coffee'></i> POS Cafe - Order System Test
            </h1>
            
            <div class='alert alert-info'>
                <h4><i class='fas fa-info-circle'></i> Enhanced Order Features</h4>
                <p>This page tests the new order functionality that has been implemented:</p>
                <ul>
                    <li><strong>Add Products to Existing Orders:</strong> Users can add new products to orders that are still pending</li>
                    <li><strong>Quantity Management:</strong> Users can update quantities of existing order items</li>
                    <li><strong>Individual Product Status:</strong> Kitchen can track each product's status separately (ordered, preparing, ready, served)</li>
                    <li><strong>Real-time Status Updates:</strong> Overall order status updates based on individual product statuses</li>
                    <li><strong>Automatic Total Recalculation:</strong> Order totals update automatically when items are modified</li>
                </ul>
            </div>

            <div class='row'>
                <div class='col-md-6'>
                    <div class='card'>
                        <div class='card-header'>
                            <h5><i class='fas fa-database'></i> Database Status</h5>
                        </div>
                        <div class='card-body'>";

// Test database connection and table structure
$tables_to_check = ['orders', 'order_items', 'products', 'tables', 'user', 'notifications'];
$table_status = [];

foreach ($tables_to_check as $table) {
    $result = $mysqli->query("SHOW TABLES LIKE '$table'");
    if ($result->num_rows > 0) {
        $table_status[$table] = "✓ Exists";
    } else {
        $table_status[$table] = "✗ Missing";
    }
}

foreach ($table_status as $table => $status) {
    $color = strpos($status, '✓') !== false ? 'text-success' : 'text-danger';
    echo "<p class='$color'><strong>$table:</strong> $status</p>";
}

// Check order_items status enum
$result = $mysqli->query("SHOW COLUMNS FROM order_items WHERE Field = 'status'");
if ($result->num_rows > 0) {
    $column_info = $result->fetch_assoc();
    $status_values = $column_info['Type'];
    echo "<p class='text-info'><strong>order_items.status:</strong> $status_values</p>";
}

// Check orders kitchen_status enum
$result = $mysqli->query("SHOW COLUMNS FROM orders WHERE Field = 'kitchen_status'");
if ($result->num_rows > 0) {
    $column_info = $result->fetch_assoc();
    $kitchen_status_values = $column_info['Type'];
    echo "<p class='text-info'><strong>orders.kitchen_status:</strong> $kitchen_status_values</p>";
}

echo "        </div>
                    </div>
                </div>
                
                <div class='col-md-6'>
                    <div class='card'>
                        <div class='card-header'>
                            <h5><i class='fas fa-list'></i> Sample Data</h5>
                        </div>
                        <div class='card-body'>";

// Show sample data counts
$tables_with_counts = ['orders', 'order_items', 'products', 'tables', 'user'];
foreach ($tables_with_counts as $table) {
    $result = $mysqli->query("SELECT COUNT(*) as count FROM $table");
    if ($result) {
        $count = $result->fetch_assoc()['count'];
        echo "<p><strong>$table:</strong> $count records</p>";
    }
}

echo "        </div>
                    </div>
                </div>
            </div>

            <div class='row mt-4'>
                <div class='col-12'>
                    <div class='card'>
                        <div class='card-header'>
                            <h5><i class='fas fa-cogs'></i> System Features</h5>
                        </div>
                        <div class='card-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <h6><i class='fas fa-user'></i> User (Waiter) Features:</h6>
                                    <ul>
                                        <li>View all tables and their status</li>
                                        <li>Create new orders from menu</li>
                                        <li>Add products to existing orders</li>
                                        <li>Update quantities of order items</li>
                                        <li>View individual product status</li>
                                        <li>Process payments when food is ready</li>
                                        <li>Receive real-time notifications</li>
                                    </ul>
                                </div>
                                <div class='col-md-6'>
                                    <h6><i class='fas fa-utensils'></i> Kitchen Features:</h6>
                                    <ul>
                                        <li>View pending orders</li>
                                        <li>Update individual product status</li>
                                        <li>Track preparation progress</li>
                                        <li>Mark products as ready</li>
                                        <li>Send notifications to waiters</li>
                                        <li>Manage order modifications</li>
                                        <li>Automatic status synchronization</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class='row mt-4'>
                <div class='col-12'>
                    <div class='card'>
                        <div class='card-header'>
                            <h5><i class='fas fa-route'></i> Status Flow</h5>
                        </div>
                        <div class='card-body'>
                            <div class='row'>
                                <div class='col-md-6'>
                                    <h6>Product Status Flow:</h6>
                                    <div class='d-flex align-items-center mb-2'>
                                        <span class='badge bg-warning me-2'>ordered</span>
                                        <i class='fas fa-arrow-right me-2'></i>
                                        <span class='badge bg-info me-2'>preparing</span>
                                        <i class='fas fa-arrow-right me-2'></i>
                                        <span class='badge bg-primary me-2'>ready</span>
                                        <i class='fas fa-arrow-right me-2'></i>
                                        <span class='badge bg-success'>served</span>
                                    </div>
                                </div>
                                <div class='col-md-6'>
                                    <h6>Order Status Flow:</h6>
                                    <div class='d-flex align-items-center mb-2'>
                                        <span class='badge bg-warning me-2'>pending</span>
                                        <i class='fas fa-arrow-right me-2'></i>
                                        <span class='badge bg-info me-2'>preparing</span>
                                        <i class='fas fa-arrow-right me-2'></i>
                                        <span class='badge bg-primary me-2'>ready</span>
                                        <i class='fas fa-arrow-right me-2'></i>
                                        <span class='badge bg-success'>completed</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class='row mt-4'>
                <div class='col-12 text-center'>
                    <a href='user/' class='btn btn-primary btn-lg me-3'>
                        <i class='fas fa-user'></i> Go to User Dashboard
                    </a>
                    <a href='kitchen/' class='btn btn-success btn-lg me-3'>
                        <i class='fas fa-utensils'></i> Go to Kitchen Dashboard
                    </a>
                    <a href='cashier/' class='btn btn-warning btn-lg me-3'>
                        <i class='fas fa-cash-register'></i> Go to Cashier Dashboard
                    </a>
                    <a href='admin/' class='btn btn-secondary btn-lg'>
                        <i class='fas fa-cog'></i> Go to Admin Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?> 