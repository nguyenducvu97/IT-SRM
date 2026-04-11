<?php
require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "=== TEST ADMIN NOTIFY DIRECT ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Direct Admin Test%'");
    $clear_stmt->execute();
    
    // Test 1: Check admin users
    $notificationHelper = new ServiceRequestNotificationHelper();
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    
    echo "Admin users found: " . count($adminUsers) . PHP_EOL;
    foreach ($adminUsers as $admin) {
        echo "- ID: {$admin['id']}, Name: {$admin['full_name']}" . PHP_EOL;
    }
    echo PHP_EOL;
    
    // Test 2: Call notifyAdminStatusChange directly
    echo "Testing notifyAdminStatusChange directly..." . PHP_EOL;
    $result = $notificationHelper->notifyAdminStatusChange(
        999,
        'open',
        'in_progress',
        'Test Staff Name',
        'Direct Admin Test Request'
    );
    echo "Result: " . ($result ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    echo PHP_EOL;
    
    // Test 3: Check notifications
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, created_at 
        FROM notifications 
        WHERE message LIKE '%Direct Admin Test%' 
        ORDER BY created_at DESC
    ");
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Notifications created: " . count($notifications) . PHP_EOL;
    foreach ($notifications as $notif) {
        echo "- ID: {$notif['id']}, User: {$notif['user_id']}, Title: {$notif['title']}" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Test 4: Check if admin user exists in database
    $admin_check = $pdo->prepare("SELECT id, username, role FROM users WHERE role = 'admin'");
    $admin_check->execute();
    $admin_db_users = $admin_check->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Admin users in database: " . count($admin_db_users) . PHP_EOL;
    foreach ($admin_db_users as $admin) {
        echo "- ID: {$admin['id']}, Username: {$admin['username']}, Role: {$admin['role']}" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
