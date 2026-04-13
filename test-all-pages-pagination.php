<?php
// Test pagination for all 3 pages: Requests, Support, Reject
require_once 'config/database.php';
require_once 'config/session.php';

startSession();

echo "<h2>🔍 All Pages Pagination Test (9 items per page)</h2>";

// Test 1: Service Requests (normal)
echo "<div style='border: 2px solid #007bff; margin: 10px; padding: 10px;'>";
echo "<h3>📋 Service Requests (Normal)</h3>";

$url1 = "api/service_requests.php?action=list&page=1&limit=9&status=in_progress";
echo "<p><strong>URL:</strong> $url1</p>";

$context = stream_context_create([
    'http' => [
        'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n"
    ]
]);

$response1 = file_get_contents($url1, false, $context);
$data1 = json_decode($response1, true);

if ($data1 && $data1['success']) {
    $requests1 = $data1['data']['requests'];
    $pagination1 = $data1['data']['pagination'];
    
    echo "<p><strong>✅ Success:</strong> " . count($requests1) . " requests</p>";
    echo "<p><strong>📊 Pagination:</strong> Page {$pagination1['page']}/{$pagination1['total_pages']} (Total: {$pagination1['total']})</p>";
} else {
    echo "<p><strong>❌ Error:</strong> " . ($data1['message'] ?? 'Unknown error') . "</p>";
}
echo "</div>";

// Test 2: Support Requests
echo "<div style='border: 2px solid #28a745; margin: 10px; padding: 10px;'>";
echo "<h3>🛠️ Support Requests</h3>";

$url2 = "api/support_requests.php?action=list&page=1&limit=9&status=approved";
echo "<p><strong>URL:</strong> $url2</p>";

$response2 = file_get_contents($url2, false, $context);
$data2 = json_decode($response2, true);

if ($data2 && $data2['success']) {
    $requests2 = $data2['data'];
    $pagination2 = $data2['pagination'];
    
    echo "<p><strong>✅ Success:</strong> " . count($requests2) . " requests</p>";
    echo "<p><strong>📊 Pagination:</strong> Page {$pagination2['page']}/{$pagination2['pages']} (Total: {$pagination2['total']})</p>";
} else {
    echo "<p><strong>❌ Error:</strong> " . ($data2['message'] ?? 'Unknown error') . "</p>";
}
echo "</div>";

// Test 3: Reject Requests
echo "<div style='border: 2px solid #dc3545; margin: 10px; padding: 10px;'>";
echo "<h3>❌ Reject Requests</h3>";

$url3 = "api/reject_requests.php?action=list&page=1&limit=9&status=pending";
echo "<p><strong>URL:</strong> $url3</p>";

$response3 = file_get_contents($url3, false, $context);
$data3 = json_decode($response3, true);

if ($data3 && $data3['success']) {
    $requests3 = $data3['data']['reject_requests'];
    $pagination3 = $data3['data']['pagination'];
    
    echo "<p><strong>✅ Success:</strong> " . count($requests3) . " requests</p>";
    echo "<p><strong>📊 Pagination:</strong> Page {$pagination3['page']}/{$pagination3['pages']} (Total: {$pagination3['total']})</p>";
} else {
    echo "<p><strong>❌ Error:</strong> " . ($data3['message'] ?? 'Unknown error') . "</p>";
}
echo "</div>";

// Test pagination for page 2
echo "<h2>📄 Page 2 Test</h2>";

$page2_tests = [
    ['name' => 'Service Requests', 'url' => 'api/service_requests.php?action=list&page=2&limit=9&status=in_progress'],
    ['name' => 'Support Requests', 'url' => 'api/support_requests.php?action=list&page=2&limit=9&status=approved'],
    ['name' => 'Reject Requests', 'url' => 'api/reject_requests.php?action=list&page=2&limit=9&status=pending']
];

foreach ($page2_tests as $test) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
    echo "<h4>{$test['name']} - Page 2</h4>";
    
    $response = file_get_contents($test['url'], false, $context);
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        if ($test['name'] === 'Service Requests') {
            $requests = $data['data']['requests'];
            $pagination = $data['data']['pagination'];
        } elseif ($test['name'] === 'Support Requests') {
            $requests = $data['data'];
            $pagination = $data['pagination'];
        } else {
            $requests = $data['data']['reject_requests'];
            $pagination = $data['data']['pagination'];
        }
        
        echo "<p><strong>✅ Page 2:</strong> " . count($requests) . " requests</p>";
        echo "<p><strong>📊 Pagination:</strong> Page {$pagination['page']}/{$pagination['pages'] ?? $pagination['total_pages']}</p>";
    } else {
        echo "<p><strong>❌ Error:</strong> " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
    echo "</div>";
}

echo "<h2>🎯 Expected Results</h2>";
echo "<ul>";
echo "<li>✅ All pages should show max 9 requests per page</li>";
echo "<li>✅ All pages should have pagination data</li>";
echo "<li>✅ Page 2 should show different requests than page 1</li>";
echo "<li>✅ Total counts should be accurate</li>";
echo "</ul>";
?>
