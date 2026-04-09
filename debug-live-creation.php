<?php
// Debug script to check what happens during real request creation
echo "<h1>Debug Live Request Creation</h1>";

// Check if there's a recent request creation
require_once 'config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>1. Recent Requests (last 5 minutes)</h2>";
    $stmt = $db->prepare("
        SELECT sr.*, u.username 
        FROM service_requests sr
        LEFT JOIN users u ON sr.user_id = u.id
        WHERE sr.created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY sr.created_at DESC
        LIMIT 5
    ");
    $stmt->execute();
    $recentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($recentRequests)) {
        echo "<p>No recent requests found in last 5 minutes</p>";
        echo "<p><a href='index.html' target='_blank'>Create a new request now</a> and refresh this page</p>";
    } else {
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>User</th><th>Created</th></tr>";
        foreach ($recentRequests as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['title']}</td>";
            echo "<td>{$req['username']}</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h2>2. Notifications for these requests</h2>";
        foreach ($recentRequests as $req) {
            echo "<h3>Request #{$req['id']} - {$req['title']}</h3>";
            
            $stmt = $db->prepare("
                SELECT n.*, u.username 
                FROM notifications n
                LEFT JOIN users u ON n.user_id = u.id
                WHERE n.related_id = ? AND n.related_type = 'service_request'
                ORDER BY n.created_at DESC
            ");
            $stmt->execute([$req['id']]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($notifications)) {
                echo "<p style='color: red;'>No notifications found for this request!</p>";
            } else {
                echo "<table border='1'>";
                echo "<tr><th>User</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
                foreach ($notifications as $notif) {
                    echo "<tr>";
                    echo "<td>{$notif['username']} ({$notif['user_id']})</td>";
                    echo "<td>{$notif['title']}</td>";
                    echo "<td>{$notif['message']}</td>";
                    echo "<td>{$notif['type']}</td>";
                    echo "<td>{$notif['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
        }
    }
    
    echo "<h2>3. Check Error Logs</h2>";
    $logFile = 'logs/api_errors.log';
    if (file_exists($logFile)) {
        echo "<p>Recent error logs:</p>";
        $logs = file_get_contents($logFile);
        $recentLogs = array_slice(explode("\n", $logs), -10);
        echo "<pre style='background: #f0f0f0; padding: 10px;'>";
        foreach ($recentLogs as $log) {
            if (strpos($log, 'notification') !== false) {
                echo htmlspecialchars($log) . "\n";
            }
        }
        echo "</pre>";
    } else {
        echo "<p>No error log file found</p>";
    }
    
    echo "<h2>4. Test Live Creation</h2>";
    echo "<p>1. Open <a href='index.html' target='_blank'>index.html</a> in a new tab</p>";
    echo "<p>2. Create a new request as a regular user</p>";
    echo "<p>3. Refresh this page to see if notifications were created</p>";
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
