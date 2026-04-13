<?php
// Test pagination API
require_once 'config/database.php';
require_once 'config/session.php';

startSession();

// Test API call
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 9;

echo "<h2>Testing Pagination API</h2>";

// Test 1: Normal list with limit
$url = "api/service_requests.php?action=list&page=$page&limit=$limit";
echo "<h3>Test 1: Normal list with limit</h3>";
echo "<p>URL: $url</p>";

$context = stream_context_create([
    'http' => [
        'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n"
    ]
]);

$response = file_get_contents($url, false, $context);
echo "<pre>" . json_encode(json_decode($response), JSON_PRETTY_PRINT) . "</pre>";

// Test 2: With status filter
$url2 = "api/service_requests.php?action=list&page=$page&limit=$limit&status=in_progress";
echo "<h3>Test 2: With status filter</h3>";
echo "<p>URL: $url2</p>";

$response2 = file_get_contents($url2, false, $context);
echo "<pre>" . json_encode(json_decode($response2), JSON_PRETTY_PRINT) . "</pre>";

// Test 3: Check pagination data structure
echo "<h3>Test 3: Check pagination data</h3>";
$data = json_decode($response, true);
if (isset($data['data']['pagination'])) {
    echo "<p>✅ Pagination data found:</p>";
    echo "<pre>" . json_encode($data['data']['pagination'], JSON_PRETTY_PRINT) . "</pre>";
} else {
    echo "<p>❌ No pagination data found</p>";
    echo "<p>Available keys: " . implode(', ', array_keys($data['data'])) . "</p>";
}
?>
