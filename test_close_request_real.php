<?php
// Test close request functionality
require_once 'config/session.php';

// Start session first
startSession();

// Mock authenticated user
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyễn Đức Vũ';
$_SESSION['role'] = 'user';

// Mock PUT request data
$_SERVER['REQUEST_METHOD'] = 'PUT';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simulate JSON input
$json_data = json_encode([
    'action' => 'close_request',
    'request_id' => 5,
    'rating' => 5,
    'feedback' => 'Great service! Very satisfied.',
    'software_feedback' => 'The system is easy to use.',
    'would_recommend' => 'yes',
    'ease_of_use' => 5,
    'speed_stability' => 4,
    'requirement_meeting' => 5
]);

// Mock php://input
file_put_contents('php://temp', $json_data);
stream_wrapper_register('php', 'PhpStreamWrapper');
$_SERVER['REQUEST_URI'] = '/api/service_requests.php';

echo "Testing Close Request API...\n";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . $_SESSION['user_id'] . "\n";
echo "User Role: " . $_SESSION['role'] . "\n";
echo "Request Method: " . $_SERVER['REQUEST_METHOD'] . "\n";
echo "Request Data: $json_data\n";

// Capture API output
ob_start();
include 'api/service_requests.php';
$output = ob_get_clean();

echo "API Response: $output\n";
?>
