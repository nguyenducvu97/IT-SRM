<?php
echo "=== TEST COUNT API ===" . PHP_EOL;

// Mock session for user ID 4
session_start();
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyeen Duc Vu';
$_SESSION['role'] = 'user';

echo "Session set for user ID: " . $_SESSION['user_id'] . PHP_EOL;

// Test API call
$_GET['action'] = 'count';

require_once 'api/notifications.php';

echo "API test completed" . PHP_EOL;

// Also test direct database query
require_once 'config/database.php';
$database = new Database();
$pdo = $database->getConnection();

$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
$stmt->execute([4]);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Direct database count: " . $result['count'] . PHP_EOL;

// Check all notifications for user
$stmt = $pdo->prepare("SELECT id, is_read FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 10");
$stmt->execute([4]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Recent notifications for user 4:" . PHP_EOL;
foreach ($notifications as $notif) {
    echo "ID: {$notif['id']}, Read: " . ($notif['is_read'] ? 'YES' : 'NO') . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
