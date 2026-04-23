<?php
echo "<h1>Test NotificationHelper Instance in ServiceRequestNotificationHelper</h1>";

require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';
require_once 'lib/NotificationHelper.php';

try {
    $db = (new Database())->getConnection();
    
    echo "<h3>Test 1: Direct NotificationHelper instance</h3>";
    $directHelper = new NotificationHelper($db);
    echo "<p>Direct NotificationHelper created</p>";
    
    // Test createNotification
    $staff_query = "SELECT id, full_name FROM users WHERE role = 'staff' LIMIT 1";
    $staff_stmt = $db->query($staff_query);
    $staff_user = $staff_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($staff_user) {
        echo "<p>Staff user: {$staff_user['full_name']} (ID: {$staff_user['id']})</p>";
        
        $result1 = $directHelper->createNotification(
            $staff_user['id'],
            "Direct Test Notification",
            "This is from direct NotificationHelper",
            'info',
            999,
            'service_request',
            false
        );
        
        echo "<p>Direct createNotification result: " . ($result1 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    }
    
    echo "<hr>";
    
    echo "<h3>Test 2: NotificationHelper via ServiceRequestNotificationHelper</h3>";
    $serviceHelper = new ServiceRequestNotificationHelper();
    
    // Access notificationHelper property directly
    $notificationHelperProperty = $serviceHelper->notificationHelper;
    echo "<p>NotificationHelper property accessed</p>";
    
    // Test createNotification
    if ($staff_user) {
        $result2 = $notificationHelperProperty->createNotification(
            $staff_user['id'],
            "ServiceHelper Test Notification",
            "This is from ServiceRequestNotificationHelper->notificationHelper",
            'info',
            999,
            'service_request',
            false
        );
        
        echo "<p>ServiceHelper createNotification result: " . ($result2 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    }
    
    echo "<hr>";
    
    echo "<h3>Test 3: Check database connection</h3>";
    echo "<p>Direct DB connection: " . ($db ? "✅ Connected" : "❌ Not connected") . "</p>";
    
    // Try to query
    $test_query = "SELECT 1 as test";
    $test_result = $db->query($test_query);
    $test_row = $test_result->fetch(PDO::FETCH_ASSOC);
    echo "<p>DB query test: " . ($test_row['test'] == 1 ? "✅ Working" : "❌ Failed") . "</p>";
    
    // Check ServiceRequestNotificationHelper DB connection
    $reflection = new ReflectionClass($serviceHelper);
    $dbProperty = $reflection->getProperty('db');
    $dbProperty->setAccessible(true);
    $serviceDb = $dbProperty->getValue($serviceHelper);
    
    echo "<p>ServiceRequestNotificationHelper DB: " . ($serviceDb ? "✅ Connected" : "❌ Not connected") . "</p>";
    
    if ($serviceDb) {
        $test_query2 = "SELECT 1 as test";
        $test_result2 = $serviceDb->query($test_query2);
        $test_row2 = $test_result2->fetch(PDO::FETCH_ASSOC);
        echo "<p>ServiceHelper DB query test: " . ($test_row2['test'] == 1 ? "✅ Working" : "❌ Failed") . "</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
?>
