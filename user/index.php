<?php
require_once '../auth/isLogin.php';
require_once '../require/db.php';
// session_start(); // Removed duplicate session_start()

if (!isset($_SESSION['waiter_id'])) {
    header("Location: login.php");
    exit;
}

$waiter_id = $_SESSION['waiter_id'];

// Fetch tables with their current status
$tables = $mysqli->query("SELECT * FROM tables ORDER BY name ASC")->fetch_all(MYSQLI_ASSOC);

// Get unread notifications count for real-time updates
$stmt = $mysqli->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->bind_param("i", $waiter_id);
$stmt->execute();
$result = $stmt->get_result();
$unread_notifications = $result->fetch_assoc()['count'];

include 'layout/header.php';
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Tables Management</h4>
            </div>
            <div class="card-body">
                    <div class="row">
                        <?php foreach ($tables as $table): ?>
                        <div class="col-md-3 mb-3">
                            <div class="card text-center 
                                <?php 
                                    if ($table['status'] == 'occupied') echo 'bg-danger';
                                    elseif ($table['status'] == 'reserved') echo 'bg-warning';
                                    else echo 'bg-success';
                                ?>">
                                <div class="card-body">
                                    <h5 class="card-title text-white"><?= htmlspecialchars($table['name']) ?></h5>
                                    <p class="card-text text-white"><?= ucfirst($table['status']) ?></p>
                                    <?php if ($table['status'] == 'available'): ?>
                                        <a href="menu.php?table_id=<?= $table['id'] ?>" class="btn btn-light">New Order</a>
                                    <?php else: 
                                        $order_result = $mysqli->query("SELECT id, kitchen_status FROM orders WHERE table_id = {$table['id']} AND status = 'pending'");
                                        $order = $order_result->fetch_assoc();
                                        if ($order):
                                    ?>
                                        <a href="order_details.php?order_id=<?= $order['id'] ?>" class="btn btn-light">
                                            View Order 
                                            <span class="badge bg-<?= 
                                                $order['kitchen_status'] == 'pending' ? 'warning' : 
                                                ($order['kitchen_status'] == 'accepted' ? 'success' : 
                                                ($order['kitchen_status'] == 'rejected' ? 'danger' : 
                                                ($order['kitchen_status'] == 'preparing' ? 'info' : 
                                                ($order['kitchen_status'] == 'ready' ? 'primary' : 'secondary'))))
                                            ?>">
                                                <?= ucfirst($order['kitchen_status']) ?>
                                            </span>
                                        </a>
                                    <?php endif; endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Real-time Updates Script -->
<script>
let lastNotificationCount = <?= $unread_notifications ?>;
let refreshInterval;

// Function to check for new notifications and update dashboard
function checkForUpdates() {
    fetch('check_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.unread_count > lastNotificationCount) {
                // New notifications received
                lastNotificationCount = data.unread_count;
                
                // Show notification
                if (data.new_notifications > 0) {
                    showNotification(`${data.new_notifications} new notification(s) received!`, 'info');
                }
                
                // Refresh the page to show updated data
                setTimeout(() => {
                    location.reload();
                }, 2000);
            }
        })
        .catch(error => {
            console.error('Error checking for updates:', error);
        });
}

// Function to show browser notification
function showNotification(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
    notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    notification.innerHTML = `
        <i class="fas fa-bell"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(notification);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (notification.parentNode) {
            notification.remove();
        }
    }, 5000);
}

// Start real-time checking when page loads
document.addEventListener('DOMContentLoaded', function() {
    // Check for updates every 10 seconds
    refreshInterval = setInterval(checkForUpdates, 10000);
    
    // Also check when user becomes active (tab focus)
    document.addEventListener('visibilitychange', function() {
        if (!document.hidden) {
            checkForUpdates();
        }
    });
});

// Clean up interval when page unloads
window.addEventListener('beforeunload', function() {
    if (refreshInterval) {
        clearInterval(refreshInterval);
    }
});
</script>

<?php include 'layout/footer.php'; ?>
