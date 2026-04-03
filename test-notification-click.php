<?php
// Test notification click functionality
require_once 'config/database.php';

echo "<h2>Test Notification Click Functionality</h2>";

try {
    $db = getDatabaseConnection();
    
    // Get recent notifications for admin (user_id = 1)
    $notifCheck = $db->prepare("SELECT id, user_id, title, message, type, related_id, related_type, is_read, created_at FROM notifications WHERE user_id = 1 ORDER BY created_at DESC LIMIT 5");
    $notifCheck->execute();
    $notifications = $notifCheck->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Recent Admin Notifications:</h3>";
    
    if (empty($notifications)) {
        echo "<p>No notifications found for admin user</p>";
    } else {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Related ID</th><th>Related Type</th><th>Read</th><th>Created</th><th>Action</th></tr>";
        
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>" . substr($notif['title'], 0, 30) . "...</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['related_id']}</td>";
            echo "<td><strong>{$notif['related_type']}</strong></td>";
            echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$notif['created_at']}</td>";
            
            // Test navigation URL
            $navUrl = '';
            switch ($notif['related_type']) {
                case 'request':
                case 'service_request':
                    $navUrl = "request-detail.html?id={$notif['related_id']}";
                    break;
                case 'support_request':
                    $navUrl = 'support-requests.html';
                    break;
                case 'reject_request':
                    $navUrl = 'reject-requests.html';
                    break;
                default:
                    $navUrl = 'index.html';
            }
            
            echo "<td><a href='{$navUrl}' target='_blank'>Test Link</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>Expected Behavior:</h3>";
        echo "<ul>";
        echo "<li>When admin clicks on notification with related_type 'service_request', should navigate to request-detail.html?id=[related_id]</li>";
        echo "<li>Notification should be marked as read</li>";
        echo "<li>Admin should see the service request details</li>";
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
