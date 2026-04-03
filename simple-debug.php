<?php
// Simple debug for admin notifications
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "<h2>Simple Debug Admin Notifications</h2>";

try {
    $db = getDatabaseConnection();
    
    // Step 1: Check admin users
    echo "<h3>Step 1: Check Admin Users</h3>";
    $adminCheck = $db->prepare("SELECT id, username, full_name, role FROM users WHERE role = 'admin'");
    $adminCheck->execute();
    $admins = $adminCheck->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($admins) . " admin users</p>";
    foreach ($admins as $admin) {
        echo "<li>ID: {$admin['id']}, Username: {$admin['username']}, Name: {$admin['full_name']}</li>";
    }
    
    // Step 2: Test notification helper
    echo "<h3>Step 2: Test ServiceRequestNotificationHelper</h3>";
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    echo "<p>getUsersByRole found: " . count($adminUsers) . " users</p>";
    
    // Step 3: Check service requests
    echo "<h3>Step 3: Check Service Requests</h3>";
    $requestCheck = $db->prepare("SELECT id, title FROM service_requests ORDER BY id DESC LIMIT 1");
    $requestCheck->execute();
    $latestRequest = $requestCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($latestRequest) {
        echo "<p>Latest request: ID {$latestRequest['id']} - {$latestRequest['title']}</p>";
        
        // Step 4: Test notification creation
        echo "<h3>Step 4: Test Notification Creation</h3>";
        
        echo "<h4>Testing notifyAdminRejectionRequest...</h4>";
        $result = $notificationHelper->notifyAdminRejectionRequest(
            $latestRequest['id'], 
            "Test reject reason", 
            "Test Staff", 
            $latestRequest['title']
        );
        echo "<p>Result: " . ($result ? "SUCCESS" : "FAILED") . "</p>";
        
        echo "<h4>Testing notifyAdminSupportRequest...</h4>";
        $result2 = $notificationHelper->notifyAdminSupportRequest(
            $latestRequest['id'], 
            "Test support details", 
            "Test Staff", 
            $latestRequest['title']
        );
        echo "<p>Result: " . ($result2 ? "SUCCESS" : "FAILED") . "</p>";
        
        // Step 5: Check notifications table
        echo "<h3>Step 5: Check Notifications Table</h3>";
        $notifCheck = $db->prepare("SELECT id, user_id, title, message, type, created_at FROM notifications ORDER BY created_at DESC LIMIT 3");
        $notifCheck->execute();
        $notifications = $notifCheck->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Found " . count($notifications) . " recent notifications</p>";
        foreach ($notifications as $notif) {
            echo "<li>User ID: {$notif['user_id']}, Title: {$notif['title']}, Type: {$notif['type']}</li>";
        }
        
    } else {
        echo "<p>No service requests found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
