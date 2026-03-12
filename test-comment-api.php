<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

$data = [
    'service_request_id' => 31,
    'comment' => 'Test comment from admin'
];

$ch = curl_init('http://localhost/it-service-request/api/comments.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . session_name() . '=' . session_id()
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Status: $http_code\n";
echo "Response: $response\n";
?>
