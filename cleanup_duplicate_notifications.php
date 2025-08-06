<?php
require_once 'require/db.php';

echo "<h2>Duplicate Notifications Cleanup Script</h2>";

// Remove duplicate notifications created within 5 minutes of each other
$sql = "
    DELETE n1 FROM notifications n1
    INNER JOIN notifications n2 
    WHERE n1.id > n2.id 
    AND n1.order_id = n2.order_id 
    AND n1.user_id = n2.user_id 
    AND n1.message = n2.message 
    AND n1.type = n2.type
    AND ABS(TIMESTAMPDIFF(MINUTE, n1.created_at, n2.created_at)) < 5
";

if ($mysqli->query($sql)) {
    $affected_rows = $mysqli->affected_rows;
    echo "<p style='color: green;'>✓ Removed $affected_rows duplicate notifications.</p>";
} else {
    echo "<p style='color: red;'>✗ Error removing duplicates: " . $mysqli->error . "</p>";
}

// Show current notification count
$result = $mysqli->query("SELECT COUNT(*) as count FROM notifications");
$total_notifications = $result->fetch_assoc()['count'];

echo "<p style='color: blue;'>ℹ Total notifications remaining: $total_notifications</p>";

// Show notifications by type
$result = $mysqli->query("
    SELECT type, COUNT(*) as count 
    FROM notifications 
    GROUP BY type 
    ORDER BY count DESC
");

echo "<h3>Notifications by Type:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 300px;'>";
echo "<tr><th>Type</th><th>Count</th></tr>";

while ($row = $result->fetch_assoc()) {
    $color = $row['type'] == 'danger' ? 'red' : 
            ($row['type'] == 'warning' ? 'orange' : 
            ($row['type'] == 'success' ? 'green' : 'blue'));
    
    echo "<tr>";
    echo "<td style='color: $color; font-weight: bold;'>" . ucfirst($row['type']) . "</td>";
    echo "<td>" . $row['count'] . "</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Cleanup Complete!</h3>";
echo "<p><a href='kitchen/login.php'>Go to Kitchen Login</a></p>";
echo "<p><a href='user/login.php'>Go to User Login</a></p>";
?> 