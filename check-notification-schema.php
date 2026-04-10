<?php
require_once 'config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h3>🔍 Database Schema Analysis</h3>";
    
    // Check notifications table structure
    echo "<h4>Notifications Table Structure:</h4>";
    $result = $db->query('DESCRIBE notifications');
    $columns = $result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>" . ($column['Default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check if recipient_id column exists
    $has_recipient_id = false;
    $has_service_request_id = false;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'recipient_id') $has_recipient_id = true;
        if ($column['Field'] === 'service_request_id') $has_service_request_id = true;
    }
    
    echo "<h4>Column Analysis:</h4>";
    echo "<p><strong>recipient_id column:</strong> " . ($has_recipient_id ? "✅ EXISTS" : "❌ MISSING") . "</p>";
    echo "<p><strong>service_request_id column:</strong> " . ($has_service_request_id ? "✅ EXISTS" : "❌ MISSING") . "</p>";
    
    // Check recent notifications with correct columns
    echo "<h4>Recent Notifications (Correct Query):</h4>";
    
    if ($has_recipient_id && $has_service_request_id) {
        $query = "SELECT n.*, u.full_name as recipient_name, ur.username as recipient_username
                 FROM notifications n
                 LEFT JOIN users u ON n.user_id = u.id
                 LEFT JOIN users ur ON n.recipient_id = ur.id
                 ORDER BY n.created_at DESC
                 LIMIT 10";
    } else {
        $query = "SELECT n.*, u.full_name as recipient_name
                 FROM notifications n
                 LEFT JOIN users u ON n.user_id = u.id
                 ORDER BY n.created_at DESC
                 LIMIT 10";
    }
    
    try {
        $recent_notifications = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($recent_notifications) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Recipient</th><th>Title</th><th>Type</th><th>Related ID</th><th>Created</th></tr>";
            
            foreach ($recent_notifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['id']}</td>";
                echo "<td>{$notif['user_id']}</td>";
                echo "<td>" . ($notif['recipient_name'] ?: 'User ' . $notif['user_id']) . "</td>";
                echo "<td>" . htmlspecialchars(substr($notif['title'], 0, 30)) . "...</td>";
                echo "<td>{$notif['type']}</td>";
                echo "<td>" . ($notif['related_id'] ?: 'N/A') . "</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No recent notifications found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Query failed: " . $e->getMessage() . "</p>";
    }
    
    // Check NotificationHelper methods
    echo "<h4>🔧 NotificationHelper Methods:</h4>";
    
    $notification_methods = [
        'createNotification',
        'notifyUsers', 
        'notifyRole',
        'notifyRequestParticipants'
    ];
    
    foreach ($notification_methods as $method) {
        if (method_exists('NotificationHelper', $method)) {
            echo "<p>✅ NotificationHelper::$method: EXISTS</p>";
        } else {
            echo "<p>❌ NotificationHelper::$method: NOT FOUND</p>";
        }
    }
    
    // Check ServiceRequestNotificationHelper methods
    echo "<h4>🔧 ServiceRequestNotificationHelper Methods:</h4>";
    
    $service_methods = [
        'notifyUserRequestInProgress',
        'notifyUserRequestPendingApproval', 
        'notifyUserRequestResolved',
        'notifyUserRequestRejected',
        'notifyStaffNewRequest',
        'notifyStaffUserFeedback',
        'notifyStaffAdminApproved',
        'notifyStaffAdminRejected',
        'notifyAdminNewRequest',
        'notifyAdminStatusChange',
        'notifyAdminSupportRequest',
        'notifyAdminRejectionRequest'
    ];
    
    foreach ($service_methods as $method) {
        if (method_exists('ServiceRequestNotificationHelper', $method)) {
            echo "<p>✅ ServiceRequestNotificationHelper::$method: EXISTS</p>";
        } else {
            echo "<p>❌ ServiceRequestNotificationHelper::$method: NOT FOUND</p>";
        }
    }
    
    // Recommendations
    echo "<h4>📋 Recommendations:</h4>";
    
    if (!$has_recipient_id) {
        echo "<p style='color: orange;'>⚠️ Consider adding recipient_id column for better notification targeting</p>";
    }
    
    if (!$has_service_request_id) {
        echo "<p style='color: orange;'>⚠️ Consider adding service_request_id column for request-specific notifications</p>";
    }
    
    echo "<p style='color: green;'>✅ Use existing methods: createNotification, notifyUsers, notifyRole</p>";
    echo "<p style='color: green;'>✅ Use ServiceRequestNotificationHelper for specific scenarios</p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
