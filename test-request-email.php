<?php
// Test creating a service request to trigger email notification
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate a logged-in admin session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';

// Test data for creating a service request
$test_request_data = [
    'title' => 'Test Email Notification Request',
    'description' => 'This request is created to test if email notifications are sent to admin when users create requests.',
    'category_id' => 1,
    'priority' => 'medium'
];

echo "=== Testing Service Request Creation with Email Notification ===\n\n";

// Use cURL to test the API
$ch = curl_init('http://localhost/it-service-request/api/service_requests.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_request_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

// Add session cookie
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: " . $http_code . "\n";
if ($error) {
    echo "CURL Error: " . $error . "\n";
}
echo "Response: " . $response . "\n\n";

// Check email logs
echo "Checking email logs...\n";
$log_file = __DIR__ . '/logs/email_activity.log';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $recent_logs = substr($logs, -500); // Last 500 characters
    echo "Recent email activity:\n" . $recent_logs . "\n";
} else {
    echo "No email activity log found.\n";
}

echo "\n=== Summary ===\n";
echo "If the request was created successfully but email status shows 'FAILED',\n";
echo "the email system needs PHP mail configuration or an alternative email service.\n";
echo "The request creation itself should work regardless of email status.\n";
?>
