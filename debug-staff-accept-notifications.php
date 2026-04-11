<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

startSession();

echo "=== DEBUG STAFF ACCEPT NOTIFICATIONS ===" . PHP_EOL;

// Mock staff session
$_SESSION['user_id'] = 2;
$_SESSION['username'] = 'staff1';
$_SESSION['full_name'] = 'John Smith';
$_SESSION['role'] = 'staff';

echo "Current session: " . json_encode($_SESSION) . PHP_EOL;
echo PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications for this test
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Test Staff Accept%'");
    $clear_stmt->execute();
    
    echo "Cleared existing test notifications" . PHP_EOL;
    
    // Simulate staff accepting request #112
    $request_id = 112;
    $user_id = 1; // Admin who created the request
    $assigned_name = 'John Smith';
    $request_title = 'Test Staff Accept Notification';
    
    echo "Simulating staff accept request #{$request_id}" . PHP_EOL;
    echo "Request created by user ID: {$user_id}" . PHP_EOL;
    echo "Assigned to: {$assigned_name}" . PHP_EOL;
    echo PHP_EOL;
    
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    // Test 1: notifyUserRequestInProgress
    echo "Test 1: notifyUserRequestInProgress" . PHP_EOL;
    $result1 = $notificationHelper->notifyUserRequestInProgress(
        $request_id,
        $user_id,
        $assigned_name
    );
    echo "Result: " . ($result1 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Test 2: notifyAdminStatusChange
    echo PHP_EOL . "Test 2: notifyAdminStatusChange" . PHP_EOL;
    $result2 = $notificationHelper->notifyAdminStatusChange(
        $request_id,
        'open',
        'in_progress',
        $assigned_name,
        $request_title
    );
    echo "Result: " . ($result2 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    echo PHP_EOL;
    
    // Check created notifications
    echo "=== CREATED NOTIFICATIONS ===" . PHP_EOL;
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, is_read, created_at 
        FROM notifications 
        WHERE message LIKE '%Test Staff Accept%' 
        ORDER BY created_at DESC
    ");
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total notifications created: " . count($notifications) . PHP_EOL;
    echo PHP_EOL;
    
    foreach ($notifications as $notif) {
        echo "Notification ID: {$notif['id']}" . PHP_EOL;
        echo "User ID: {$notif['user_id']}" . PHP_EOL;
        echo "Title: {$notif['title']}" . PHP_EOL;
        echo "Message: {$notif['message']}" . PHP_EOL;
        echo "Type: {$notif['type']}" . PHP_EOL;
        echo "Created: {$notif['created_at']}" . PHP_EOL;
        echo "---" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Check for duplicates
    $user_notifications = array_filter($notifications, function($notif) use ($user_id) {
        return $notif['user_id'] == $user_id;
    });
    
    $admin_notifications = array_filter($notifications, function($notif) {
        return $notif['user_id'] != $user_id; // Assuming admin has different ID
    });
    
    echo "=== ANALYSIS ===" . PHP_EOL;
    echo "User notifications: " . count($user_notifications) . PHP_EOL;
    echo "Admin notifications: " . count($admin_notifications) . PHP_EOL;
    
    if (count($admin_notifications) > 1) {
        echo "⚠️  ISSUE: Admin received " . count($admin_notifications) . " notifications!" . PHP_EOL;
    } else {
        echo "✅ Admin received 1 notification (correct)" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo "=== DEBUG COMPLETE ===" . PHP_EOL;
?>
