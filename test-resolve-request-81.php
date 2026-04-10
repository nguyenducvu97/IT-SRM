<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Test Resolve Request #81</h2>";
    
    // Get request details
    $request_query = "SELECT sr.id, sr.title, sr.status, sr.assigned_to, sr.user_id, u.full_name as assigned_name
                       FROM service_requests sr
                       LEFT JOIN users u ON sr.assigned_to = u.id
                       WHERE sr.id = 81";
    
    $request_stmt = $db->prepare($request_query);
    $request_stmt->execute();
    $request = $request_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<h3>Request Details:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th><th>User ID</th></tr>";
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . htmlspecialchars($request['title']) . "</td>";
        echo "<td>{$request['status']}</td>";
        echo "<td>{$request['assigned_name']}</td>";
        echo "<td>{$request['user_id']}</td>";
        echo "</tr>";
        echo "</table>";
        
        // Get user details
        $user_query = "SELECT full_name, email FROM users WHERE id = :user_id";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->bindValue(':user_id', $request['user_id']);
        $user_stmt->execute();
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>User Details:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Name</th><th>Email</th></tr>";
        echo "<tr>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['email']}</td>";
        echo "</tr>";
        echo "</table>";
        
        // Get admin users
        $admin_query = "SELECT id, full_name, email FROM users WHERE role = 'admin'";
        $admin_stmt = $db->prepare($admin_query);
        $admin_stmt->execute();
        $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Admin Users:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Name</th><th>Email</th></tr>";
        foreach ($admins as $admin) {
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['full_name']}</td>";
            echo "<td>{$admin['email']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Test notification functions
        echo "<h3>Test Notification Functions:</h3>";
        
        require_once __DIR__ . '/../lib/NotificationHelper.php';
        $notificationHelper = new NotificationHelper($db);
        
        // Test notifyUser
        echo "<h4>Test notifyUser:</h4>";
        $user_title = "Yêu yêu #81 yêu yêu yêu yêu";
        $user_message = "John Smith yêu yêu yêu yêu: {$request['title']}";
        
        $user_result = $notificationHelper->notifyUser($request['user_id'], $user_title, $user_message, 'success', 81, 'request');
        echo "<p>notifyUser result: " . ($user_result ? "SUCCESS" : "FAILED") . "</p>";
        
        // Test notifyAdmins
        echo "<h4>Test notifyAdmins:</h4>";
        $admin_title = "Yêu yêu #81 yêu yêu yêu yêu";
        $admin_message = "John Smith yêu yêu yêu yêu: {$request['title']}";
        
        $admin_result = $notificationHelper->notifyAdmins($admin_title, $admin_message, 'success', 81, 'request');
        echo "<p>notifyAdmins result: " . ($admin_result ? "SUCCESS" : "FAILED") . "</p>";
        
        // Check recent notifications
        echo "<h3>Recent Notifications:</h3>";
        $notification_query = "SELECT n.id, n.title, n.message, n.type, n.created_at, u.full_name as user_name
                               FROM notifications n
                               LEFT JOIN users u ON n.user_id = u.id
                               WHERE n.service_request_id = 81
                               ORDER BY n.created_at DESC
                               LIMIT 10";
        
        $notification_stmt = $db->prepare($notification_query);
        $notification_stmt->execute();
        $notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifications) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>User</th><th>Created</th></tr>";
            foreach ($notifications as $notification) {
                echo "<tr>";
                echo "<td>{$notification['id']}</td>";
                echo "<td>" . htmlspecialchars($notification['title']) . "</td>";
                echo "<td>" . htmlspecialchars($notification['message']) . "</td>";
                echo "<td>{$notification['type']}</td>";
                echo "<td>{$notification['user_name']}</td>";
                echo "<td>{$notification['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>No notifications found for request #81</p>";
        }
        
        echo "<h3>Manual Test Steps:</h3>";
        echo "<ol>";
        echo "<li>Login as <strong>John Smith</strong> (staff)</li>";
        echo "<li>Navigate to request #81</li>";
        echo "<li>Click 'Giã Quyãt' button</li>";
        echo "<li>Fill in resolution form:</li>";
        echo "<ul>";
        echo "<li>Error Description: Test error</li>";
        echo "<li>Error Type: Test type</li>";
        echo "<li>Solution Method: Test solution</li>";
        echo "</ul>";
        echo "<li>Submit the form</li>";
        echo "<li>Check notifications for user and admins</li>";
        echo "<li>Check logs for 'RESOLVE NOTIFICATIONS SENT' message</li>";
        echo "</ol>";
        
        echo "<h3>Expected Results:</h3>";
        echo "<ul>";
        echo "<li>Request status changes to 'resolved'</li>";
        echo "<li>User receives notification: 'Yêu yêu #81 yêu yêu yêu yêu'</li>";
        echo "<li>All admins receive notification: 'Yêu yêu #81 yêu yêu yêu yêu'</li>";
        echo "<li>Log message: 'RESOLVE NOTIFICATIONS SENT - User: {$request['user_id']}, Admins notified'</li>";
        echo "</ul>";
        
    } else {
        echo "<p>Request #81 not found</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
