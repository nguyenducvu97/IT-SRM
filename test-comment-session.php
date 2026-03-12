<?php
// Test with proper session
session_start();

// Set admin session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

echo "Session set: ";
print_r($_SESSION);
echo "<br>";

// Create test data
$data = [
    'service_request_id' => 31,
    'comment' => 'Test comment from admin at ' . date('Y-m-d H:i:s')
];

echo "Test data: " . json_encode($data) . "<br>";

// Use cURL with session cookie
$ch = curl_init('http://localhost/it-service-request/api/comments.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $http_code<br>";
echo "CURL Error: $curl_error<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
?>
