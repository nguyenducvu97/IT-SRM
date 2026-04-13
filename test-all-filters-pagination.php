<?php
// Test pagination with all filters
require_once 'config/database.php';
require_once 'config/session.php';

startSession();

echo "<h2>Filters Pagination Test (9 items per page)</h2>";

$filters = [
    ['name' => 'Tình trang: Mô', 'params' => 'status=open&limit=9'],
    ['name' => 'Tình trang: Dang xl', 'params' => 'status=in_progress&limit=9'],
    ['name' => 'Tình trang: Da giai', 'params' => 'status=resolved&limit=9'],
    ['name' => 'Tình trang: Tu choi', 'params' => 'status=rejected&limit=9'],
    ['name' => 'Tình trang: Dong', 'params' => 'status=closed&limit=9'],
    ['name' => 'Uu tien: Cao', 'params' => 'priority=high&limit=9'],
    ['name' => 'Uu tien: Trung binh', 'params' => 'priority=medium&limit=9'],
    ['name' => 'Uu tien: Thap', 'params' => 'priority=low&limit=9'],
    ['name' => 'Search: "test"', 'params' => 'search=test&limit=9'],
    ['name' => 'Khong loc', 'params' => 'limit=9']
];

foreach ($filters as $filter) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
    echo "<h3> {$filter['name']}</h3>";
    
    // Test page 1
    $url = "api/service_requests.php?action=list&page=1&{$filter['params']}";
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
        
        echo "<p><strong>S luong:</strong> " . count($requests) . "</p>";
        
        if (count($requests) <= 9) {
            echo "<p><strong> Pagination:</strong> {$pagination['page']}/{$pagination['total_pages']} (Tong: {$pagination['total']})</p>";
        } else {
            echo "<p><strong> LOI:</strong> Qua 9 yeu cau!</p>";
        }
        
        // Test page 2 if exists
        if ($pagination['total_pages'] > 1) {
            $url2 = "api/service_requests.php?action=list&page=2&{$filter['params']}";
            $response2 = file_get_contents($url2, false, $context);
            $data2 = json_decode($response2, true);
            
            if ($data2 && $data2['success']) {
                $requests2 = $data2['data']['requests'];
                echo "<p><strong>Trang 2:</strong> " . count($requests2) . " yeu cau</p>";
            }
        }
    } else {
        echo "<p><strong> LOI:</strong> " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
    
    echo "</div>";
}

echo "<h2>Search API Test</h2>";

$search_terms = ['test', 'request', 'user', 'computer'];

foreach ($search_terms as $term) {
    echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
    echo "<h3>Search: \"$term\"</h3>";
    
    $url = "api/search_requests.php?search=$term&page=1&limit=9";
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
        
        echo "<p><strong>S luong:</strong> " . count($requests) . "</p>";
        echo "<p><strong> Pagination:</strong> {$pagination['page']}/{$pagination['total_pages']} (Tong: {$pagination['total']})</p>";
    } else {
        echo "<p><strong> LOI:</strong> " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
    
    echo "</div>";
}

echo "<h2> Summary</h2>";
echo "<p>  Bo loc deu phai co 9 yeu cau/trang</p>";
echo "<p>  Pagination phai hoat dong</p>";
echo "<p>  Search API phai hoat dong</p>";
?>
