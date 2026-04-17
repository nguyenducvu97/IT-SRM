<?php
// Test script to verify accept_request notifications
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/ServiceRequestNotificationHelper.php';

echo "<h2>Test Accept Request Notifications</h2>";

// Test data
$testRequestId = 1; // Change to existing request ID
$testUserId = 2; // Change to existing user ID  
$testStaffId = 3; // Change to existing staff ID

try {
    $db = getDatabaseConnection();
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    echo "<h3>1. Testing User Notification</h3>";
    $userResult = $notificationHelper->notifyUserRequestInProgress(
        $testRequestId, 
        $testUserId, 
        'Test Staff Name'
    );
    echo "User notification result: " . ($userResult ? "SUCCESS" : "FAILED") . "<br>";
    
    echo "<h3>2. Testing Admin Notification</h3>";
    $adminResult = $notificationHelper->notifyAdminStatusChange(
        $testRequestId,
        'open',
        'in_progress', 
        'Test Staff Name',
        'Test Request Title'
    );
    echo "Admin notification result: " . ($adminResult ? "SUCCESS" : "FAILED") . "<br>";
    
    echo "<h3>3. Check Database</h3>";
    $stmt = $db->prepare("SELECT * FROM notifications WHERE related_id = ? AND related_type = 'service_request' ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$testRequestId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Found " . count($notifications) . " notifications:<br>";
    foreach ($notifications as $notif) {
        echo "- ID: {$notif['id']}, User: {$notif['user_id']}, Title: {$notif['title']}<br>";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}
?>
