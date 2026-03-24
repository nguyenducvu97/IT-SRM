<?php
// Test API GET request properly
require_once 'config/session.php';

// Start session first
startSession();

// Mock authenticated user
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyễn Đức Vũ';
$_SESSION['role'] = 'user';

// Mock GET request
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get';
$_GET['id'] = '5';

echo "Testing API GET request...\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User Role: " . $_SESSION['role'] . "\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";

// Capture API output
ob_start();
include 'api/service_requests.php';
$output = ob_get_clean();

echo "API Response: $output\n";
?>
