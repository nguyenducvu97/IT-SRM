<?php
// Test notifications API directly
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

// Test notifications API directly
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'list';

echo "\nTesting notifications API...\n";

// Include notifications API
include 'api/notifications.php';
?>
