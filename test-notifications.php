<?php
// Test notifications API
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

echo "Session data: ";
print_r($_SESSION);
echo "<br>";

// Test API call
$ch = curl_init('http://localhost/it-service-request/api/notifications.php?action=list');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "HTTP Status: $http_code<br>";
echo "CURL Error: $curl_error<br>";
echo "Response: <pre>" . htmlspecialchars($response) . "</pre>";
?>
