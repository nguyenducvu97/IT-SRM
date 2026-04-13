<?php
// Test pagination with 9 items per page
require_once 'config/database.php';
require_once 'config/session.php';

startSession();

echo "<h2>🔍 Pagination Verification Test (9 items per page)</h2>";

// Test different pages
$pages_to_test = [1, 2, 3, 4];
$limit = 9;

foreach ($pages_to_test as $page) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
    echo "<h3>📄 Trang $page</h3>";
    
    $url = "api/service_requests.php?action=list&page=$page&limit=$limit&status=in_progress";
    echo "<p><strong>URL:</strong> $url</p>";
    
    $context = stream_context_create([
        'http' => [
            'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n"
        ]
    ]);
    
    $response = file_get_contents($url, false, $context);
    $data = json_decode($response, true);
    
    if ($data && $data['success']) {
        $requests = $data['data']['requests'];
        $pagination = $data['data']['pagination'];
        
        echo "<p><strong>✅ Số lượng yêu cầu:</strong> " . count($requests) . "</p>";
        echo "<p><strong>📊 Pagination:</strong></p>";
        echo "<pre>" . json_encode($pagination, JSON_PRETTY_PRINT) . "</pre>";
        
        // Display first few requests for verification
        echo "<p><strong>📋 Yêu cầu đầu tiên:</strong></p>";
        if (count($requests) > 0) {
            $first_request = $requests[0];
            echo "<p>ID: {$first_request['id']} - {$first_request['title']}</p>";
        }
        
        if (count($requests) > 1) {
            $last_request = $requests[count($requests) - 1];
            echo "<p><strong>📋 Yêu cầu cuối cùng:</strong></p>";
            echo "<p>ID: {$last_request['id']} - {$last_request['title']}</p>";
        }
    } else {
        echo "<p><strong>❌ Lỗi:</strong> " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
    
    echo "</div>";
}

echo "<h2>📈 Summary</h2>";
echo "<p>✅ Mỗi trang nên có tối đa 9 yêu cầu</p>";
echo "<p>✅ Trang khác nhau nên có các yêu cầu khác nhau</p>";
echo "<p>✅ Pagination info nên chính xác</p>";
?>
