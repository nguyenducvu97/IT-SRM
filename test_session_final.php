<?php
// Simple session test
session_start();

// Clear any existing session
session_unset();
session_destroy();

// Start new session
session_start();

// Create admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';
$_SESSION['email'] = 'admin@test.com';

echo "Session ID: " . session_id() . "\n";
echo "Session data: ";
print_r($_SESSION);

// Test API by including service_requests.php
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get';
$_GET['id'] = 43;

echo "\nTesting API...\n";
include __DIR__ . '/api/service_requests.php';
?>
