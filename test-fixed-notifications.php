<?php
// Test fixed notification creation
require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "<h2>Test Fixed Notification Creation</h2>";

try {
    $db = getDatabaseConnection();
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    // Get a test request
    $requestCheck = $db->prepare("SELECT id, title FROM service_requests ORDER BY id DESC LIMIT 1");
    $requestCheck->execute();
    $request = $requestCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<h3>Testing with Request #{$request['id']}: {$request['title']}</h3>";
        
        // Test notifyAdminRejectionRequest
        echo "<h4>Testing notifyAdminRejectionRequest...</h4>";
        $result1 = $notificationHelper->notifyAdminRejectionRequest(
            $request['id'], 
            "Test reject reason", 
            "Test Staff", 
            $request['title']
        );
        echo "<p>Result: " . ($result1 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
        
        // Test notifyAdminSupportRequest
        echo "<h4>Testing notifyAdminSupportRequest...</h4>";
        $result2 = $notificationHelper->notifyAdminSupportRequest(
            $request['id'], 
            "Test support details", 
            "Test Staff", 
            $request['title']
        );
        echo "<p>Result: " . ($result2 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
        
        // Check database for created notifications
        echo "<h4>Checking Database...</h4>";
        $notifCheck = $db->prepare("SELECT id, related_id, related_type, title FROM notifications WHERE user_id = 1 ORDER BY created_at DESC LIMIT 5");
        $notifCheck->execute();
        $notifications = $notifCheck->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Related ID</th><th>Related Type</th><th>Title</th><th>Navigation</th></tr>";
        
        foreach ($notifications as $notif) {
            $navUrl = '';
            switch ($notif['related_type']) {
                case 'service_request':
                    $navUrl = "request-detail.html?id={$notif['related_id']}";
                    break;
                default:
                    $navUrl = 'index.html';
            }
            
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['related_id']}</td>";
            echo "<td><strong>{$notif['related_type']}</strong></td>";
            echo "<td>" . substr($notif['title'], 0, 30) . "...</td>";
            echo "<td><a href='{$navUrl}' target='_blank'>{$navUrl}</a></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h3>✅ Expected Behavior:</h3>";
        echo "<ul>";
        echo "<li>✅ Related Type should be 'service_request'</li>";
        echo "<li>✅ Click should navigate to request-detail.html?id=[service_request_id]</li>";
        echo "<li>✅ Admin should see request details</li>";
        echo "</ul>";
        
    } else {
        echo "<p>No service requests found for testing</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
