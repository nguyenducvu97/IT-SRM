<?php
// Check what happened with request #70
require_once 'config/database.php';

echo "<h1>Debug Request #70</h1>";

try {
    $db = getDatabaseConnection();
    
    echo "<h2>1. Request #70 Details</h2>";
    $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = 70");
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<p>Request #70 found:</p>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        foreach ($request as $key => $value) {
            echo "<tr><td>$key</td><td>$value</td></tr>";
        }
        echo "</table>";
        
        echo "<h2>2. Notifications for Request #70</h2>";
        $stmt = $db->prepare("
            SELECT n.*, u.username 
            FROM notifications n
            LEFT JOIN users u ON n.user_id = u.id
            WHERE n.related_id = 70 AND n.related_type = 'service_request'
            ORDER BY n.created_at DESC
        ");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($notifications)) {
            echo "<p style='color: red; font-weight: bold;'>NO NOTIFICATIONS FOUND FOR REQUEST #70!</p>";
        } else {
            echo "<p>Found " . count($notifications) . " notifications:</p>";
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
        
        echo "<h2>3. Check Error Logs Around Request #70 Creation Time</h2>";
        $logFile = 'logs/api_errors.log';
        if (file_exists($logFile)) {
            $logs = file_get_contents($logFile);
            $lines = explode("\n", $logs);
            $relevantLogs = [];
            
            foreach ($lines as $line) {
                if (strpos($line, '70') !== false || 
                    strpos($line, 'notification') !== false ||
                    strpos($line, 'Failed to create') !== false) {
                    $relevantLogs[] = $line;
                }
            }
            
            if (!empty($relevantLogs)) {
                echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 300px; overflow-y: auto;'>";
                foreach (array_slice($relevantLogs, -20) as $log) {
                    echo htmlspecialchars($log) . "\n";
                }
                echo "</pre>";
            } else {
                echo "<p>No relevant error logs found</p>";
            }
        }
        
        echo "<h2>4. Compare with Working Request #69</h2>";
        $stmt = $db->prepare("
            SELECT n.*, u.username 
            FROM notifications n
            LEFT JOIN users u ON n.user_id = u.id
            WHERE n.related_id = 69 AND n.related_type = 'service_request'
            ORDER BY n.created_at DESC
        ");
        $stmt->execute();
        $notifications69 = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Request #69 has " . count($notifications69) . " notifications (working):</p>";
        
    } else {
        echo "<p style='color: red;'>Request #70 NOT FOUND in database!</p>";
        
        echo "<h2>Check Recent Requests</h2>";
        $stmt = $db->prepare("
            SELECT * FROM service_requests 
            ORDER BY created_at DESC 
            LIMIT 5
        ");
        $stmt->execute();
        $recent = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>ID</th><th>Title</th><th>User</th><th>Created</th></tr>";
        foreach ($recent as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['title']}</td>";
            echo "<td>{$req['user_id']}</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
