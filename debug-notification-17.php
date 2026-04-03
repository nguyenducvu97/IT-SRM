<?php
// Debug specific notification click issue
require_once 'config/database.php';

echo "<h2>Debug Notification Click: Request #17</h2>";

try {
    $db = getDatabaseConnection();
    
    // Get the specific notification
    $notifCheck = $db->prepare("SELECT id, user_id, title, message, type, related_id, related_type, is_read, created_at FROM notifications WHERE user_id = 1 AND related_id = 17 ORDER BY created_at DESC LIMIT 1");
    $notifCheck->execute();
    $notification = $notifCheck->fetch(PDO::FETCH_ASSOC);
    
    if ($notification) {
        echo "<h3>Found Notification:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>{$notification['id']}</td></tr>";
        echo "<tr><td>Title</td><td>{$notification['title']}</td></tr>";
        echo "<tr><td>Message</td><td>" . htmlspecialchars($notification['message']) . "</td></tr>";
        echo "<tr><td>Type</td><td>{$notification['type']}</td></tr>";
        echo "<tr><td><strong>Related ID</strong></td><td><strong>{$notification['related_id']}</strong></td></tr>";
        echo "<tr><td><strong>Related Type</strong></td><td><strong>{$notification['related_type']}</strong></td></tr>";
        echo "<tr><td>Is Read</td><td>" . ($notification['is_read'] ? 'Yes' : 'No') . "</td></tr>";
        echo "<tr><td>Created</td><td>{$notification['created_at']}</td></tr>";
        echo "</table>";
        
        echo "<h3>Expected Navigation:</h3>";
        $expectedUrl = "request-detail.html?id={$notification['related_id']}";
        echo "<p>Should navigate to: <strong><a href='{$expectedUrl}' target='_blank'>{$expectedUrl}</a></strong></p>";
        
        echo "<h3>JavaScript Debug Info:</h3>";
        echo "<p>When notification is clicked, JavaScript should:</p>";
        echo "<ol>";
        echo "<li>Find notification with ID: {$notification['id']}</li>";
        echo "<li>Check related_type: '{$notification['related_type']}'</li>";
        echo "<li>Match case 'service_request' (since we added it)</li>";
        echo "<li>Navigate to: {$expectedUrl}</li>";
        echo "<li>Mark notification as read</li>";
        echo "</ol>";
        
        // Check if request actually exists
        echo "<h3>Verify Service Request Exists:</h3>";
        $requestCheck = $db->prepare("SELECT id, title, status FROM service_requests WHERE id = ?");
        $requestCheck->execute([$notification['related_id']]);
        $request = $requestCheck->fetch(PDO::FETCH_ASSOC);
        
        if ($request) {
            echo "<p>✅ Service Request #{$request['id']} exists: {$request['title']} (Status: {$request['status']})</p>";
        } else {
            echo "<p style='color: red;'>❌ Service Request #{$notification['related_id']} does not exist!</p>";
        }
        
    } else {
        echo "<p style='color: orange;'>⚠️ No notification found for user_id=1 and related_id=17</p>";
        
        // Check all notifications for admin
        $allNotifs = $db->prepare("SELECT id, related_id, related_type, title FROM notifications WHERE user_id = 1 ORDER BY created_at DESC LIMIT 5");
        $allNotifs->execute();
        $notifications = $allNotifs->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>All Recent Admin Notifications:</h3>";
        foreach ($notifications as $notif) {
            echo "<li>ID: {$notif['id']}, Related: {$notif['related_id']} ({$notif['related_type']}), Title: {$notif['title']}</li>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
