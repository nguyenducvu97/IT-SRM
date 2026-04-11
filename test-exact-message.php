<?php
require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "=== TEST EXACT MESSAGE ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Yêu câu #999%'");
    $clear_stmt->execute();
    
    // Test 1: Create notification with exact message format
    echo "Test 1: Create notification with exact message format" . PHP_EOL;
    $title = "Yêu câu dang duoc xu ly";
    $message = "Yêu câu #999 cua ban da duoc nhan vien IT tiep nhan va dang xu ly. Nhan vien phu trach: Test Staff Name";
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([4, $title, $message, 'info', 999, 'service_request']);
    echo "Manual insert with exact message: " . ($result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Test 2: Call notifyUserRequestInProgress
    echo PHP_EOL . "Test 2: notifyUserRequestInProgress" . PHP_EOL;
    $notificationHelper = new ServiceRequestNotificationHelper();
    $result2 = $notificationHelper->notifyUserRequestInProgress(
        999,
        4,
        'Test Staff Name'
    );
    echo "notifyUserRequestInProgress result: " . ($result2 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Check notifications
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, created_at 
        FROM notifications 
        WHERE message LIKE '%Yêu câu #999%' 
        ORDER BY created_at DESC
    ");
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo PHP_EOL . "Notifications found: " . count($notifications) . PHP_EOL;
    foreach ($notifications as $notif) {
        echo "- ID: {$notif['id']}, User: {$notif['user_id']}" . PHP_EOL;
        echo "  Title: {$notif['title']}" . PHP_EOL;
        echo "  Message: " . $notif['message'] . PHP_EOL;
        echo "---" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
