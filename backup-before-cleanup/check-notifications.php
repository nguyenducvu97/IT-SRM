<?php
// Check notifications in database
require_once 'config/database.php';

$db = (new Database())->getConnection();

echo "<h1>Notifications Database Check</h1>";

// Check recent notifications
$stmt = $db->query("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Recent Notifications (Last 10):</h2>";
if (!empty($notifications)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Related ID</th><th>Related Type</th><th>Created</th></tr>";
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['user_id']}</td>";
        echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
        echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>{$notif['related_id']}</td>";
        echo "<td>{$notif['related_type']}</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No notifications found in database</p>";
}

// Check notifications for request #140 specifically
echo "<h2>Notifications for Request #140:</h2>";
$stmt = $db->prepare("SELECT * FROM notifications WHERE related_id = 140 AND related_type = 'service_request'");
$stmt->execute([140]);
$request_notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($request_notifications)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
    foreach ($request_notifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['user_id']}</td>";
        echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
        echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>No notifications found for request #140</p>";
}

// Check users
echo "<h2>Users in System:</h2>";
$stmt = $db->query("SELECT id, username, full_name, role FROM users ORDER BY role, full_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th></tr>";
foreach ($users as $user) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td>{$user['full_name']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "</tr>";
}
echo "</table>";

// Check request #140 details
echo "<h2>Request #140 Details:</h2>";
$stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ?");
$stmt->execute([140]);
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
} else {
    echo "<p style='color: red;'>Request #140 not found</p>";
}

// Check PHP error logs for recent notification-related errors
echo "<h2>Recent PHP Error Logs:</h2>";
$logFile = 'C:/xampp/apache/logs/php_error_log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLines = array_slice($lines, -20); // Last 20 lines
    
    echo "<div style='font-family: monospace; font-size: 12px; background-color: #f8f9fa; padding: 10px; border-radius: 4px;'>";
    foreach ($recentLines as $line) {
        if (strpos($line, 'NOTIFICATIONS:') !== false || strpos($line, 'EMAIL:') !== false || strpos($line, 'accept_request') !== false) {
            echo "<p style='color: blue; margin: 2px 0;'>" . htmlspecialchars($line) . "</p>";
        }
    }
    echo "</div>";
} else {
    echo "<p>PHP error log not found</p>";
}

echo "<hr>";
echo "<h2>Debug Steps:</h2>";
echo "<ol>";
echo "<li>Check if there are any notifications created recently</li>";
echo "<li>Check if request #140 exists and its status</li>";
echo "<li>Check if users exist (staff, admin, regular user)</li>";
echo "<li>Check PHP error logs for notification-related errors</li>";
echo "<li>Test the actual accept request flow in browser</li>";
echo "</ol>";

echo "<p><a href='test-accept-web.php'>Test Accept Request in Browser</a></p>";
echo "<p><a href='index.html'>Main Application</a></p>";
?>
