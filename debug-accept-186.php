<?php
// Debug accept request #186 specifically
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug Accept Request #186</h1>";

// Check request #186 details
try {
    require_once 'config/database.php';
    $db = (new Database())->getConnection();
    
    echo "<h2>Request #186 Details</h2>";
    $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ?");
    $stmt->execute([186]);
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>User ID</th><th>Status</th><th>Assigned To</th><th>Created</th></tr>";
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . htmlspecialchars($request['title']) . "</td>";
        echo "<td>{$request['user_id']}</td>";
        echo "<td>{$request['status']}</td>";
        echo "<td>" . ($request['assigned_to'] ?? 'None') . "</td>";
        echo "<td>{$request['created_at']}</td>";
        echo "</tr>";
        echo "</table>";
        
        // Test manual notification creation
        echo "<h2>Manual Notification Test</h2>";
        
        require_once 'lib/ServiceRequestNotificationHelper.php';
        $notificationHelper = new ServiceRequestNotificationHelper();
        
        // Test user notification
        echo "<h3>Test User Notification</h3>";
        $userResult = $notificationHelper->notifyUserRequestInProgress(
            186, 
            $request['user_id'], 
            'Test Staff Name'
        );
        echo "<p>User notification result: " . ($userResult ? "SUCCESS" : "FAILED") . "</p>";
        
        // Test admin notification
        echo "<h3>Test Admin Notification</h3>";
        $adminResult = $notificationHelper->notifyAdminStatusChange(
            186, 
            'open', 
            'in_progress', 
            'Test Staff Name', 
            $request['title']
        );
        echo "<p>Admin notification result: " . ($adminResult ? "SUCCESS" : "FAILED") . "</p>";
        
        // Check notifications for request #186
        echo "<h2>Notifications for Request #186</h2>";
        $stmt = $db->prepare("SELECT * FROM notifications WHERE related_id = ? AND related_type = 'service_request' ORDER BY created_at DESC");
        $stmt->execute([186]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($notifications)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
            foreach ($notifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['id']}</td>";
                echo "<td>{$notif['user_id']}</td>";
                echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
                echo "<td>{$notif['type']}</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>No notifications found for request #186</p>";
        }
        
        // Check users
        echo "<h2>Check Users</h2>";
        echo "<h3>Request User (ID: {$request['user_id']})</h3>";
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$request['user_id']]);
        $requestUser = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($requestUser) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Email</th></tr>";
            echo "<tr>";
            echo "<td>{$requestUser['id']}</td>";
            echo "<td>{$requestUser['username']}</td>";
            echo "<td>{$requestUser['full_name']}</td>";
            echo "<td>{$requestUser['role']}</td>";
            echo "<td>{$requestUser['email']}</td>";
            echo "</tr>";
            echo "</table>";
        } else {
            echo "<p style='color: red;'>Request user not found</p>";
        }
        
        echo "<h3>Admin Users</h3>";
        $stmt = $db->query("SELECT * FROM users WHERE role = 'admin'");
        $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($admins)) {
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Email</th></tr>";
            foreach ($admins as $admin) {
                echo "<tr>";
                echo "<td>{$admin['id']}</td>";
                echo "<td>{$admin['username']}</td>";
                echo "<td>{$admin['full_name']}</td>";
                echo "<td>{$admin['role']}</td>";
                echo "<td>{$admin['email']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: red;'>No admin users found</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Request #186 not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Check PHP Error Logs</h2>";
$logFile = 'C:/xampp/apache/logs/error.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLines = array_slice($lines, -20); // Last 20 lines
    
    echo "<div style='font-family: monospace; font-size: 12px; background-color: #f8f9fa; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto;'>";
    foreach ($recentLines as $line) {
        if (strpos($line, 'NOTIFICATIONS:') !== false || 
            strpos($line, 'EMAIL:') !== false || 
            strpos($line, 'createNotification') !== false ||
            strpos($line, 'notifyUser') !== false ||
            strpos($line, 'notifyAdmin') !== false ||
            strpos($line, '186') !== false) {
            echo "<p style='color: blue; margin: 2px 0;'>" . htmlspecialchars($line) . "</p>";
        }
    }
    echo "</div>";
} else {
    echo "<p>PHP error log not found</p>";
}

echo "<hr>";
echo "<h2>Next Steps</h2>";
echo "<p>If manual notifications work but API notifications don't, the issue is in the API accept_request logic.</p>";
echo "<p>If manual notifications also fail, the issue is in the notification system itself.</p>";

echo "<p><a href='test-accept-minimal.php?request_id=186'>Test Accept Request #186 Again</a></p>";
echo "<p><a href='check-notifications.php'>Check All Notifications</a></p>";
echo "<p><a href='index.html'>Main Application</a></p>";
?>
