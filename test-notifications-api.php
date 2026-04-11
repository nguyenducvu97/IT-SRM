<?php
session_start();
$_SESSION['user_id'] = 1; // Simulate logged in user
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

// Test notifications API
echo "=== TESTING NOTIFICATIONS API ===" . PHP_EOL;

// Test count endpoint
echo PHP_EOL . "1. Testing count endpoint:" . PHP_EOL;
$context = stream_context_create([
    'http' => [
        'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] ?? ''
    ]
]);

$response = file_get_contents('http://localhost/it-service-request/api/notifications.php?action=count', false, $context);
echo "Response: " . $response . PHP_EOL;

// Test list endpoint
echo PHP_EOL . "2. Testing list endpoint:" . PHP_EOL;
$response = file_get_contents('http://localhost/it-service-request/api/notifications.php?action=get', false, $context);
echo "Response: " . $response . PHP_EOL;

echo PHP_EOL . "=== END TEST ===" . PHP_EOL;
?>
