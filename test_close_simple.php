<?php
// Test close request with manual input
require_once 'config/session.php';

// Start session first
startSession();

// Mock authenticated user
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyễn Đức Vũ';
$_SESSION['role'] = 'user';

// Mock PUT request
$_SERVER['REQUEST_METHOD'] = 'PUT';

// Manually set the input data that API expects
$input = [
    'action' => 'close_request',
    'request_id' => 5,
    'rating' => 5,
    'feedback' => 'Great service! Very satisfied.',
    'software_feedback' => 'The system is easy to use.',
    'would_recommend' => 'yes',
    'ease_of_use' => 5,
    'speed_stability' => 4,
    'requirement_meeting' => 5
];

echo "Testing Close Request API...\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User Role: " . $_SESSION['role'] . "\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Request Data: " . json_encode($input) . "\n";

// We need to modify the API to accept our test input
// Let's create a simplified test
$api_file = file_get_contents('api/service_requests.php');
$api_file = str_replace('json_decode(file_get_contents(\'php://input\'), true)', json_encode($input), $api_file);

// Save to temp file and include
file_put_contents('temp_service_requests.php', $api_file);

// Capture API output
ob_start();
include 'temp_service_requests.php';
$output = ob_get_clean();

echo "API Response: $output\n";

// Clean up
unlink('temp_service_requests.php');
?>
