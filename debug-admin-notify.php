<?php
require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "=== DEBUG ADMIN NOTIFY ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Debug Admin Notify%'");
    $clear_stmt->execute();
    
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    // Get admin users
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    echo "Admin users found: " . count($adminUsers) . PHP_EOL;
    foreach ($adminUsers as $admin) {
        echo "Admin ID: {$admin['id']}, Name: {$admin['full_name']}" . PHP_EOL;
    }
    echo PHP_EOL;
    
    // Test step by step
    echo "Step 1: Create notification manually..." . PHP_EOL;
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $manual_result = $stmt->execute([
        1, // Admin ID
        'Debug Title',
        'Debug Message',
        'info',
        999,
        'service_request'
    ]);
    echo "Manual insert result: " . ($manual_result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    echo PHP_EOL . "Step 2: Test notifyAdminStatusChange..." . PHP_EOL;
    $result = $notificationHelper->notifyAdminStatusChange(
        999,
        'open',
        'in_progress',
        'Test Staff',
        'Debug Test Title'
    );
    echo "notifyAdminStatusChange result: " . ($result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    echo PHP_EOL;
    
    // Check all notifications
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, created_at 
        FROM notifications 
        WHERE message LIKE '%Debug%' 
        ORDER BY created_at DESC
    ");
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== ALL NOTIFICATIONS ===" . PHP_EOL;
    echo "Total notifications: " . count($notifications) . PHP_EOL;
    echo PHP_EOL;
    
    foreach ($notifications as $notif) {
        echo "ID: {$notif['id']}, User: {$notif['user_id']}, Title: {$notif['title']}" . PHP_EOL;
        echo "Message: " . substr($notif['message'], 0, 50) . "..." . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== DEBUG COMPLETE ===" . PHP_EOL;
?>
