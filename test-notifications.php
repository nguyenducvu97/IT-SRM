<?php
// Test script to check notification system
require_once 'config/database.php';
require_once 'lib/NotificationHelper.php';

echo "<h1>Test Notification System</h1>";

try {
    $db = getDatabaseConnection();
    $notificationHelper = new NotificationHelper();
    
    // Get current user ID (assuming admin with ID 1)
    $userId = 1;
    
    echo "<h2>1. Checking existing notifications</h2>";
    
    // Get unread count
    $unreadCount = $notificationHelper->getUnreadCount($userId);
    echo "<p>Unread count: <strong>$unreadCount</strong></p>";
    
    // Get notifications
    $notifications = $notificationHelper->getUserNotifications($userId, 10, 0);
    echo "<p>Total notifications: <strong>" . count($notifications) . "</strong></p>";
    
    echo "<h2>2. Recent notifications</h2>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Read</th><th>Created</th><th>Time Ago</th></tr>";
    
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['title']}</td>";
        echo "<td>{$notif['message']}</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "<td>" . ($notif['time_ago'] ?? 'N/A') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h2>3. Database check</h2>";
    $stmt = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ?");
    $stmt->execute([$userId]);
    $total = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "<p>Total notifications in DB: <strong>$total</strong></p>";
    
    $stmt = $db->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$userId]);
    $unread = $stmt->fetch(PDO::FETCH_ASSOC)['unread'];
    echo "<p>Unread notifications in DB: <strong>$unread</strong></p>";
    
    echo "<h2>4. Test API endpoints</h2>";
    echo "<p><a href='api/notifications.php?action=list' target='_blank'>Test notifications list</a></p>";
    echo "<p><a href='api/notifications.php?action=count' target='_blank'>Test notification count</a></p>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
