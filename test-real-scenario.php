<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

startSession();

echo "=== REAL SCENARIO TEST ===" . PHP_EOL;

// Clear existing notifications
$pdo = getDatabaseConnection();
$clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Real Scenario%'");
$clear_stmt->execute();

// Scenario 1: Admin creates request, Staff accepts
echo "Scenario 1: Admin creates request, Staff accepts" . PHP_EOL;

// Mock admin session for creating request
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';

// Create a test request
$create_stmt = $pdo->prepare("
    INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at, updated_at) 
    VALUES (1, 'Real Scenario Test', 'Test request for notification debugging', 1, 'medium', 'open', NOW(), NOW())
");
$create_stmt->execute();
$request_id = $pdo->lastInsertId();
echo "Created request #{$request_id} by admin (user_id = 1)" . PHP_EOL;

// Now switch to staff session and accept the request
$_SESSION['user_id'] = 2;
$_SESSION['username'] = 'staff1';
$_SESSION['full_name'] = 'John Smith';
$_SESSION['role'] = 'staff';

// Update request to in_progress and assign to staff
$update_stmt = $pdo->prepare("
    UPDATE service_requests 
    SET assigned_to = 2, status = 'in_progress', assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
    WHERE id = ?
");
$update_stmt->execute([$request_id]);

echo "Staff (user_id = 2) accepted request #{$request_id}" . PHP_EOL;

// Get request details for notifications
$request_query = "SELECT sr.*, u.full_name as requester_name, staff.full_name as assigned_name 
                 FROM service_requests sr 
                 LEFT JOIN users u ON sr.user_id = u.id 
                 LEFT JOIN users staff ON sr.assigned_to = staff.id 
                 WHERE sr.id = ?";
$request_stmt = $pdo->prepare($request_query);
$request_stmt->execute([$request_id]);
$request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);

echo "Request details:" . PHP_EOL;
echo "- Requester user_id: {$request_data['user_id']}" . PHP_EOL;
echo "- Assigned staff user_id: {$request_data['assigned_to']}" . PHP_EOL;
echo "- Requester name: {$request_data['requester_name']}" . PHP_EOL;
echo "- Assigned name: {$request_data['assigned_name']}" . PHP_EOL;
echo PHP_EOL;

// Send notifications using the same logic as service_requests.php
$notificationHelper = new ServiceRequestNotificationHelper();

// Call notifyUserRequestInProgress
echo "Calling notifyUserRequestInProgress..." . PHP_EOL;
$result1 = $notificationHelper->notifyUserRequestInProgress(
    $request_id,
    $request_data['user_id'], // This should be 1 (admin)
    $request_data['assigned_name']
);
echo "Result: " . ($result1 ? "SUCCESS" : "FAILED") . PHP_EOL;

// Call notifyAdminStatusChange
echo PHP_EOL . "Calling notifyAdminStatusChange..." . PHP_EOL;
$result2 = $notificationHelper->notifyAdminStatusChange(
    $request_id,
    'open',
    'in_progress',
    $request_data['assigned_name'],
    $request_data['title']
);
echo "Result: " . ($result2 ? "SUCCESS" : "FAILED") . PHP_EOL;

echo PHP_EOL;

// Check created notifications
$notif_stmt = $pdo->prepare("
    SELECT id, user_id, title, message, type, is_read, created_at 
    FROM notifications 
    WHERE message LIKE '%Real Scenario%' 
    ORDER BY created_at DESC
");
$notif_stmt->execute();
$notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "=== NOTIFICATIONS CREATED ===" . PHP_EOL;
echo "Total notifications: " . count($notifications) . PHP_EOL;
echo PHP_EOL;

foreach ($notifications as $notif) {
    echo "Notification ID: {$notif['id']}" . PHP_EOL;
    echo "User ID: {$notif['user_id']}" . PHP_EOL;
    echo "Title: {$notif['title']}" . PHP_EOL;
    echo "Message: " . $notif['message'] . PHP_EOL;
    echo "Type: {$notif['type']}" . PHP_EOL;
    echo "---" . PHP_EOL;
}

echo PHP_EOL;

// Analysis
$user_notifications = array_filter($notifications, function($notif) use ($request_data) {
    return $notif['user_id'] == $request_data['user_id']; // Should be user_id = 1
});

$admin_notifications = array_filter($notifications, function($notif) use ($request_data) {
    return $notif['user_id'] != $request_data['user_id']; // Should be admin users
});

echo "=== ANALYSIS ===" . PHP_EOL;
echo "User (requester) notifications: " . count($user_notifications) . PHP_EOL;
echo "Admin notifications: " . count($admin_notifications) . PHP_EOL;

if (count($user_notifications) == 1 && count($admin_notifications) == 1) {
    echo "✅ CORRECT: 1 notification for user, 1 for admin" . PHP_EOL;
} else {
    echo "❌ INCORRECT: User notifications: " . count($user_notifications) . ", Admin notifications: " . count($admin_notifications) . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
