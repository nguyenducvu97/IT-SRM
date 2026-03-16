<?php
// Test API calls
echo "<h2>Testing API Calls</h2>";

// Test list requests
echo "<h3>1. Testing list requests API</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/service_requests.php?action=list");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
curl_close($ch);
echo "<pre>" . $response . "</pre>";

// Test request detail
echo "<h3>2. Testing request detail API</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/service_requests.php?action=detail&id=43");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
curl_close($ch);
echo "<pre>" . $response . "</pre>";

// Test reject request API
echo "<h3>3. Testing reject request API</h3>";
$post_data = [
    'action' => 'reject_request',
    'request_id' => 43,
    'reject_reason' => 'Test reject reason',
    'reject_details' => 'Test reject details'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/reject_request.php");
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
curl_close($ch);
echo "<pre>" . $response . "</pre>";
?>
