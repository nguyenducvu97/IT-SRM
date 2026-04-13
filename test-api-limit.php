<?php
// Simple test to check if API returns exactly 9 requests
require_once 'config/database.php';
require_once 'config/session.php';

startSession();

echo "<h2>🔍 API Limit Test (9 requests per page)</h2>";

// Test page 1 with status=in_progress and limit=9
$url = "api/service_requests.php?action=list&page=1&limit=9&status=in_progress";

echo "<h3>📄 Test URL</h3>";
echo "<p><code>$url</code></p>";

$context = stream_context_create([
    'http' => [
        'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n"
    ]
]);

$response = file_get_contents($url, false, $context);
$data = json_decode($response, true);

echo "<h3>📊 Response Analysis</h3>";

if ($data && $data['success']) {
    $requests = $data['data']['requests'];
    $pagination = $data['data']['pagination'];
    
    echo "<p><strong>✅ Success:</strong> " . count($requests) . " requests returned</p>";
    
    if (count($requests) === 9) {
        echo "<p><strong>✅ PERFECT:</strong> Exactly 9 requests as expected!</p>";
    } else {
        echo "<p><strong>❌ ISSUE:</strong> Expected 9 requests, got " . count($requests) . "</p>";
    }
    
    echo "<h4>📋 Pagination Info:</h4>";
    echo "<pre>" . json_encode($pagination, JSON_PRETTY_PRINT) . "</pre>";
    
    echo "<h4>📄 Request IDs (first 5):</h4>";
    for ($i = 0; $i < min(5, count($requests)); $i++) {
        echo "<p>ID: " . $requests[$i]['id'] . " - " . $requests[$i]['title'] . "</p>";
    }
    
} else {
    echo "<p><strong>❌ API Error:</strong> " . ($data['message'] ?? 'Unknown error') . "</p>";
    echo "<h4>📄 Full Response:</h4>";
    echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
}

echo "<h3>🔍 Server Logs Check</h3>";
echo "<p>Check server error logs for '=== PAGINATION DEBUG ===' messages</p>";
echo "<p>Location: C:/xampp/apache/logs/error.log (or similar)</p>";
?>
