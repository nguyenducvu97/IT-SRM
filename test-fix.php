<?php
// Test fixed getUsersByRole method
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Test Fixed getUsersByRole</h2>";

try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    require_once 'lib/ServiceRequestNotificationHelper.php';
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    echo "<h3>Test with single role ['admin']</h3>";
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    echo "<p>✅ Method completed</p>";
    echo "<p>Found " . count($adminUsers) . " admin users</p>";
    
    if (!empty($adminUsers)) {
        echo "<h4>Admin Users:</h4>";
        foreach ($adminUsers as $admin) {
            echo "<li>ID: {$admin['id']}, Username: {$admin['username']}, Name: {$admin['full_name']}</li>";
        }
    }
    
    echo "<h3>Test notification creation</h3>";
    $requestCheck = $db->prepare("SELECT id, title FROM service_requests ORDER BY id DESC LIMIT 1");
    $requestCheck->execute();
    $latestRequest = $requestCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($latestRequest && !empty($adminUsers)) {
        echo "<p>Testing with request: {$latestRequest['id']} - {$latestRequest['title']}</p>";
        
        $result = $notificationHelper->notifyAdminRejectionRequest(
            $latestRequest['id'], 
            "Test reason", 
            "Test Staff", 
            $latestRequest['title']
        );
        
        echo "<p>❌ Notification result: " . ($result ? "SUCCESS" : "FAILED") . "</p>";
        
        if ($result) {
            // Check if notification was created
            $notifCheck = $db->prepare("SELECT id, title, created_at FROM notifications WHERE user_id = 1 ORDER BY created_at DESC LIMIT 1");
            $notifCheck->execute();
            $notification = $notifCheck->fetch(PDO::FETCH_ASSOC);
            
            if ($notification) {
                echo "<p>✅ Notification created in database:</p>";
                echo "<li>ID: {$notification['id']}, Title: {$notification['title']}, Created: {$notification['created_at']}</li>";
            } else {
                echo "<p>❌ No notification found in database</p>";
            }
        }
    } else {
        echo "<p>⚠️ No service requests found for testing</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>✅ Test Complete</h2>";
?>
