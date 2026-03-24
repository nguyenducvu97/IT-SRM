<?php
// Test script for close request functionality
session_start();

// Mock user session for testing
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'user1';
$_SESSION['full_name'] = 'Test User';
$_SESSION['role'] = 'user';

echo "Testing Close Request Functionality\n";
echo "====================================\n\n";

// Test data
$test_data = [
    'action' => 'close_request',
    'request_id' => 1, // Assuming there's a request with ID 1
    'rating' => 5,
    'feedback' => 'Great service! Very satisfied.',
    'software_feedback' => 'The system is easy to use.',
    'would_recommend' => 'yes',
    'ease_of_use' => 5,
    'speed_stability' => 4,
    'requirement_meeting' => 5
];

// Convert to JSON
$json_data = json_encode($test_data);

echo "Test Data:\n";
echo $json_data . "\n\n";

// Use cURL to test the API
$ch = curl_init('http://localhost/it-service-request/api/service_requests.php');

curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($json_data)
]);

// Add session cookie
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

echo "Sending request...\n";
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

echo "HTTP Status: $http_code\n";
echo "Response:\n";
echo $response . "\n\n";

curl_close($ch);

echo "Test completed.\n";
?>
