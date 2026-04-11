<?php
require_once 'config/database.php';
require_once 'lib/NotificationHelper.php';

echo "=== TEST EMAIL DISABLED ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Email Disabled Test%'");
    $clear_stmt->execute();
    
    // Test 1: Create notification with email enabled (default)
    echo "Test 1: createNotification with email enabled (default)" . PHP_EOL;
    $helper1 = new NotificationHelper();
    $result1 = $helper1->createNotification(
        4, // User ID
        'Email Enabled Test',
        'Email Disabled Test message with email enabled',
        'info',
        999,
        'service_request',
        true // Email enabled
    );
    echo "Result with email enabled: " . ($result1 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Test 2: Create notification with email disabled
    echo PHP_EOL . "Test 2: createNotification with email disabled" . PHP_EOL;
    $helper2 = new NotificationHelper();
    $result2 = $helper2->createNotification(
        4, // User ID
        'Email Disabled Test',
        'Email Disabled Test message with email disabled',
        'info',
        999,
        'service_request',
        false // Email disabled
    );
    echo "Result with email disabled: " . ($result2 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Check notifications
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, created_at 
        FROM notifications 
        WHERE message LIKE '%Email Disabled Test%' 
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
    $email_enabled = array_filter($notifications, function($notif) {
        return strpos($notif['title'], 'Email Enabled') !== false;
    });
    
    $email_disabled = array_filter($notifications, function($notif) {
        return strpos($notif['title'], 'Email Disabled') !== false;
    });
    
    echo "=== ANALYSIS ===" . PHP_EOL;
    echo "Email enabled notifications: " . count($email_enabled) . PHP_EOL;
    echo "Email disabled notifications: " . count($email_disabled) . PHP_EOL;
    
    if (count($email_enabled) == 0 && count($email_disabled) > 0) {
        echo "ISSUE IDENTIFIED: Email sending is preventing notification creation!" . PHP_EOL;
        echo "When email disabled: " . count($email_disabled) . " notifications created" . PHP_EOL;
        echo "When email enabled: " . count($email_enabled) . " notifications created" . PHP_EOL;
    } elseif (count($email_enabled) > 0 && count($email_disabled) > 0) {
        echo "SUCCESS: Both email enabled and disabled notifications work" . PHP_EOL;
    } else {
        echo "ISSUE: Neither email enabled nor disabled notifications work" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
