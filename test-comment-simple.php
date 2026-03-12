<?php
// Direct test without session
$data = [
    'service_request_id' => 31,
    'comment' => 'Test comment from admin'
];

$ch = curl_init('http://localhost/it-service-request/api/comments.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

header('Content-Type: text/plain');
echo "HTTP Status: $http_code\n";
echo "Response: $response\n";

// Also check if function exists
if (function_exists('commentsJsonResponse')) {
    echo "\nFunction commentsJsonResponse exists\n";
} else {
    echo "\nFunction commentsJsonResponse does NOT exist\n";
}
?>
