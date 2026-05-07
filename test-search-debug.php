<?php
// Test Search Functionality
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test Search Functionality</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { color: green; }
        .error { color: red; }
        .info { color: blue; }
        pre { background: #f5f5f5; padding: 10px; overflow-x: auto; }
    </style>
</head>
<body>
    <h1>🔍 Test Search Functionality</h1>
    
    <div class="test-section">
        <h2>1. Test Search API Directly</h2>
        <p>Testing the search API endpoint with different search terms...</p>
        
        <?php
        // Test 1: Check if search API file exists
        $api_file = __DIR__ . '/api/search_requests.php';
        if (file_exists($api_file)) {
            echo '<p class="success">✅ search_requests.php file exists</p>';
        } else {
            echo '<p class="error">❌ search_requests.php file NOT found</p>';
        }
        
        // Test 2: Test API call with curl
        echo '<h3>API Test Results:</h3>';
        
        // Test search with different terms
        $test_terms = ['test', 'yêu cầu', 'admin', ''];
        
        foreach ($test_terms as $term) {
            echo "<div class='info'>";
            echo "<h4>Testing search term: '" . htmlspecialchars($term) . "'</h4>";
            
            $ch = curl_init();
            $url = 'http://localhost/it-service-request/api/search_requests.php?search=' . urlencode($term) . '&limit=5';
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . ($_COOKIE['PHPSESSID'] ?? 'test'));
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            echo "<p><strong>URL:</strong> " . htmlspecialchars($url) . "</p>";
            echo "<p><strong>HTTP Code:</strong> $http_code</p>";
            
            if ($error) {
                echo "<p class='error'><strong>cURL Error:</strong> " . htmlspecialchars($error) . "</p>";
            } else {
                echo "<p><strong>Response:</strong></p>";
                echo "<pre>" . htmlspecialchars($response) . "</pre>";
                
                // Try to parse JSON
                $data = json_decode($response, true);
                if ($data) {
                    echo "<p class='success'>✅ Valid JSON response</p>";
                    if (isset($data['success']) && $data['success']) {
                        $total = $data['data']['pagination']['total'] ?? 0;
                        echo "<p class='success'>✅ Found $total requests</p>";
                    } else {
                        echo "<p class='error'>❌ API returned error: " . htmlspecialchars($data['message'] ?? 'Unknown error') . "</p>";
                    }
                } else {
                    echo "<p class='error'>❌ Invalid JSON response</p>";
                }
            }
            echo "</div><hr>";
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>2. Check UI Elements</h2>
        <p>Checking if search input exists in the main page...</p>
        
        <?php
        $index_file = __DIR__ . '/index.html';
        if (file_exists($index_file)) {
            $content = file_get_contents($index_file);
            
            if (strpos($content, 'id="requestSearch"') !== false) {
                echo '<p class="success">✅ requestSearch input found in index.html</p>';
            } else {
                echo '<p class="error">❌ requestSearch input NOT found in index.html</p>';
            }
            
            if (strpos($content, 'placeholder="Tìm kiếm yêu cầu"') !== false) {
                echo '<p class="success">✅ Search placeholder found</p>';
            } else {
                echo '<p class="error">❌ Search placeholder NOT found</p>';
            }
        } else {
            echo '<p class="error">❌ index.html file NOT found</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>3. JavaScript Event Handlers</h2>
        <p>Checking if search event handlers are properly bound...</p>
        
        <?php
        $js_file = __DIR__ . '/assets/js/app.js';
        if (file_exists($js_file)) {
            $js_content = file_get_contents($js_file);
            
            if (strpos($js_content, 'requestSearch') !== false) {
                echo '<p class="success">✅ requestSearch found in app.js</p>';
            } else {
                echo '<p class="error">❌ requestSearch NOT found in app.js</p>';
            }
            
            if (strpos($js_content, 'addEventListener') !== false) {
                echo '<p class="success">✅ Event listeners found in app.js</p>';
            } else {
                echo '<p class="error">❌ Event listeners NOT found in app.js</p>';
            }
            
            if (strpos($js_content, 'search_requests.php') !== false) {
                echo '<p class="success">✅ search_requests.php API call found in app.js</p>';
            } else {
                echo '<p class="error">❌ search_requests.php API call NOT found in app.js</p>';
            }
        } else {
            echo '<p class="error">❌ app.js file NOT found</p>';
        }
        ?>
    </div>
    
    <div class="test-section">
        <h2>4. Manual Test Form</h2>
        <p>Use this form to test search manually:</p>
        
        <form action="api/search_requests.php" method="GET" target="_blank">
            <label>Search Term:</label><br>
            <input type="text" name="search" placeholder="Enter search term..." style="width: 300px; padding: 5px;">
            <br><br>
            <input type="hidden" name="limit" value="5">
            <button type="submit" style="padding: 10px 20px; background: #007bff; color: white; border: none; cursor: pointer;">
                🔍 Test Search
            </button>
        </form>
    </div>
    
    <div class="test-section">
        <h2>5. Quick Links</h2>
        <ul>
            <li><a href="index.html" target="_blank">🏠 Main Application</a></li>
            <li><a href="api/search_requests.php?search=test&limit=5" target="_blank">🔍 Test API (search: "test")</a></li>
            <li><a href="api/search_requests.php?search=&limit=5" target="_blank">📋 Test API (all requests)</a></li>
        </ul>
    </div>
    
    <script>
        // Auto-refresh every 30 seconds
        setTimeout(() => {
            location.reload();
        }, 30000);
    </script>
</body>
</html>
