<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

startSession();

echo "=== DEBUG NOTIFICATION DUPLICATE ISSUE ===" . PHP_EOL;

// Mock scenario: Admin creates request, Staff accepts
$_SESSION['user_id'] = 2; // Staff
$_SESSION['username'] = 'staff1';
$_SESSION['full_name'] = 'John Smith';
$_SESSION['role'] = 'staff';

echo "Current staff session: " . json_encode($_SESSION) . PHP_EOL;
echo PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Debug Duplicate%'");
    $clear_stmt->execute();
    
    // Simulate request details where admin (user_id = 1) created request
    $request_id = 999;
    $user_id = 1; // Admin user ID
    $assigned_name = 'John Smith';
    
    echo "Simulating scenario:" . PHP_EOL;
    echo "- Request created by admin (user_id = {$user_id})" . PHP_EOL;
    echo "- Staff (user_id = {$_SESSION['user_id']}) accepts request" . PHP_EOL;
    echo "- Both user_id and admin_id are the same: {$user_id}" . PHP_EOL;
    echo PHP_EOL;
    
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    // Test 1: notifyUserRequestInProgress
    echo "Test 1: notifyUserRequestInProgress" . PHP_EOL;
    $result1 = $notificationHelper->notifyUserRequestInProgress(
        $request_id,
        $user_id, // This is admin ID = 1
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
        'Debug Duplicate Test'
    );
    echo "Result: " . ($result2 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    echo PHP_EOL;
    
    // Check created notifications
    echo "=== CREATED NOTIFICATIONS ===" . PHP_EOL;
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, is_read, created_at 
        FROM notifications 
        WHERE message LIKE '%Debug Duplicate%' 
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
    
    // Check for potential duplicates
    $titles = array_count_values(array_column($notifications, 'title'));
    $duplicate_titles = array_filter($titles, function($count) {
        return $count > 1;
    });
    
    echo "=== DUPLICATE ANALYSIS ===" . PHP_EOL;
    if (!empty($duplicate_titles)) {
        echo "⚠️  DUPLICATE TITLES FOUND:" . PHP_EOL;
        foreach ($duplicate_titles as $title => $count) {
            if ($count > 1) {
                echo "- '{$title}': {$count} times" . PHP_EOL;
            }
        }
    } else {
        echo "✅ No duplicate titles found" . PHP_EOL;
    }
    
    // Check for same user_id notifications
    $user_ids = array_column($notifications, 'user_id');
    $unique_user_ids = array_unique($user_ids);
    
    echo PHP_EOL . "User IDs in notifications: " . json_encode($user_ids) . PHP_EOL;
    echo "Unique user IDs: " . json_encode($unique_user_ids) . PHP_EOL;
    
    if (count($user_ids) > count($unique_user_ids)) {
        echo "⚠️  MULTIPLE NOTIFICATIONS TO SAME USER!" . PHP_EOL;
    } else {
        echo "✅ Each notification goes to different user" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== DEBUG COMPLETE ===" . PHP_EOL;
?>
