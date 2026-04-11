<?php
require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "=== DEBUG NOTIFICATION CREATION ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Debug Creation%'");
    $clear_stmt->execute();
    
    // Test 1: Check what notifyUserRequestInProgress actually does
    echo "Test 1: Step-by-step notification creation" . PHP_EOL;
    
    $requestId = 999;
    $userId = 4;
    $assignedStaffName = 'Test Staff Name';
    
    // Build the exact message that notifyUserRequestInProgress creates
    $title = "Yêu câu dang duoc xu ly";
    $message = "Yêu câu #{$requestId} cua ban da duoc nhan vien IT tiep nhan va dang xu ly." . 
               ($assignedStaffName ? " Nhan vien phu trach: {$assignedStaffName}" : "");
    
    echo "Title: '{$title}'" . PHP_EOL;
    echo "Message: '{$message}'" . PHP_EOL;
    echo "User ID: {$userId}" . PHP_EOL;
    echo "Request ID: {$requestId}" . PHP_EOL;
    echo PHP_EOL;
    
    // Test 2: Create notification using NotificationHelper directly
    echo "Test 2: Direct NotificationHelper call" . PHP_EOL;
    require_once 'lib/NotificationHelper.php';
    $directHelper = new NotificationHelper();
    
    $direct_result = $directHelper->createNotification(
        $userId,
        $title,
        $message,
        'info',
        $requestId,
        'service_request'
    );
    echo "Direct NotificationHelper result: " . ($direct_result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Test 3: Check what was created
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, related_id, related_type, created_at 
        FROM notifications 
        WHERE message LIKE '%Debug Creation%' 
        ORDER BY created_at DESC
    ");
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo PHP_EOL . "Notifications created: " . count($notifications) . PHP_EOL;
    foreach ($notifications as $notif) {
        echo "- ID: {$notif['id']}, User: {$notif['user_id']}" . PHP_EOL;
        echo "  Title: '{$notif['title']}'" . PHP_EOL;
        echo "  Message: '{$notif['message']}'" . PHP_EOL;
        echo "  Type: {$notif['type']}, Related ID: {$notif['related_id']}" . PHP_EOL;
        echo "---" . PHP_EOL;
    }
    
    // Test 4: Call notifyUserRequestInProgress and capture any issues
    echo PHP_EOL . "Test 3: notifyUserRequestInProgress call" . PHP_EOL;
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    // Enable error reporting temporarily
    $old_error_reporting = error_reporting(E_ALL);
    ini_set('display_errors', 1);
    
    $result = $notificationHelper->notifyUserRequestInProgress(
        $requestId,
        $userId,
        $assignedStaffName
    );
    
    // Restore error reporting
    error_reporting($old_error_reporting);
    ini_set('display_errors', 0);
    
    echo "notifyUserRequestInProgress result: " . ($result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Check for new notifications
    $new_notif_stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE message LIKE '%Debug Creation%'
    ");
    $new_notif_stmt->execute();
    $new_count = $new_notif_stmt->fetchColumn();
    
    echo "Total notifications after call: {$new_count}" . PHP_EOL;
    
    // Test 5: Check if there are any special characters or encoding issues
    echo PHP_EOL . "Test 4: Character encoding check" . PHP_EOL;
    echo "Title length: " . strlen($title) . PHP_EOL;
    echo "Message length: " . strlen($message) . PHP_EOL;
    echo "Title UTF-8: " . (mb_check_encoding($title, 'UTF-8') ? 'VALID' : 'INVALID') . PHP_EOL;
    echo "Message UTF-8: " . (mb_check_encoding($message, 'UTF-8') ? 'VALID' : 'INVALID') . PHP_EOL;
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== DEBUG COMPLETE ===" . PHP_EOL;
?>
