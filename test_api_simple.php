<?php
// Simple test for API
session_start();

// Mock user session
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyễn Đức Vũ';
$_SESSION['role'] = 'user';

echo "Testing API GET request\n";
echo "======================\n";

// Test GET request for request detail
$url = 'http://localhost/it-service-request/api/service_requests.php?action=get&id=5';

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $http_code\n";
echo "Response:\n";
echo $response . "\n";

if ($http_code == 200) {
    echo "\n✅ API working correctly!\n";
} else {
    echo "\n❌ API error detected\n";
}
?>
