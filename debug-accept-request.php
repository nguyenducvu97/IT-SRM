<?php
// Debug script to check accept_request API call
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Simulate the accept_request API call
$data = [
    'action' => 'accept_request',
    'request_id' => 1 // Change to existing request ID
];

$ch = curl_init('http://localhost/it-service-request/api/service_requests.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: PHPSESSID=test_session' // Add actual session cookie
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>Accept Request API Debug</h2>";
echo "HTTP Code: {$http_code}<br>";
echo "Response: <pre>{$response}</pre>";

// Also test PUT method
echo "<h2>Testing PUT Method</h2>";

$ch2 = curl_init('http://localhost/it-service-request/api/service_requests.php');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch2, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: PHPSESSID=test_session' // Add actual session cookie
]);

$response2 = curl_exec($ch2);
$http_code2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "HTTP Code: {$http_code2}<br>";
echo "Response: <pre>{$response2}</pre>";
?>
