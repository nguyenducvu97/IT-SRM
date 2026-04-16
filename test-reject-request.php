<?php
session_start();
require_once 'config/database.php';
require_once 'config/session.php';

// Check if user is logged in as staff
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] !== 'staff' && $_SESSION['role'] !== 'admin')) {
    die("Access denied. Staff or admin only.");
}

echo "<h2>Test Reject Request Function</h2>";
echo "<p>User: " . $_SESSION['username'] . " (Role: " . $_SESSION['role'] . ")</p>";

// Test FormData preparation
$testRequestId = 1; // Change this to a valid request ID
$testReason = "Test reject reason";
$testDetails = "Test reject details";

echo "<h3>Test Data:</h3>";
echo "<p>Request ID: $testRequestId</p>";
echo "<p>Reason: $testReason</p>";
echo "<p>Details: $testDetails</p>";

// Simulate FormData
$_POST['request_id'] = $testRequestId;
$_POST['action'] = 'reject_request';
$_POST['reject_reason'] = $testReason;
$_POST['reject_details'] = $testDetails;

echo "<h3>POST Data Simulation:</h3>";
echo "<pre>" . print_r($_POST, true) . "</pre>";

// Test action parsing
$content_type = 'multipart/form-data; boundary=----WebKitFormBoundary';
$_SERVER['CONTENT_TYPE'] = $content_type;

$action = isset($_POST['action']) ? $_POST['action'] : '';
echo "<h3>Action Parsing:</h3>";
echo "<p>Action: '$action'</p>";
echo "<p>Trimmed Action: '" . trim($action) . "'</p>";
echo "<p>Is reject_request: " . (trim($action) == 'reject_request' ? 'YES' : 'NO') . "</p>";

// Test API call
echo "<h3>API Call Test:</h3>";
$api_url = 'http://localhost/it-service-request/api/service_requests.php';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $_POST);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Code: $http_code</p>";
echo "<h4>Response:</h4>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

if ($http_code == 405) {
    echo "<h3 style='color: red;'>Method Not Allowed Error Detected!</h3>";
    echo "<p>This confirms the issue. The API is returning 405 Method Not Allowed.</p>";
}
?>
