<?php
// Debug Notification Bell Issue
// This script helps debug why notification count is not showing in the bell

require_once 'config/database.php';
require_once 'config/session.php';

session_start();

echo "<h1>Debug Notification Bell Issue</h1>";

echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h2>Problem Analysis:</h2>";
echo "<p><strong>Issue:</strong> Notifications show in list but count not showing in bell</p>";
echo "<p><strong>Expected:</strong> Bell should show red badge with count</p>";
echo "<p><strong>Actual:</strong> Bell shows no count</p>";
echo "</div>";

// Check current notifications
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>Current Notifications in Database:</h3>";

try {
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("SELECT id, title, message, type, is_read, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([1]); // Test user ID
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $unreadCount = 0;
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Type</th><th>Read</th><th>Created</th></tr>";
    
    foreach ($notifications as $notif) {
        $readStatus = $notif['is_read'] ? 'Yes' : 'No';
        $rowClass = $notif['is_read'] ? '' : 'style="background: #ffe6e6;"';
        
        if (!$notif['is_read']) {
            $unreadCount++;
        }
        
        echo "<tr $rowClass>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>$readStatus</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    echo "<p><strong>Unread Count: $unreadCount</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "</div>";

// Test API response
echo "<div style='background: #fff3cd; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>API Response Test:</h3>";

echo "<div style='margin: 10px 0;'>";
echo "<button onclick='testAPI()' class='btn' style='background: #007bff; color: white; padding: 8px 16px; border: none; border-radius: 4px;'>Test Notification Count API</button>";
echo "<span> - Check what the API returns</span>";
echo "</div>";

echo "<div id='apiResult' style='margin-top: 10px;'></div>";

echo "</div>";

// HTML Structure Check
echo "<div style='background: #d4edda; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>HTML Structure Check:</h3>";
echo "<p><strong>Expected HTML:</strong></p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>";
echo "&lt;button id=\"notificationBtn\" class=\"notification-btn\"&gt;
    &lt;i class=\"fas fa-bell\"&gt;&lt;/i&gt;
    &lt;span id=\"notificationCount\" class=\"notification-count\"&gt;0&lt;/span&gt;
&lt;/button&gt;";
echo "</pre>";

echo "<p><strong>CSS Selectors:</strong></p>";
echo "<ul>";
echo "<li><code>.notification-btn</code> - Button container</li>";
echo "<li><code>#notificationBtn</code> - Button ID</li>";
echo "<li><code>.notification-count</code> - Count span</li>";
echo "<li><code>#notificationCount</code> - Count ID</li>";
echo "<li><code>.notification-badge</code> - Badge (if exists)</li>";
echo "</ul>";
echo "</div>";

// JavaScript Check
echo "<div style='background: #f8d7da; padding: 15px; border-radius: 8px; margin-bottom: 20px;'>";
echo "<h3>JavaScript Functions Check:</h3>";
echo "<p><strong>Functions that should exist:</strong></p>";
echo "<ul>";
echo "<li><code>updateNotificationCount()</code> - Calls API to get count</li>";
echo "<li><code>updateNotificationCountDisplay(count)</code> - Updates both badge and count</li>";
echo "<li><code>updateNotificationBadge(notifications)</code> - Calculates unread from list</li>";
echo "</ul>";

echo "<p><strong>Auto-reload triggers:</strong></p>";
echo "<ul>";
echo "<li>Dashboard load → <code>updateNotificationCount()</code></li>";
echo "<li>Auto-reload every 3s → <code>updateNotificationCount()</code></li>";
echo "<li>Notifications page load → <code>updateNotificationCount()</code> (just added)</li>";
echo "</ul>";
echo "</div>";

echo "<div style='background: #28a745; color: white; padding: 20px; border-radius: 8px;'>";
echo "<h2>Debug Steps:</h2>";
echo "<ol>";
echo "<li><strong>Check database:</strong> Verify unread notifications exist</li>";
echo "<li><strong>Test API:</strong> Click button to test count API response</li>";
echo "<li><strong>Check HTML:</strong> Verify bell button structure exists</li>";
echo "<li><strong>Check JavaScript:</strong> Verify functions are called correctly</li>";
echo "<li><strong>Clear cache:</strong> Ctrl+F5 and test again</li>";
echo "</ol>";
echo "</div>";

?>

<script>
function testAPI() {
    const resultDiv = document.getElementById('apiResult');
    resultDiv.innerHTML = '<p>Testing API...</p>';
    
    fetch('api/notifications.php?action=count')
    .then(response => response.json())
    .then(data => {
        console.log('API Response:', data);
        
        let html = '<div style="background: #f8f9fa; padding: 10px; border-radius: 4px;">';
        html += '<p><strong>API Response:</strong></p>';
        html += '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
        html += '<p><strong>Success:</strong> ' + (data.success ? 'YES' : 'NO') + '</p>';
        html += '<p><strong>Unread Count:</strong> ' + (data.data?.unread_count || 'N/A') + '</p>';
        html += '</div>';
        
        resultDiv.innerHTML = html;
    })
    .catch(error => {
        console.error('API Error:', error);
        resultDiv.innerHTML = '<p style="color: red;">API Error: ' + error.message + '</p>';
    });
}

// Auto-refresh every 5 seconds
setTimeout(() => {
    location.reload();
}, 5000);
</script>

<style>
.btn {
    cursor: pointer;
    transition: background-color 0.3s;
}
.btn:hover {
    opacity: 0.8;
}
</style>
