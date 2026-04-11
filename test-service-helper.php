<?php
require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "=== TEST SERVICE HELPER ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Service Helper Test%'");
    $clear_stmt->execute();
    
    // Test 1: Check ServiceRequestNotificationHelper constructor
    echo "Test 1: ServiceRequestNotificationHelper constructor" . PHP_EOL;
    $serviceHelper = new ServiceRequestNotificationHelper();
    echo "ServiceRequestNotificationHelper created successfully" . PHP_EOL;
    
    // Test 2: Direct call to notifyUserRequestInProgress
    echo PHP_EOL . "Test 2: notifyUserRequestInProgress direct call" . PHP_EOL;
    $result1 = $serviceHelper->notifyUserRequestInProgress(
        999,
        4,
        'Test Staff Name'
    );
    echo "notifyUserRequestInProgress result: " . ($result1 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Test 3: Manual call to NotificationHelper through ServiceRequestNotificationHelper
    echo PHP_EOL . "Test 3: Manual NotificationHelper call" . PHP_EOL;
    
    // Access the internal NotificationHelper (if possible)
    $reflection = new ReflectionClass($serviceHelper);
    $notificationHelperProperty = $reflection->getProperty('notificationHelper');
    $notificationHelperProperty->setAccessible(true);
    $internalHelper = $notificationHelperProperty->getValue($serviceHelper);
    
    echo "Got internal NotificationHelper" . PHP_EOL;
    
    $result2 = $internalHelper->createNotification(
        4,
        'Service Helper Test',
        'Service Helper Test message via internal helper',
        'info',
        999,
        'service_request'
    );
    echo "Internal NotificationHelper result: " . ($result2 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Test 4: Check notifications
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, created_at 
        FROM notifications 
        WHERE message LIKE '%Service Helper Test%' 
        ORDER BY created_at DESC
    ");
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo PHP_EOL . "Notifications created: " . count($notifications) . PHP_EOL;
    foreach ($notifications as $notif) {
        echo "- ID: {$notif['id']}, User: {$notif['user_id']}, Title: {$notif['title']}" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Analysis
    $service_method = array_filter($notifications, function($notif) {
        return strpos($notif['message'], 'Service Helper Test message') !== false;
    });
    
    $internal_method = array_filter($notifications, function($notif) {
        return strpos($notif['message'], 'via internal helper') !== false;
    });
    
    echo "=== ANALYSIS ===" . PHP_EOL;
    echo "Service method notifications: " . count($service_method) . PHP_EOL;
    echo "Internal method notifications: " . count($internal_method) . PHP_EOL;
    
    if (count($service_method) == 0 && count($internal_method) > 0) {
        echo "ISSUE: notifyUserRequestInProgress not working, but internal NotificationHelper works" . PHP_EOL;
    } elseif (count($service_method) > 0 && count($internal_method) > 0) {
        echo "SUCCESS: Both methods work" . PHP_EOL;
    } else {
        echo "ISSUE: Neither method works" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
