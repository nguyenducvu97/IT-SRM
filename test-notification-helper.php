<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

startSession();

echo "=== TEST NOTIFICATION HELPER ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Notification Helper Test%'");
    $clear_stmt->execute();
    
    // Get admin users
    $notificationHelper = new ServiceRequestNotificationHelper();
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    
    echo "Admin users found: " . count($adminUsers) . PHP_EOL;
    foreach ($adminUsers as $admin) {
        echo "Admin ID: {$admin['id']}, Name: {$admin['full_name']}" . PHP_EOL;
    }
    echo PHP_EOL;
    
    // Test notifyAdminStatusChange directly
    echo "Testing notifyAdminStatusChange..." . PHP_EOL;
    $result = $notificationHelper->notifyAdminStatusChange(
        999, // Test request ID
        'open',
        'in_progress',
        'Test Staff',
        'Test Request Title'
    );
    echo "Result: " . ($result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    echo PHP_EOL;
    
    // Check created notifications
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, is_read, created_at 
        FROM notifications 
        WHERE message LIKE '%Notification Helper Test%' 
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
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
