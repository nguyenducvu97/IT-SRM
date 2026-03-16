<?php
require_once 'config/database.php';
require_once 'config/session.php';

startSession();

echo "<h2>Simple API Test</h2>";

// Test basic list API
echo "<h3>Testing list API:</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/service_requests.php?action=list");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
curl_close($ch);

echo "<h4>Raw Response:</h4>";
echo "<pre>" . $response . "</pre>";

echo "<h4>JSON Decode:</h4>";
$data = json_decode($response, true);
if ($data) {
    echo "<pre>" . print_r($data, true) . "</pre>";
} else {
    echo "<p>Failed to decode JSON</p>";
}

echo "<hr>";

// Test with specific user ID
echo "<h3>Testing with user filter:</h3>";
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/service_requests.php?action=list&user_id=17");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
$response = curl_exec($ch);
curl_close($ch);

echo "<h4>Response for user ID 17:</h4>";
echo "<pre>" . $response . "</pre>";

$data = json_decode($response, true);
if ($data && isset($data['requests'])) {
    echo "<p>Found " . count($data['requests']) . " requests for user 17</p>";
    foreach ($data['requests'] as $i => $req) {
        echo "<p>" . ($i+1) . ". ID: {$req['id']}, Title: {$req['title']}, Status: {$req['status']}</p>";
    }
}
?>
