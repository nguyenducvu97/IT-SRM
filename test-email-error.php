<?php
require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "=== TEST EMAIL ERROR ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    // Test with email enabled (default)
    echo "Test 1: notifyAdminStatusChange with email enabled (default)" . PHP_EOL;
    $result1 = $notificationHelper->notifyAdminStatusChange(
        999,
        'open',
        'in_progress',
        'Test Staff',
        'Test Request Title'
    );
    echo "Result: " . ($result1 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Check notifications
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE message LIKE '%Test Staff%'");
    $count1 = $stmt->fetchColumn();
    echo "Notifications created: {$count1}" . PHP_EOL;
    
    // Test with email disabled
    echo PHP_EOL . "Test 2: notifyAdminStatusChange with email disabled" . PHP_EOL;
    $result2 = $notificationHelper->notifyAdminStatusChange(
        999,
        'open',
        'in_progress',
        'Test Staff 2',
        'Test Request Title 2',
        null, // This will disable email
        null,
        false // This will disable email
    );
    echo "Result: " . ($result2 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Check notifications
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM notifications WHERE message LIKE '%Test Staff 2%'");
    $count2 = $stmt->fetchColumn();
    echo "Notifications created: {$count2}" . PHP_EOL;
    
    echo PHP_EOL . "=== ANALYSIS ===" . PHP_EOL;
    if ($count1 == 0 && $count2 > 0) {
        echo "✅ ISSUE IDENTIFIED: Email was causing notification creation to fail" . PHP_EOL;
        echo "When email enabled: {$count1} notifications" . PHP_EOL;
        echo "When email disabled: {$count2} notifications" . PHP_EOL;
    } else {
        echo "❓ No email-related issues detected" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
