<?php
// Database credentials
$host = 'localhost';
$user = 'root'; // Change if needed
$password = '';    // Change if needed
$dbname = 'POS_Cafe';

// Connect to MySQL server
$mysqli = new mysqli($host, $user, $password);
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// Create database if not exists
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$mysqli->query($sql)) {
    die('Database creation failed: ' . $mysqli->error);
}

// Select the database
$mysqli->select_db($dbname);

// Create tables
$tables = [
    // main_categories
    "CREATE TABLE IF NOT EXISTS main_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    // second_categories
    "CREATE TABLE IF NOT EXISTS second_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        main_categories_id INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (main_categories_id) REFERENCES main_categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
    // products
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        second_categories_id INT,
        original_price DECIMAL(10,2),
        status ENUM('active', 'inactive') DEFAULT 'active',
        images VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (second_categories_id) REFERENCES second_categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
    // discounts
    "CREATE TABLE IF NOT EXISTS discounts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        percent DECIMAL(5,2),
        product_id INT,
        start_date DATE,
        end_date DATE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
    // tables
    "CREATE TABLE IF NOT EXISTS tables (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        status ENUM('available', 'occupied', 'reserved') DEFAULT 'available',
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    // user
    "CREATE TABLE IF NOT EXISTS user (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_name VARCHAR(255) NOT NULL,
        password VARCHAR(255) NOT NULL,
        name VARCHAR(255),
        status ENUM('active', 'inactive') DEFAULT 'active',
        image VARCHAR(255) DEFAULT NULL,
        role ENUM('admin', 'cashier', 'kitchen', 'waiter'),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    // orders
    "CREATE TABLE IF NOT EXISTS orders (
        id INT AUTO_INCREMENT PRIMARY KEY,
        table_id INT,
        user_id INT,
        order_date DATE,
        status ENUM('pending', 'completed', 'cancelled') DEFAULT 'pending',
        kitchen_status ENUM('pending', 'accepted', 'rejected', 'preparing', 'ready') DEFAULT 'pending',
        kitchen_notes TEXT,
        total_amount DECIMAL(10,2),
        pyment_method_id INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (table_id) REFERENCES tables(id),
        FOREIGN KEY (user_id) REFERENCES user(id)
    ) ENGINE=InnoDB",
    // order_items
    "CREATE TABLE IF NOT EXISTS order_items (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        product_id INT,
        quantity INT,
        status ENUM('ordered', 'served', 'cancelled') DEFAULT 'ordered',
        unit_price DECIMAL(10,2),
        discount_applied DECIMAL(10,2),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (product_id) REFERENCES products(id)
    ) ENGINE=InnoDB",
    // payment_type
    "CREATE TABLE IF NOT EXISTS payment_type (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    // payment
    "CREATE TABLE IF NOT EXISTS payment (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        payment_type_id INT,
        payment_date DATE,
        transaction_code VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (payment_type_id) REFERENCES payment_type(id)
    ) ENGINE=InnoDB",
    // notifications
    "CREATE TABLE IF NOT EXISTS notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        user_id INT,
        message TEXT NOT NULL,
        type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES user(id)
    ) ENGINE=InnoDB"
];

foreach ($tables as $query) {
    if (!$mysqli->query($query)) {
        echo 'Table creation failed: ' . $mysqli->error . "<br>";
    }
}
