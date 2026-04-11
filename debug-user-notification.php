<?php
require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "=== DEBUG USER NOTIFICATION ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Debug User Notification%'");
    $clear_stmt->execute();
    
    // Test 1: Check user ID 4 exists
    $user_check = $pdo->prepare("SELECT id, username, role FROM users WHERE id = 4");
    $user_check->execute();
    $user = $user_check->fetch(PDO::FETCH_ASSOC);
    
    echo "User ID 4: " . ($user ? "EXISTS - {$user['username']} ({$user['role']})" : "NOT FOUND") . PHP_EOL;
    
    // Test 2: Create notification manually for user ID 4
    echo PHP_EOL . "Test 1: Manual notification for user ID 4" . PHP_EOL;
    $manual_stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
        VALUES (?, 'Debug Title', 'Debug User Notification message', 'info', 999, 'service_request')
    ");
    $manual_result = $manual_stmt->execute([4]);
    echo "Manual insert result: " . ($manual_result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Test 3: Test notifyUserRequestInProgress
    echo PHP_EOL . "Test 2: notifyUserRequestInProgress for user ID 4" . PHP_EOL;
    $notificationHelper = new ServiceRequestNotificationHelper();
    $result = $notificationHelper->notifyUserRequestInProgress(
        999,
        4, // User ID 4
        'Test Staff Name'
    );
    echo "notifyUserRequestInProgress result: " . ($result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Test 4: Check all notifications
    echo PHP_EOL . "Checking notifications..." . PHP_EOL;
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, created_at 
        FROM notifications 
        WHERE message LIKE '%Debug User Notification%' 
        ORDER BY created_at DESC
    ");
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total notifications: " . count($notifications) . PHP_EOL;
    foreach ($notifications as $notif) {
        echo "- ID: {$notif['id']}, User: {$notif['user_id']}, Title: {$notif['title']}" . PHP_EOL;
    }
    
    // Test 5: Check if there are any errors in notification creation
    echo PHP_EOL . "Test 3: Direct NotificationHelper test" . PHP_EOL;
    require_once 'lib/NotificationHelper.php';
    $directHelper = new NotificationHelper();
    
    $direct_result = $directHelper->createNotification(
        4, // User ID 4
        'Direct Test Title',
        'Direct Debug User Notification message',
        'info',
        999,
        'service_request'
    );
    echo "Direct NotificationHelper result: " . ($direct_result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Check final notifications
    $final_check = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE message LIKE '%Debug User Notification%'
    ");
    $final_check->execute();
    $final_count = $final_check->fetchColumn();
    
    echo PHP_EOL . "Final notification count: {$final_count}" . PHP_EOL;
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== DEBUG COMPLETE ===" . PHP_EOL;
?>
