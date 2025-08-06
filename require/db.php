<?php
$host = 'localhost';
$username = 'root';
$password = '';
$database = 'pos_cafe';

// First connect without specifying database
$mysqli = new mysqli($host, $username, $password);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Create database if not exists
$mysqli->query("CREATE DATABASE IF NOT EXISTS $database");

// Select the database
$mysqli->select_db($database);

// Define all tables
$tables = [
    // main_categories
    "CREATE TABLE IF NOT EXISTS main_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB",
    // second_categories
    "CREATE TABLE IF NOT EXISTS second_categories (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        main_categories_id INT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (main_categories_id) REFERENCES main_categories(id) ON DELETE CASCADE
    ) ENGINE=InnoDB",
    // products
    "CREATE TABLE IF NOT EXISTS products (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255),
        original_price DECIMAL(10,2),
        second_categories_id INT,
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
        kitchen_status ENUM('pending', 'accepted', 'rejected', 'preparing', 'ready', 'cancelled') DEFAULT 'pending',
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
        status ENUM('ordered', 'preparing', 'ready', 'served', 'cancelled') DEFAULT 'ordered',
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

// Create tables
foreach ($tables as $query) {
    if (!$mysqli->query($query)) {
        echo 'Table creation failed: ' . $mysqli->error . "<br>";
    }
}

// Database update functions
function updateDatabaseSchema($mysqli) {
    $updates = [];
    
    // Check and update orders table kitchen_status column
    $result = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'kitchen_status'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE orders ADD COLUMN kitchen_status ENUM('pending', 'accepted', 'rejected', 'preparing', 'ready', 'cancelled') DEFAULT 'pending' AFTER status";
        if ($mysqli->query($sql)) {
            $updates[] = "✓ Added kitchen_status column to orders table";
        } else {
            $updates[] = "✗ Error adding kitchen_status column: " . $mysqli->error;
        }
    } else {
        // Check if 'cancelled' status exists
        $result = $mysqli->query("SHOW COLUMNS FROM orders WHERE Field = 'kitchen_status' AND Type LIKE '%cancelled%'");
        if ($result->num_rows == 0) {
            $sql = "ALTER TABLE orders MODIFY COLUMN kitchen_status ENUM('pending', 'accepted', 'rejected', 'preparing', 'ready', 'cancelled') DEFAULT 'pending'";
            if ($mysqli->query($sql)) {
                $updates[] = "✓ Updated kitchen_status column to include 'cancelled' status";
            } else {
                $updates[] = "✗ Error updating kitchen_status column: " . $mysqli->error;
            }
        }
    }
    
    // Check and update orders table kitchen_notes column
    $result = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'kitchen_notes'");
    if ($result->num_rows == 0) {
        $sql = "ALTER TABLE orders ADD COLUMN kitchen_notes TEXT AFTER kitchen_status";
        if ($mysqli->query($sql)) {
            $updates[] = "✓ Added kitchen_notes column to orders table";
        } else {
            $updates[] = "✗ Error adding kitchen_notes column: " . $mysqli->error;
        }
    }
    
    // Update existing orders to have 'pending' kitchen_status
    $sql = "UPDATE orders SET kitchen_status = 'pending' WHERE kitchen_status IS NULL OR kitchen_status = ''";
    if ($mysqli->query($sql)) {
        $affected_rows = $mysqli->affected_rows;
        if ($affected_rows > 0) {
            $updates[] = "✓ Updated $affected_rows existing orders with 'pending' kitchen status";
        }
    }
    
    // Check and update order_items table status column
    $result = $mysqli->query("SHOW COLUMNS FROM order_items LIKE 'status'");
    if ($result->num_rows > 0) {
        $column_info = $result->fetch_assoc();
        $current_type = $column_info['Type'];
        
        // Check if new status values are missing
        if (strpos($current_type, 'preparing') === false || strpos($current_type, 'ready') === false) {
            $sql = "ALTER TABLE order_items MODIFY COLUMN status ENUM('ordered', 'preparing', 'ready', 'served', 'cancelled') DEFAULT 'ordered'";
            if ($mysqli->query($sql)) {
                $updates[] = "✓ Updated order_items status column to include new values (ordered, preparing, ready, served, cancelled)";
            } else {
                $updates[] = "✗ Error updating order_items status column: " . $mysqli->error;
            }
        }
        
        // Update any existing 'accepted' status to 'ordered'
        $sql = "UPDATE order_items SET status = 'ordered' WHERE status = 'accepted'";
        if ($mysqli->query($sql)) {
            $affected_rows = $mysqli->affected_rows;
            if ($affected_rows > 0) {
                $updates[] = "✓ Updated $affected_rows order items from 'accepted' to 'ordered' status";
            }
        }
    }
    
    return $updates;
}

// Run database updates
$updates = updateDatabaseSchema($mysqli);

// If there are updates, log them (optional)
if (!empty($updates)) {
    error_log("Database schema updates: " . implode(", ", $updates));
}
?>
