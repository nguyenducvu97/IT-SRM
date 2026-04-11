<?php
echo "=== TEST GET ACCEPT REQUEST ===" . PHP_EOL;

// Mock session for staff user
session_start();
$_SESSION['user_id'] = 2;
$_SESSION['username'] = 'johnsmith';
$_SESSION['full_name'] = 'John Smith';
$_SESSION['role'] = 'staff';

echo "Session set for staff user ID: " . $_SESSION['user_id'] . PHP_EOL;

// Create a test request first
require_once 'config/database.php';
$database = new Database();
$pdo = $database->getConnection();

$pdo->exec("INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at) VALUES (4, 'GET Test Request', 'Test request for GET method', 1, 'medium', 'open', NOW())");
$request_id = $pdo->lastInsertId();
echo "Created test request #$request_id" . PHP_EOL;

// Test GET accept_request
$_GET['action'] = 'accept_request';
$_GET['request_id'] = $request_id;

echo "Testing GET accept_request for request #$request_id" . PHP_EOL;

// Capture output
ob_start();
require_once 'api/service_requests.php';
$response = ob_get_clean();

echo "API Response: " . $response . PHP_EOL;

// Check if notifications were created
$stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE related_id = ? AND related_type = 'service_request'");
$stmt->execute([$request_id]);
$notification_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo "Notifications created for request #$request_id: $notification_count" . PHP_EOL;

// Check specific notifications
$stmt = $pdo->prepare("SELECT user_id, title, message FROM notifications WHERE related_id = ? AND related_type = 'service_request' ORDER BY created_at DESC");
$stmt->execute([$request_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Notification details:" . PHP_EOL;
foreach ($notifications as $notif) {
    echo "User ID: {$notif['user_id']}, Title: {$notif['title']}, Message: {$notif['message']}" . PHP_EOL;
}

// Clean up
$pdo->prepare("DELETE FROM notifications WHERE related_id = ?")->execute([$request_id]);
$pdo->prepare("DELETE FROM service_requests WHERE id = ?")->execute([$request_id]);

echo "Test cleaned up" . PHP_EOL;
echo "=== TEST COMPLETE ===" . PHP_EOL;
?>
