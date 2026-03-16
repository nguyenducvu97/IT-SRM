<?php
// Test API directly with absolute path
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

// Test API with absolute path to service_requests.php
echo "\nTesting API with absolute path...\n";

// Set up server variables
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get';
$_GET['id'] = 43;

// Define absolute path to API file
$apiFile = __DIR__ . '/api/service_requests.php';

echo "API file: $apiFile\n";
echo "File exists: " . (file_exists($apiFile) ? "Yes" : "No") . "\n";

if (file_exists($apiFile)) {
    echo "Including API...\n";
    include $apiFile;
} else {
    echo "API file not found!\n";
}
?>
