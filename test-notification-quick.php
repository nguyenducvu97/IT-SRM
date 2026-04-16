<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

// Test basic notification functionality
echo "<h1>Quick Notification Test</h1>";

try {
    $notificationHelper = new ServiceRequestNotificationHelper();
    echo "✅ ServiceRequestNotificationHelper loaded successfully<br>";
    
    // Test getting users by role
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    echo "✅ Found " . count($adminUsers) . " admin users<br>";
    
    // Test creating a simple notification
    $result = $notificationHelper->notificationHelper->createNotification(
        1, // admin user ID
        "Test Notification",
        "This is a test notification",
        "info",
        1, // test request ID
        "service_request"
    );
    
    if ($result) {
        echo "✅ Test notification created successfully<br>";
    } else {
        echo "❌ Failed to create test notification<br>";
    }
    
    // Check database
    $db = getDatabaseConnection();
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "✅ Total notifications in database: {$count}<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
