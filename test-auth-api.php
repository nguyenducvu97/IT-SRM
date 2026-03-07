<?php
// Test auth API endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate POST request
$_SERVER['REQUEST_METHOD'] = 'POST';

// Simulate JSON input
$json_data = '{
    "action": "register",
    "username": "testuser2",
    "email": "test2@example.com",
    "password": "123456",
    "full_name": "Test User 2",
    "department": "IT",
    "phone": "123456789"
}';

// Put the JSON data into php://input
file_put_contents('php://temp', $json_data);

// Override the file_get_contents to use our test data
function mock_file_get_contents($filename) {
    if ($filename === 'php://input') {
        return $json_data;
    }
    return file_get_contents($filename);
}

// Test the auth.php logic
echo "Testing auth.php registration...\n";

// Include the auth.php file
include 'api/auth.php';
?>
