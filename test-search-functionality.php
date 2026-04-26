<?php
// Test Search Functionality
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Search Functionality</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Kiểm tra chức năng tìm kiếm yêu cầu dịch vụ</h1>
    
    <div class="test-section info">
        <h2>📋 Test Plan</h2>
        <ol>
            <li>Kiểm tra API search_requests.php</li>
            <li>Kiểm tra API service_requests.php với search_filter</li>
            <li>Kiểm tra multi-field search</li>
            <li>Kiểm tra role-based filtering</li>
            <li>Kiểm tra combine filters</li>
        </ol>
    </div>

    <?php
    // Test 1: API search_requests.php
    echo '<div class="test-section">';
    echo '<h2>1️⃣ Test API search_requests.php</h2>';
    
    // Simulate API call
    $api_url = 'http://localhost:8000/api/search_requests.php';
    $test_params = [
        'search' => 'test',
        'page' => 1,
        'limit' => 5
    ];
    
    echo '<p><strong>API URL:</strong> ' . $api_url . '</p>';
    echo '<p><strong>Test Parameters:</strong></p>';
    echo '<pre>' . json_encode($test_params, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . '</pre>';
    
    // Check if file exists and is readable
    $search_api_file = __DIR__ . '/api/search_requests.php';
    if (file_exists($search_api_file)) {
        echo '<p class="success">✅ File search_requests.php exists</p>';
        
        // Check syntax
        $output = [];
        $return_var = 0;
        exec('php -l "' . $search_api_file . '" 2>&1', $output, $return_var);
        
        if ($return_var === 0) {
            echo '<p class="success">✅ PHP syntax valid</p>';
        } else {
            echo '<p class="error">❌ PHP syntax error:</p>';
            echo '<pre>' . implode("\n", $output) . '</pre>';
        }
    } else {
        echo '<p class="error">❌ File search_requests.php not found</p>';
    }
    echo '</div>';
    
    // Test 2: API service_requests.php
    echo '<div class="test-section">';
    echo '<h2>2️⃣ Test API service_requests.php với search_filter</h2>';
    
    $service_api_file = __DIR__ . '/api/service_requests.php';
    if (file_exists($service_api_file)) {
        echo '<p class="success">✅ File service_requests.php exists</p>';
        
        // Check for search_filter implementation
        $content = file_get_contents($service_api_file);
        if (strpos($content, 'search_filter') !== false) {
            echo '<p class="success">✅ search_filter parameter found</p>';
        } else {
            echo '<p class="error">❌ search_filter parameter not found</p>';
        }
        
        if (strpos($content, 'LIKE :search') !== false) {
            echo '<p class="success">✅ LIKE search query found</p>';
        } else {
            echo '<p class="error">❌ LIKE search query not found</p>';
        }
    } else {
        echo '<p class="error">❌ File service_requests.php not found</p>';
    }
    echo '</div>';
    
    // Test 3: Multi-field search
    echo '<div class="test-section">';
    echo '<h2>3️⃣ Test Multi-field Search Logic</h2>';
    
    $expected_fields = ['title', 'description', 'username', 'id'];
    $search_query = "WHERE (sr.title LIKE :search OR sr.description LIKE :search OR u.username LIKE :search OR sr.id LIKE :search)";
    
    echo '<p><strong>Expected search fields:</strong></p>';
    echo '<ul>';
    foreach ($expected_fields as $field) {
        echo '<li>✅ ' . $field . '</li>';
    }
    echo '</ul>';
    
    echo '<p><strong>SQL Query:</strong></p>';
    echo '<pre>' . htmlspecialchars($search_query) . '</pre>';
    
    if (file_exists($search_api_file)) {
        $content = file_get_contents($search_api_file);
        if (strpos($content, $search_query) !== false) {
            echo '<p class="success">✅ Multi-field search query implemented</p>';
        } else {
            echo '<p class="error">❌ Multi-field search query not found</p>';
        }
    }
    echo '</div>';
    
    // Test 4: Frontend Implementation
    echo '<div class="test-section">';
    echo '<h2>4️⃣ Test Frontend Implementation</h2>';
    
    $index_file = __DIR__ . '/index.html';
    if (file_exists($index_file)) {
        $content = file_get_contents($index_file);
        
        if (strpos($content, 'requestSearch') !== false) {
            echo '<p class="success">✅ Search input element found</p>';
        } else {
            echo '<p class="error">❌ Search input element not found</p>';
        }
        
        if (strpos($content, 'Tìm kiếm yêu cầu') !== false) {
            echo '<p class="success">✅ Search placeholder fixed</p>';
        } else {
            echo '<p class="error">❌ Search placeholder not found or incorrect</p>';
        }
    }
    
    $app_js_file = __DIR__ . '/assets/js/app.js';
    if (file_exists($app_js_file)) {
        $content = file_get_contents($app_js_file);
        
        if (strpos($content, 'addEventListener') !== false && strpos($content, 'requestSearch') !== false) {
            echo '<p class="success">✅ Search event listener found</p>';
        } else {
            echo '<p class="error">❌ Search event listener not found</p>';
        }
        
        if (strpos($content, 'search_requests.php') !== false) {
            echo '<p class="success">✅ Search API call found</p>';
        } else {
            echo '<p class="error">❌ Search API call not found</p>';
        }
        
        if (strpos($content, 'setTimeout') !== false && strpos($content, '500') !== false) {
            echo '<p class="success">✅ Debounce 500ms found</p>';
        } else {
            echo '<p class="error">❌ Debounce not found or incorrect</p>';
        }
    }
    echo '</div>';
    
    // Test 5: Role-based Filtering
    echo '<div class="test-section">';
    echo '<h2>5️⃣ Test Role-based Filtering</h2>';
    
    if (file_exists($search_api_file)) {
        $content = file_get_contents($search_api_file);
        
        if (strpos($content, 'user_role != \'admin\'') !== false && strpos($content, 'user_role != \'staff\'') !== false) {
            echo '<p class="success">✅ Role-based filtering implemented</p>';
        } else {
            echo '<p class="error">❌ Role-based filtering not found</p>';
        }
        
        if (strpos($content, 'sr.user_id = :user_id') !== false) {
            echo '<p class="success">✅ User ID filter for non-admin/staff found</p>';
        } else {
            echo '<p class="error">❌ User ID filter not found</p>';
        }
    }
    echo '</div>';
    ?>

    <div class="test-section info">
        <h2>🎯 Manual Testing Steps</h2>
        <ol>
            <li>Mở <a href="http://localhost:8000" target="_blank">http://localhost:8000</a></li>
            <li>Đăng nhập với tài khoản admin/staff/user</li>
            <li>Đến trang "Yêu cầu dịch vụ"</li>
            <li>Nhập từ khóa tìm kiếm (ví dụ: "test", "laptop", "printer")</li>
            <li>Kiểm tra kết quả hiển thị</li>
            <li>Test kết hợp với filters (status, priority, category)</li>
            <li>Kiểm tra debounce (chờ 500ms sau khi gõ)</li>
            <li>Kiểm tra role-based access (user chỉ thấy request của mình)</li>
        </ol>
    </div>

    <div class="test-section info">
        <h2>📝 Expected Results</h2>
        <ul>
            <li><strong>Search by title:</strong> Tìm theo tiêu đề yêu cầu</li>
            <li><strong>Search by description:</strong> Tìm theo nội dung mô tả</li>
            <li><strong>Search by username:</strong> Tìm theo tên người tạo</li>
            <li><strong>Search by ID:</strong> Tìm theo số ID yêu cầu</li>
            <li><strong>Combine filters:</strong> Search + status/priority/category</li>
            <li><strong>Debounce:</strong> Chờ 500ms sau khi gõ xong</li>
            <li><strong>Role-based:</strong> User chỉ thấy request của mình</li>
        </ul>
    </div>

</body>
</html>
