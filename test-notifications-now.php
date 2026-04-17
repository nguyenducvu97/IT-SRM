<?php
// Test notifications immediately
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/ServiceRequestNotificationHelper.php';

echo "<h2>Test Notifications Immediately</h2>";

try {
    $db = getDatabaseConnection();
    
    // Get a real request from database
    $stmt = $db->prepare("SELECT * FROM service_requests WHERE status = 'open' LIMIT 1");
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request) {
        echo "No open requests found. Creating test data...<br>";
        
        // Create test request
        $insert = $db->prepare("INSERT INTO service_requests (title, description, user_id, category_id, status, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $insert->execute(['Test Request', 'Test Description', 1, 1, 'open']);
        $request_id = $db->lastInsertId();
        echo "Created test request ID: $request_id<br>";
    } else {
        $request_id = $request['id'];
        echo "Using existing request ID: $request_id<br>";
    }
    
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    echo "<h3>Testing User Notification</h3>";
    $userResult = $notificationHelper->notifyUserRequestInProgress(
        $request_id, 
        1, // User ID
        'Test Staff Name'
    );
    echo "User notification: " . ($userResult ? "✅ SUCCESS" : "❌ FAILED") . "<br>";
    
    echo "<h3>Testing Admin Notification</h3>";
    $adminResult = $notificationHelper->notifyAdminStatusChange(
        $request_id,
        'open',
        'in_progress', 
        'Test Staff Name',
        'Test Request Title'
    );
    echo "Admin notification: " . ($adminResult ? "✅ SUCCESS" : "❌ FAILED") . "<br>";
    
    echo "<h3>Check Database</h3>";
    $check = $db->prepare("SELECT * FROM notifications WHERE related_id = ? AND related_type = 'service_request' ORDER BY created_at DESC LIMIT 10");
    $check->execute([$request_id]);
    $notifications = $check->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($notifications) . " notifications:<br>";
    foreach ($notifications as $notif) {
        echo "- ID: {$notif['id']}, User: {$notif['user_id']}, Title: {$notif['title']}, Type: {$notif['type']}<br>";
    }
    
    echo "<h3>Test Complete</h3>";
    echo "✅ Test completed. Check results above.";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
