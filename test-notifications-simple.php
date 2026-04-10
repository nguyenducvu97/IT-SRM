<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Test Notification Functions</h2>";
    
    require_once __DIR__ . '/../lib/NotificationHelper.php';
    $notificationHelper = new NotificationHelper($db);
    
    // Test notifyUser
    echo "<h3>Test notifyUser:</h3>";
    $user_title = "Test Yêu yêu #81";
    $user_message = "John Smith yêu yêu yêu yêu: éaew";
    
    $user_result = $notificationHelper->notifyUser(4, $user_title, $user_message, 'success', 81, 'request');
    echo "<p>notifyUser result: " . ($user_result ? "SUCCESS" : "FAILED") . "</p>";
    
    // Test notifyAdmins
    echo "<h3>Test notifyAdmins:</h3>";
    $admin_title = "Test Yêu yêu #81";
    $admin_message = "John Smith yêu yêu yêu yêu: éaew";
    
    $admin_result = $notificationHelper->notifyAdmins($admin_title, $admin_message, 'success', 81, 'request');
    echo "<p>notifyAdmins result: " . ($admin_result ? "SUCCESS" : "FAILED") . "</p>";
    
    // Check recent notifications
    echo "<h3>Recent Notifications:</h3>";
    $notification_query = "SELECT n.id, n.title, n.message, n.type, n.created_at, u.full_name as user_name
                           FROM notifications n
                           LEFT JOIN users u ON n.user_id = u.id
                           WHERE n.service_request_id = 81
                           ORDER BY n.created_at DESC
                           LIMIT 5";
    
    $notification_stmt = $db->prepare($notification_query);
    $notification_stmt->execute();
    $notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>User</th><th>Created</th></tr>";
        foreach ($notifications as $notification) {
            echo "<tr>";
            echo "<td>{$notification['id']}</td>";
            echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
            echo "<td>" . htmlspecialchars($notification['message']) . "</td>";
            echo "<td>{$notification['type']}</td>";
            echo "<td>{$notification['user_name']}</td>";
            echo "<td>{$notification['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No notifications found for request #81</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li><a href='index.html' target='_blank'>Login as John Smith (staff)</a></li>";
    echo "<li>Navigate to request #81</li>";
    echo "<li>Click 'Giã Quyãt' button</li>";
    echo "<li>Fill and submit resolution form</li>";
    echo "<li>Check notifications for user (Nguyên Duc Vu) and admin (System Administrator)</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
