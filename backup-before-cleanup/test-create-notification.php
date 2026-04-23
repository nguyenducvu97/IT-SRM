<?php
echo "<h1>Test createNotification Directly</h1>";

require_once 'config/database.php';
require_once 'lib/NotificationHelper.php';

try {
    $db = (new Database())->getConnection();
    $notificationHelper = new NotificationHelper($db);
    
    echo "<h3>Test createNotification with Staff User</h3>";
    
    // Get staff user
    $staff_query = "SELECT id, full_name, email FROM users WHERE role = 'staff' LIMIT 1";
    $staff_stmt = $db->query($staff_query);
    $staff_user = $staff_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$staff_user) {
        echo "<p style='color: red;'>❌ No staff user found</p>";
        exit;
    }
    
    echo "<p>Testing with staff user: {$staff_user['full_name']} (ID: {$staff_user['id']})</p>";
    
    // Test 1: createNotification with sendEmail = false
    echo "<h4>Test 1: createNotification with sendEmail = false</h4>";
    
    $result1 = $notificationHelper->createNotification(
        $staff_user['id'],
        "Test Notification",
        "This is a test notification",
        'info',
        999,
        'service_request',
        false
    );
    
    echo "<p>Result: " . ($result1 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    // Test 2: createNotification with sendEmail = true
    echo "<h4>Test 2: createNotification with sendEmail = true</h4>";
    
    $result2 = $notificationHelper->createNotification(
        $staff_user['id'],
        "Test Notification with Email",
        "This is a test notification with email",
        'info',
        999,
        'service_request',
        true
    );
    
    echo "<p>Result: " . ($result2 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    
    // Check database
    echo "<h4>Database Check</h4>";
    
    $notification_query = "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?";
    $notification_stmt = $db->prepare($notification_query);
    $notification_stmt->execute([$staff_user['id']]);
    $notification_count = $notification_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Total notifications for staff user: {$notification_count['total']}</p>";
    
    // Show recent notifications
    $recent_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
    $recent_stmt = $db->prepare($recent_query);
    $recent_stmt->execute([$staff_user['id']]);
    $recent_notifications = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($recent_notifications)) {
        echo "<h5>Recent Notifications:</h5>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
        echo "</tr>";
        
        foreach ($recent_notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
?>
