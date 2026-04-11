<?php
// Test notifications
require_once 'config/database.php';
require_once 'config/session.php';
startSession();

$database = new Database();
$pdo = $database->getConnection();

echo "=== NOTIFICATIONS DEBUG ===" . PHP_EOL;

// Check if notifications table exists
$stmt = $pdo->query("SHOW TABLES LIKE 'notifications'");
$table = $stmt->fetch();
if ($table) {
    echo "✅ Notifications table exists" . PHP_EOL;
    
    // Count total notifications
    $stmt = $pdo->query('SELECT COUNT(*) as total FROM notifications');
    $result = $stmt->fetch();
    echo "Total notifications: " . $result['total'] . PHP_EOL;
    
    // Count unread notifications
    $stmt = $pdo->query('SELECT COUNT(*) as unread FROM notifications WHERE is_read = 0');
    $result = $stmt->fetch();
    echo "Unread notifications: " . $result['unread'] . PHP_EOL;
    
    // Show recent notifications
    $stmt = $pdo->query('SELECT id, title, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 5');
    $notifications = $stmt->fetchAll();
    
    echo PHP_EOL . "Recent notifications:" . PHP_EOL;
    foreach ($notifications as $notif) {
        echo "- ID: {$notif['id']}, Title: {$notif['title']}, Read: " . ($notif['is_read'] ? 'Yes' : 'No') . ", Created: {$notif['created_at']}" . PHP_EOL;
    }
} else {
    echo "❌ Notifications table does not exist" . PHP_EOL;
}

echo PHP_EOL . "=== SESSION DEBUG ===" . PHP_EOL;
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . PHP_EOL;
echo "Username: " . ($_SESSION['username'] ?? 'Not set') . PHP_EOL;
echo "Role: " . ($_SESSION['role'] ?? 'Not set') . PHP_EOL;
?>
