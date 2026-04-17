<?php
// Simple API test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple API Test</h1>";

// Test POST request to categories API
echo "<h2>Testing POST to categories.php</h2>";

// Start session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Session started for admin<br>";

// Test data
$data = [
    'name' => 'Test Category ' . date('H:i:s'),
    'description' => 'Test description'
];

$json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
echo "JSON data: " . htmlspecialchars($json_data) . "<br>";

// Use cURL to test
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/categories.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json; charset=UTF-8'
]);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
curl_setopt($ch, CURLOPT_VERBOSE, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "CURL Error: " . ($error ?: 'None') . "<br>";
echo "Response: " . htmlspecialchars($response) . "<br>";

// Parse response
$result = json_decode($response, true);
if ($result) {
    echo "<h3>Result:</h3>";
    echo "Success: " . ($result['success'] ? 'YES' : 'NO') . "<br>";
    echo "Message: " . htmlspecialchars($result['message']) . "<br>";
    if ($result['success']) {
        echo "Category ID: " . $result['data']['id'] . "<br>";
    }
} else {
    echo "<h3>Invalid JSON response</h3>";
}

// Test GET request
echo "<h2>Testing GET to categories.php</h2>";

$ch2 = curl_init();
curl_setopt($ch2, CURLOPT_URL, 'http://localhost/it-service-request/api/categories.php');
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());

$get_response = curl_exec($ch2);
$get_http_code = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "GET HTTP Code: $get_http_code<br>";
echo "GET Response: " . htmlspecialchars(substr($get_response, 0, 500)) . "...<br>";

$get_result = json_decode($get_response, true);
if ($get_result) {
    echo "GET Success: " . ($get_result['success'] ? 'YES' : 'NO') . "<br>";
    if ($get_result['success'] && isset($get_result['data'])) {
        echo "Categories count: " . count($get_result['data']) . "<br>";
    }
}
?>
