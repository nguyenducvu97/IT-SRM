<?php
// Test auth API endpoint after fix
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Test registration endpoint
echo "Testing registration endpoint...\n";

// Create test data
$test_data = [
    'action' => 'register',
    'username' => 'testuser' . time(),
    'email' => 'test' . time() . '@example.com',
    'password' => '123456',
    'full_name' => 'Test User',
    'department' => 'IT',
    'phone' => '123456789'
];

// Use cURL to test the API
$ch = curl_init('http://localhost/it-service-request/api/auth.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: " . $http_code . "\n";
if ($error) {
    echo "CURL Error: " . $error . "\n";
}
echo "Response: " . $response . "\n\n";

// Test login endpoint with existing user
echo "Testing login endpoint...\n";

$login_data = [
    'action' => 'login',
    'username' => 'admin',  // Assuming admin exists
    'password' => 'admin123'  // Common default password
];

$ch = curl_init('http://localhost/it-service-request/api/auth.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($login_data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: " . $http_code . "\n";
if ($error) {
    echo "CURL Error: " . $error . "\n";
}
echo "Response: " . $response . "\n\n";

// Test session check endpoint
echo "Testing session check endpoint...\n";

$ch = curl_init('http://localhost/it-service-request/api/auth.php?action=check_session');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: " . $http_code . "\n";
if ($error) {
    echo "CURL Error: " . $error . "\n";
}
echo "Response: " . $response . "\n";
?>
