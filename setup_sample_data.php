<?php
require_once 'require/db.php';

echo "<!DOCTYPE html>
<html lang='en'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>POS Cafe - Setup Sample Data</title>
    <link href='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css' rel='stylesheet'>
    <link href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css' rel='stylesheet'>
</head>
<body>
<div class='container mt-4'>
    <h1 class='text-center mb-4'>
        <i class='fas fa-database'></i> POS Cafe - Setup Sample Data
    </h1>";

// Check if data already exists
$result = $mysqli->query("SELECT COUNT(*) as count FROM user");
$user_count = $result->fetch_assoc()['count'];

if ($user_count > 0) {
    echo "<div class='alert alert-info'>
        <h4><i class='fas fa-info-circle'></i> Sample Data Already Exists</h4>
        <p>The system already has data. You can proceed to use the system.</p>
        <a href='test_order_system.php' class='btn btn-primary'>Go to Test Page</a>
        <a href='user/' class='btn btn-success'>Go to User Dashboard</a>
    </div>";
} else {
    echo "<div class='alert alert-warning'>
        <h4><i class='fas fa-exclamation-triangle'></i> No Sample Data Found</h4>
        <p>Click the button below to create sample data for testing.</p>
        <form method='post'>
            <button type='submit' name='create_sample_data' class='btn btn-warning btn-lg'>
                <i class='fas fa-plus'></i> Create Sample Data
            </button>
        </form>
    </div>";
}

if (isset($_POST['create_sample_data'])) {
    echo "<div class='card'>
        <div class='card-header'>
            <h5><i class='fas fa-cogs'></i> Creating Sample Data...</h5>
        </div>
        <div class='card-body'>";
    
    // Create sample users
    $users = [
        ['user_name' => 'admin', 'password' => password_hash('admin123', PASSWORD_DEFAULT), 'name' => 'Admin User', 'role' => 'admin'],
        ['user_name' => 'waiter1', 'password' => password_hash('waiter123', PASSWORD_DEFAULT), 'name' => 'John Waiter', 'role' => 'waiter'],
        ['user_name' => 'kitchen1', 'password' => password_hash('kitchen123', PASSWORD_DEFAULT), 'name' => 'Chef Mike', 'role' => 'kitchen'],
        ['user_name' => 'cashier1', 'password' => password_hash('cashier123', PASSWORD_DEFAULT), 'name' => 'Sarah Cashier', 'role' => 'cashier']
    ];
    
    foreach ($users as $user) {
        $stmt = $mysqli->prepare("INSERT INTO user (user_name, password, name, role) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $user['user_name'], $user['password'], $user['name'], $user['role']);
        if ($stmt->execute()) {
            echo "<p class='text-success'>✓ Created user: {$user['name']} ({$user['role']})</p>";
        } else {
            echo "<p class='text-danger'>✗ Error creating user {$user['name']}: " . $mysqli->error . "</p>";
        }
    }
    
    // Create sample tables
    $tables = ['Table 1', 'Table 2', 'Table 3', 'Table 4', 'Table 5'];
    foreach ($tables as $table_name) {
        $stmt = $mysqli->prepare("INSERT INTO tables (name, status) VALUES (?, 'available')");
        $stmt->bind_param("s", $table_name);
        if ($stmt->execute()) {
            echo "<p class='text-success'>✓ Created table: $table_name</p>";
        } else {
            echo "<p class='text-danger'>✗ Error creating table $table_name: " . $mysqli->error . "</p>";
        }
    }
    
    // Create sample categories
    $main_categories = ['Beverages', 'Food', 'Desserts'];
    foreach ($main_categories as $main_cat) {
        $stmt = $mysqli->prepare("INSERT INTO main_categories (name) VALUES (?)");
        $stmt->bind_param("s", $main_cat);
        if ($stmt->execute()) {
            $main_cat_id = $mysqli->insert_id;
            echo "<p class='text-success'>✓ Created main category: $main_cat</p>";
            
            // Create sub-categories
            if ($main_cat == 'Beverages') {
                $sub_cats = ['Hot Drinks', 'Cold Drinks', 'Coffee'];
            } elseif ($main_cat == 'Food') {
                $sub_cats = ['Main Course', 'Appetizers', 'Sandwiches'];
            } else {
                $sub_cats = ['Cakes', 'Ice Cream', 'Pastries'];
            }
            
            foreach ($sub_cats as $sub_cat) {
                $stmt = $mysqli->prepare("INSERT INTO second_categories (name, main_categories_id) VALUES (?, ?)");
                $stmt->bind_param("si", $sub_cat, $main_cat_id);
                if ($stmt->execute()) {
                    $sub_cat_id = $mysqli->insert_id;
                    echo "<p class='text-success'>  ✓ Created sub-category: $sub_cat</p>";
                    
                    // Create products
                    if ($sub_cat == 'Coffee') {
                        $products = [
                            ['name' => 'Espresso', 'price' => 3.50],
                            ['name' => 'Cappuccino', 'price' => 4.50],
                            ['name' => 'Latte', 'price' => 4.00]
                        ];
                    } elseif ($sub_cat == 'Main Course') {
                        $products = [
                            ['name' => 'Grilled Chicken', 'price' => 12.99],
                            ['name' => 'Beef Steak', 'price' => 18.99],
                            ['name' => 'Fish & Chips', 'price' => 14.99]
                        ];
                    } elseif ($sub_cat == 'Cakes') {
                        $products = [
                            ['name' => 'Chocolate Cake', 'price' => 6.99],
                            ['name' => 'Cheesecake', 'price' => 7.99]
                        ];
                    } else {
                        $products = [
                            ['name' => 'Sample Product', 'price' => 9.99]
                        ];
                    }
                    
                    foreach ($products as $product) {
                        $stmt = $mysqli->prepare("INSERT INTO products (name, original_price, second_categories_id, status) VALUES (?, ?, ?, 'active')");
                        $stmt->bind_param("sdi", $product['name'], $product['price'], $sub_cat_id);
                        if ($stmt->execute()) {
                            echo "<p class='text-success'>    ✓ Created product: {$product['name']} - \${$product['price']}</p>";
                        } else {
                            echo "<p class='text-danger'>    ✗ Error creating product {$product['name']}: " . $mysqli->error . "</p>";
                        }
                    }
                }
            }
        }
    }
    
    // Create payment types
    $payment_types = ['Cash', 'Credit Card', 'Debit Card', 'Mobile Payment'];
    foreach ($payment_types as $payment_type) {
        $stmt = $mysqli->prepare("INSERT INTO payment_type (name) VALUES (?)");
        $stmt->bind_param("s", $payment_type);
        if ($stmt->execute()) {
            echo "<p class='text-success'>✓ Created payment type: $payment_type</p>";
        }
    }
    
    echo "</div>
    </div>
    
    <div class='alert alert-success mt-3'>
        <h4><i class='fas fa-check-circle'></i> Sample Data Created Successfully!</h4>
        <p><strong>Default Login Credentials:</strong></p>
        <ul>
            <li><strong>Admin:</strong> admin / admin123</li>
            <li><strong>Waiter:</strong> waiter1 / waiter123</li>
            <li><strong>Kitchen:</strong> kitchen1 / kitchen123</li>
            <li><strong>Cashier:</strong> cashier1 / cashier123</li>
        </ul>
        <div class='mt-3'>
            <a href='test_order_system.php' class='btn btn-primary'>Go to Test Page</a>
            <a href='user/' class='btn btn-success'>Go to User Dashboard</a>
        </div>
    </div>";
}

echo "</div>
<script src='https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js'></script>
</body>
</html>";
?> 