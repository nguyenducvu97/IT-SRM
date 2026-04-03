<?php
// Debug script for admin notifications
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "<h2>Debug Admin Notifications</h2>";

try {
    $db = getDatabaseConnection();
    
    // Check admin users
    echo "<h3>1. Check Admin Users</h3>";
    $adminCheck = $db->prepare("SELECT id, username, full_name, role FROM users WHERE role = 'admin'");
    $adminCheck->execute();
    $admins = $adminCheck->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($admins)) {
        echo "<p style='color: red;'>❌ No admin users found!</p>";
    } else {
        echo "<p style='color: green;'>✅ Found " . count($admins) . " admin users:</p>";
        echo "<ul>";
        foreach ($admins as $admin) {
            echo "<li>ID: {$admin['id']}, Username: {$admin['username']}, Name: {$admin['full_name']}</li>";
        }
        echo "</ul>";
    }
    
    // Test notification helper
    echo "<h3>2. Test ServiceRequestNotificationHelper</h3>";
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    // Test getUsersByRole
    echo "<h4>Test getUsersByRole(['admin'])</h4>";
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    echo "<p>Found " . count($adminUsers) . " admin users</p>";
    if (!empty($adminUsers)) {
        echo "<pre>" . print_r($adminUsers, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ getUsersByRole returned empty array!</p>";
    }
    
    // Test getRequestDetails
    echo "<h4>Test getRequestDetails</h4>";
    $requestCheck = $db->prepare("SELECT id, title FROM service_requests ORDER BY id DESC LIMIT 1");
    $requestCheck->execute();
    $latestRequest = $requestCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($latestRequest) {
        echo "<p>Latest request: ID {$latestRequest['id']} - {$latestRequest['title']}</p>";
        $requestDetails = $notificationHelper->getRequestDetails($latestRequest['id']);
        if ($requestDetails) {
            echo "<p style='color: green;'>✅ Request details found</p>";
            echo "<pre>" . print_r($requestDetails, true) . "</pre>";
        } else {
            echo "<p style='color: red;'>❌ Failed to get request details</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No service requests found in database</p>";
    }
    
    // Test actual notification creation
    echo "<h3>3. Test Notification Creation</h3>";
    if (!empty($admins) && isset($latestRequest)) {
        echo "<h4>Test notifyAdminRejectionRequest</h4>";
        try {
            $result = $notificationHelper->notifyAdminRejectionRequest(
                $latestRequest['id'], 
                "Test reject reason", 
                "Test Staff", 
                $latestRequest['title']
            );
            echo "<p>Notification result: " . ($result ? "✅ Success" : "❌ Failed") . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Exception in notifyAdminRejectionRequest: " . $e->getMessage() . "</p>";
        }
        
        echo "<h4>Test notifyAdminSupportRequest</h4>";
        try {
            $result2 = $notificationHelper->notifyAdminSupportRequest(
                $latestRequest['id'], 
                "Test support details", 
                "Test Staff", 
                $latestRequest['title']
            );
            echo "<p>Notification result: " . ($result2 ? "✅ Success" : "❌ Failed") . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Exception in notifyAdminSupportRequest: " . $e->getMessage() . "</p>";
        }
        
        // Check notifications table
        echo "<h4>Check notifications table</h4>";
        $notifCheck = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
        $notifCheck->execute();
        $notifications = $notifCheck->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($notifications)) {
            echo "<p style='color: red;'>❌ No notifications found in database!</p>";
        } else {
            echo "<p style='color: green;'>✅ Found " . count($notifications) . " recent notifications:</p>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
            foreach ($notifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['id']}</td>";
                echo "<td>{$notif['user_id']}</td>";
                echo "<td>{$notif['title']}</td>";
                echo "<td>" . substr($notif['message'], 0, 50) . "...</td>";
                echo "<td>{$notif['type']}</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>4. Session Debug</h3>";
session_start();
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Current user: " . ($_SESSION['user_id'] ?? 'Not logged in') . "</p>";
echo "<p>User role: " . ($_SESSION['role'] ?? 'No role') . "</p>";
echo "<p>Full name: " . ($_SESSION['full_name'] ?? 'No name') . "</p>";
?>
