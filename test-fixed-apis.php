<?php
// Test all fixed API endpoints
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing Fixed API Endpoints ===\n\n";

// Test categories API
echo "1. Testing Categories API...\n";
$ch = curl_init('http://localhost/it-service-request/api/categories.php');
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
echo "Response: " . substr($response, 0, 200) . "...\n\n";

// Test departments dropdown API (public)
echo "2. Testing Departments Dropdown API (public)...\n";
$ch = curl_init('http://localhost/it-service-request/api/departments.php?action=dropdown');
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
echo "Response: " . substr($response, 0, 200) . "...\n\n";

// Test service requests API (should return unauthorized without session)
echo "3. Testing Service Requests API (without session)...\n";
$ch = curl_init('http://localhost/it-service-request/api/service_requests.php?action=list');
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
echo "Response: " . substr($response, 0, 200) . "...\n\n";

// Test departments API (should return unauthorized without session)
echo "4. Testing Departments API (without session)...\n";
$ch = curl_init('http://localhost/it-service-request/api/departments.php?action=get');
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
echo "Response: " . substr($response, 0, 200) . "...\n\n";

echo "=== Test Complete ===\n";
echo "Expected results:\n";
echo "- Categories API: 200 OK (public access)\n";
echo "- Departments Dropdown: 200 OK (public access)\n";
echo "- Service Requests: 401 Unauthorized (requires session)\n";
echo "- Departments API: 401 Unauthorized (requires session + admin)\n";
?>
