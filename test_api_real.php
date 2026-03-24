<?php
// Test real API with proper session
require_once 'config/session.php';

// Start session first
startSession();

// Mock authenticated user
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyễn Đức Vũ';
$_SESSION['role'] = 'user';

// Now test API
$_GET['action'] = 'get';
$_GET['id'] = '5';

echo "Testing API with proper session...\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User Role: " . $_SESSION['role'] . "\n";

// Capture API output
ob_start();
include 'api/service_requests.php';
$output = ob_get_clean();

echo "API Response: $output\n";
?>
