<?php
// Create valid session and test API
session_start();

// Create valid admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';
$_SESSION['email'] = 'admin@test.com';

echo "<h2>Session Created</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test API call
$url = 'http://localhost/it-service-request/api/service_requests.php?action=get&id=43';
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

echo "<h2>API Response</h2>";
echo "<h3>Headers:</h3>";
echo "<pre>" . htmlspecialchars($headers) . "</pre>";
echo "<h3>Body:</h3>";
echo "<pre>" . htmlspecialchars($body) . "</pre>";

curl_close($ch);
?>
