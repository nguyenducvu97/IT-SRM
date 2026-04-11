<?php
require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "=== TEST ACTUAL MESSAGE ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Yêu câu #999%'");
    $clear_stmt->execute();
    
    // Test 1: Create notification with actual Vietnamese message
    echo "Test 1: Create notification with actual Vietnamese message" . PHP_EOL;
    
    $title = "Yêu câu dang duoc xu ly";
    $message = "Yêu câu #999 cua ban da duoc nhan vien IT tiep nhan va dang xu ly. Nhan vien phu trach: Test Staff Name";
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $result = $stmt->execute([4, $title, $message, 'info', 999, 'service_request']);
    echo "Manual insert with Vietnamese message: " . ($result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
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
    
    // Test 3: Check if the issue is with Vietnamese characters
    echo PHP_EOL . "Test 3: Check Vietnamese character handling" . PHP_EOL;
    
    // Create a simple English message
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%English Test%'");
    $clear_stmt->execute();
    
    $english_result = $notificationHelper->notifyUserRequestInProgress(
        1000,
        4,
        'Test Staff Name'
    );
    echo "English message result: " . ($english_result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Check English notification
    $english_notif = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE message LIKE '%Yêu câu #1000%'
    ");
    $english_notif->execute();
    $english_count = $english_notif->fetchColumn();
    
    echo "English message notifications created: {$english_count}" . PHP_EOL;
    
    // Test 4: Check database encoding
    echo PHP_EOL . "Test 4: Database encoding check" . PHP_EOL;
    $charset_stmt = $pdo->query("SHOW VARIABLES LIKE 'character_set_database'");
    $charset = $charset_stmt->fetch(PDO::FETCH_ASSOC);
    echo "Database charset: " . $charset['Value'] . PHP_EOL;
    
    $collation_stmt = $pdo->query("SHOW VARIABLES LIKE 'collation_database'");
    $collation = $collation_stmt->fetch(PDO::FETCH_ASSOC);
    echo "Database collation: " . $collation['Value'] . PHP_EOL;
    
    // Check notifications table charset
    $table_charset_stmt = $pdo->query("
        SELECT CCSA.character_set_name, CCSA.collation_name 
        FROM information_schema.`TABLES` T, 
             information_schema.`COLLATION_CHARACTER_SET_APPLICABILITY` CCSA 
        WHERE CCSA.collation_name = T.table_collation 
        AND T.table_schema = DATABASE() 
        AND T.table_name = 'notifications'
    ");
    $table_charset = $table_charset_stmt->fetch(PDO::FETCH_ASSOC);
    echo "Notifications table charset: " . $table_charset['character_set_name'] . PHP_EOL;
    echo "Notifications table collation: " . $table_charset['collation_name'] . PHP_EOL;
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
