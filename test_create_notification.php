<?php
// Test creating notification for admin
session_start();

// Create admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';
$_SESSION['email'] = 'admin@test.com';

echo "Session ID: " . session_id() . "\n";

// Create notification
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'create';
$_POST['user_id'] = 1;
$_POST['title'] = 'Test Notification';
$_POST['message'] = 'This is a test notification for admin';
$_POST['type'] = 'info';

echo "Creating test notification...\n";

// Include notifications API
include 'api/notifications.php';
?>
