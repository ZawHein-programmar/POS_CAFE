<?php
require_once 'require/db.php';

echo "<h2>Database Update Script</h2>";

// Check if kitchen_status column exists
$result = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'kitchen_status'");
if ($result->num_rows == 0) {
    echo "<p>Adding kitchen_status column...</p>";
    $sql = "ALTER TABLE orders ADD COLUMN kitchen_status ENUM('pending', 'accepted', 'rejected', 'preparing', 'ready', 'cancelled') DEFAULT 'pending' AFTER status";
    if ($mysqli->query($sql)) {
        echo "<p style='color: green;'>✓ kitchen_status column added successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding kitchen_status column: " . $mysqli->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ kitchen_status column already exists.</p>";
    // Update existing column to include 'cancelled' status
    $result = $mysqli->query("SHOW COLUMNS FROM orders WHERE Field = 'kitchen_status' AND Type LIKE '%cancelled%'");
    if ($result->num_rows == 0) {
        echo "<p>Updating kitchen_status column to include 'cancelled' status...</p>";
        $sql = "ALTER TABLE orders MODIFY COLUMN kitchen_status ENUM('pending', 'accepted', 'rejected', 'preparing', 'ready', 'cancelled') DEFAULT 'pending'";
        if ($mysqli->query($sql)) {
            echo "<p style='color: green;'>✓ kitchen_status column updated successfully!</p>";
        } else {
            echo "<p style='color: red;'>✗ Error updating kitchen_status column: " . $mysqli->error . "</p>";
        }
    } else {
        echo "<p style='color: blue;'>ℹ kitchen_status column already includes 'cancelled' status.</p>";
    }
}

// Check if kitchen_notes column exists
$result = $mysqli->query("SHOW COLUMNS FROM orders LIKE 'kitchen_notes'");
if ($result->num_rows == 0) {
    echo "<p>Adding kitchen_notes column...</p>";
    $sql = "ALTER TABLE orders ADD COLUMN kitchen_notes TEXT AFTER kitchen_status";
    if ($mysqli->query($sql)) {
        echo "<p style='color: green;'>✓ kitchen_notes column added successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Error adding kitchen_notes column: " . $mysqli->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ kitchen_notes column already exists.</p>";
}

// Update existing orders to have 'pending' kitchen_status
echo "<p>Updating existing orders...</p>";
$sql = "UPDATE orders SET kitchen_status = 'pending' WHERE kitchen_status IS NULL OR kitchen_status = ''";
if ($mysqli->query($sql)) {
    $affected_rows = $mysqli->affected_rows;
    echo "<p style='color: green;'>✓ Updated $affected_rows existing orders with 'pending' kitchen status.</p>";
} else {
    echo "<p style='color: red;'>✗ Error updating existing orders: " . $mysqli->error . "</p>";
}

// Update order_items status enum to include 'accepted'
echo "<p>Updating order_items status...</p>";
$result = $mysqli->query("SHOW COLUMNS FROM order_items WHERE Field = 'status' AND Type LIKE '%accepted%'");
if ($result->num_rows == 0) {
    $sql = "ALTER TABLE order_items MODIFY COLUMN status ENUM('ordered', 'accepted', 'served', 'cancelled') DEFAULT 'ordered'";
    if ($mysqli->query($sql)) {
        echo "<p style='color: green;'>✓ Updated order_items status enum to include 'accepted'.</p>";
    } else {
        echo "<p style='color: red;'>✗ Error updating order_items status: " . $mysqli->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ order_items status already includes 'accepted'.</p>";
}

// Check if notifications table exists
echo "<p>Checking notifications table...</p>";
$result = $mysqli->query("SHOW TABLES LIKE 'notifications'");
if ($result->num_rows == 0) {
    $sql = "CREATE TABLE notifications (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT,
        user_id INT,
        message TEXT NOT NULL,
        type ENUM('info', 'warning', 'success', 'danger') DEFAULT 'info',
        is_read BOOLEAN DEFAULT FALSE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES user(id)
    ) ENGINE=InnoDB";
    if ($mysqli->query($sql)) {
        echo "<p style='color: green;'>✓ Notifications table created successfully!</p>";
    } else {
        echo "<p style='color: red;'>✗ Error creating notifications table: " . $mysqli->error . "</p>";
    }
} else {
    echo "<p style='color: blue;'>ℹ Notifications table already exists.</p>";
}

echo "<h3>Database Update Complete!</h3>";
echo "<p><a href='kitchen/login.php'>Go to Kitchen Login</a></p>";
echo "<p><a href='user/login.php'>Go to User Login</a></p>";
?> 