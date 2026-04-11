<?php
echo "=== TEST API LIST ===" . PHP_EOL;

// Mock session for testing
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';

echo "Session set for admin user" . PHP_EOL;

// Test the API
$_GET['action'] = 'list';

require_once 'api/service_requests.php';

echo "API test completed" . PHP_EOL;
?>
