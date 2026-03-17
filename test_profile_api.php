<?php
// Test profile API
session_start();

// Create user session (user ID 17 - staff)
$_SESSION['user_id'] = 17;
$_SESSION['username'] = 'nvnam';
$_SESSION['full_name'] = 'Nguyễn văn tín';
$_SESSION['role'] = 'staff';
$_SESSION['email'] = 'nguyenducvu101223@gmail.com';

echo "Session ID: " . session_id() . "\n";
echo "Session data: ";
print_r($_SESSION);

// Test profile API directly
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'profile';

echo "\nTesting profile API...\n";

// Include profile API
include 'api/profile.php';
?>
