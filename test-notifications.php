<?php
// Test notification functionality
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Mock user for testing
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Test Admin';
}

// Include required files
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/NotificationHelper.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();
    $notificationHelper = new NotificationHelper($pdo);
    
    $userId = $_SESSION['user_id'];
    
    echo "<h1>Notification System Test</h1>";
    
    // Test 1: Get unread count
    echo "<h2>Test 1: Get Unread Count</h2>";
    $unreadCount = $notificationHelper->getUnreadCount($userId);
    echo "<p>Unread count: <strong>$unreadCount</strong></p>";
    
    // Test 2: Get notifications
    echo "<h2>Test 2: Get Notifications</h2>";
    $notifications = $notificationHelper->getUserNotifications($userId, 10, 0);
    echo "<p>Total notifications: <strong>" . count($notifications) . "</strong></p>";
    
    if (!empty($notifications)) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Read</th><th>Created</th></tr>";
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>{$notif['message']}</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No notifications found</p>";
    }
    
    // Test 3: Create test notification
    echo "<h2>Test 3: Create Test Notification</h2>";
    $testTitle = "Test Notification " . date('Y-m-d H:i:s');
    $testMessage = "This is a test notification created at " . date('Y-m-d H:i:s');
    
    $result = $notificationHelper->createNotification(
        $userId,
        $testTitle,
        $testMessage,
        'info',
        null,
        null,
        false // Don't send email for test
    );
    
    if ($result) {
        echo "<p style='color: green;'>✅ Test notification created successfully!</p>";
        
        // Test 4: Verify notification was created
        echo "<h2>Test 4: Verify Notification Creation</h2>";
        $newCount = $notificationHelper->getUnreadCount($userId);
        echo "<p>Previous unread count: $unreadCount</p>";
        echo "<p>New unread count: <strong>$newCount</strong></p>";
        echo "<p>Count increased: " . ($newCount > $unreadCount ? '✅ Yes' : '❌ No') . "</p>";
        
        // Get latest notification
        $latestNotifications = $notificationHelper->getUserNotifications($userId, 1, 0);
        if (!empty($latestNotifications)) {
            $latest = $latestNotifications[0];
            echo "<h3>Latest Notification:</h3>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>Title</th><td>{$latest['title']}</td></tr>";
            echo "<tr><th>Message</th><td>{$latest['message']}</td></tr>";
            echo "<tr><th>Type</th><td>{$latest['type']}</td></tr>";
            echo "<tr><th>Read</th><td>" . ($latest['is_read'] ? 'Yes' : 'No') . "</td></tr>";
            echo "<tr><th>Created</th><td>{$latest['created_at']}</td></tr>";
            echo "</table>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to create test notification!</p>";
    }
    
    // Test 5: API endpoints
    echo "<h2>Test 5: API Endpoints</h2>";
    echo "<h3>API Test - Get Notifications:</h3>";
    echo "<iframe src='api/notifications.php?action=get' width='100%' height='200'></iframe>";
    
    echo "<h3>API Test - Get Count:</h3>";
    echo "<iframe src='api/notifications.php?action=count' width='100%' height='100'></iframe>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>

<p><a href="index.html">Back to Application</a></p>
