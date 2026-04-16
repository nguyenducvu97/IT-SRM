<?php
/**
 * Test Staff Accept Request Notification
 * Verifies that when staff accepts a request, both user and admin receive notifications
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "<h1>🧪 TEST STAFF ACCEPT REQUEST NOTIFICATION</h1>";
echo "<style>
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; }
    .warning { background-color: #fff3cd; border-color: #ffeaa7; }
</style>";

// Mock session for staff
startSession();
$_SESSION['user_id'] = 2; // Staff user ID
$_SESSION['role'] = 'staff';
$_SESSION['full_name'] = 'Test Staff';
$_SESSION['username'] = 'staff';

$db = getDatabaseConnection();
$notificationHelper = new ServiceRequestNotificationHelper();

echo "<div class='section info'>";
echo "<h2>1. TEST SETUP</h2>";

// Get a test request that can be accepted
$stmt = $db->prepare("SELECT * FROM service_requests WHERE status = 'open' LIMIT 1");
$stmt->execute();
$testRequest = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$testRequest) {
    echo "❌ No open requests found. Creating a test request...<br>";
    $stmt = $db->prepare("
        INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at) 
        VALUES (1, 'Test Request for Staff Accept', 'This is a test request for staff acceptance', 1, 'medium', 'open', NOW())
    ");
    $stmt->execute();
    $testRequest = [
        'id' => $db->lastInsertId(),
        'user_id' => 1,
        'title' => 'Test Request for Staff Accept',
        'status' => 'open'
    ];
}

echo "✅ Using test request #{$testRequest['id']}: {$testRequest['title']}<br>";
echo "✅ Request owner: User ID {$testRequest['user_id']}<br>";
echo "✅ Current status: {$testRequest['status']}<br>";

// Get staff info
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$staff = $stmt->fetch(PDO::FETCH_ASSOC);
$staffName = $staff['full_name'];

echo "✅ Staff accepting: {$staffName} (ID: {$_SESSION['user_id']})<br>";

// Get admin users
$stmt = $db->prepare("SELECT * FROM users WHERE role = 'admin'");
$stmt->execute();
$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "✅ Found " . count($admins) . " admin(s) to notify<br>";

echo "</div>";

echo "<div class='section info'>";
echo "<h2>2. SIMULATE STAFF ACCEPT REQUEST</h2>";

try {
    // Update request status to in_progress and assign to staff
    $updateStmt = $db->prepare("
        UPDATE service_requests 
        SET status = 'in_progress', assigned_to = ?, updated_at = NOW() 
        WHERE id = ?
    ");
    $updateResult = $updateStmt->execute([$_SESSION['user_id'], $testRequest['id']]);
    
    if ($updateResult) {
        echo "✅ Request updated to 'in_progress' and assigned to staff<br>";
        
        // Send notifications using the same logic as the fixed code
        $request_id = $testRequest['id'];
        $request_data = $testRequest;
        $assigned_to = $_SESSION['user_id'];
        $staff_name = $staffName;
        
        echo "<h3>Sending Notifications...</h3>";
        
        // Notify user that request is in progress
        echo "📤 Notifying user (ID: {$request_data['user_id']})...<br>";
        $userResult = $notificationHelper->notifyUserRequestInProgress(
            $request_id, 
            $request_data['user_id'], 
            $staff_name
        );
        echo ($userResult ? "✅" : "❌") . " User notification: " . ($userResult ? "SUCCESS" : "FAILED") . "<br>";
        
        // Notify admin about status change
        echo "📤 Notifying admins...<br>";
        $adminResult = $notificationHelper->notifyAdminStatusChange(
            $request_id, 
            'open', 
            'in_progress', 
            $staff_name, 
            $request_data['title']
        );
        echo ($adminResult ? "✅" : "❌") . " Admin notification: " . ($adminResult ? "SUCCESS" : "FAILED") . "<br>";
        
    } else {
        echo "❌ Failed to update request status<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

echo "</div>";

echo "<div class='section info'>";
echo "<h2>3. VERIFICATION</h2>";

// Check notifications created in last minute
$stmt = $db->prepare("
    SELECT n.*, u.full_name as user_name, u.role as user_role 
    FROM notifications n 
    LEFT JOIN users u ON n.user_id = u.id 
    WHERE n.created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)
    ORDER BY n.created_at DESC
");
$stmt->execute();
$recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Recent Notifications (Last Minute):</h3>";
if (!empty($recentNotifications)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User</th><th>Role</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
    foreach ($recentNotifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['user_name']}</td>";
        echo "<td>{$notif['user_role']}</td>";
        echo "<td>{$notif['title']}</td>";
        echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 80)) . "...</td>";
        echo "<td>{$notif['type']}</td>";
        echo "<td>{$notif['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Verify specific notifications
    $userNotifFound = false;
    $adminNotifFound = false;
    
    foreach ($recentNotifications as $notif) {
        if ($notif['user_role'] === 'user' && strpos($notif['title'], 'đang được xử lý') !== false) {
            $userNotifFound = true;
        }
        if ($notif['user_role'] === 'admin' && strpos($notif['title'], 'Thay đổi trạng thái') !== false) {
            $adminNotifFound = true;
        }
    }
    
    echo "<h3>Verification Results:</h3>";
    echo ($userNotifFound ? "✅" : "❌") . " User received 'in progress' notification<br>";
    echo ($adminNotifFound ? "✅" : "❌") . " Admin received 'status change' notification<br>";
    
} else {
    echo "❌ No recent notifications found<br>";
}

// Check request status
$stmt = $db->prepare("SELECT status, assigned_to FROM service_requests WHERE id = ?");
$stmt->execute([$testRequest['id']]);
$updatedRequest = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Request Status:</h3>";
echo "✅ Status: {$updatedRequest['status']}<br>";
echo "✅ Assigned to: {$updatedRequest['assigned_to']}<br>";

echo "</div>";

echo "<div class='section " . ($userNotifFound && $adminNotifFound ? 'success' : 'error') . "'>";
echo "<h2>4. FINAL RESULT</h2>";

if ($userNotifFound && $adminNotifFound) {
    echo "<h3>🎉 SUCCESS! Staff accept request notifications working perfectly!</h3>";
    echo "<ul>";
    echo "<li>✅ User receives notification when staff accepts request</li>";
    echo "<li>✅ Admin receives notification about status change</li>";
    echo "<li>✅ Request status updated to 'in_progress'</li>";
    echo "<li>✅ Request assigned to staff member</li>";
    echo "</ul>";
} else {
    echo "<h3>❌ ISSUES FOUND</h3>";
    echo "<p>The staff acceptance notification logic needs further investigation.</p>";
}

echo "</div>";
?>
