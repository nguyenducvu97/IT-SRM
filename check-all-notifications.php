<?php
require_once 'config/database.php';

echo "=== CHECK ALL NOTIFICATIONS ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Check all recent notifications
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, created_at 
        FROM notifications 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
        ORDER BY created_at DESC
    ");
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "All notifications in last 5 minutes: " . count($notifications) . PHP_EOL;
    echo PHP_EOL;
    
    foreach ($notifications as $notif) {
        $user_type = "Unknown";
        if ($notif['user_id'] == 4) {
            $user_type = "Regular User";
        } elseif ($notif['user_id'] == 1) {
            $user_type = "Admin";
        } elseif ($notif['user_id'] == 2) {
            $user_type = "Staff";
        }
        
        echo "Notification ID: {$notif['id']}" . PHP_EOL;
        echo "User ID: {$notif['user_id']} ({$user_type})" . PHP_EOL;
        echo "Title: {$notif['title']}" . PHP_EOL;
        echo "Message: " . $notif['message'] . PHP_EOL;
        echo "Created: {$notif['created_at']}" . PHP_EOL;
        echo "---" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Check specifically for user notifications
    $user_notif_stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE user_id = 4 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $user_notif_stmt->execute();
    $user_count = $user_notif_stmt->fetchColumn();
    
    echo "User notifications (user_id=4) in last 5 minutes: {$user_count}" . PHP_EOL;
    
    // Check specifically for admin notifications
    $admin_notif_stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE user_id = 1 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 5 MINUTE)
    ");
    $admin_notif_stmt->execute();
    $admin_count = $admin_notif_stmt->fetchColumn();
    
    echo "Admin notifications (user_id=1) in last 5 minutes: {$admin_count}" . PHP_EOL;
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== CHECK COMPLETE ===" . PHP_EOL;
?>
