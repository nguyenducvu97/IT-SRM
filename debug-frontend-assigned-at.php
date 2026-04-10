<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Debug Frontend assigned_at Display</h2>";
    
    // Check if we need to clear cache
    echo "<h3>Cache Clear Instructions:</h3>";
    echo "<ol>";
    echo "<li>Clear browser cache (Ctrl+F5 or Cmd+Shift+R)</li>";
    echo "<li>Check if CSS/JS versions are updated</li>";
    echo "<li>Verify no cached API responses</li>";
    echo "</ol>";
    
    // Test formatDate function
    echo "<h3>JavaScript formatDate Function Test:</h3>";
    echo "<script>";
    echo "function formatDate(dateString) {";
    echo "    if (!dateString) return '';";
    echo "    const date = new Date(dateString);";
    echo "    if (isNaN(date.getTime())) return dateString;";
    echo "    return date.toLocaleString('vi-VN', {";
    echo "        hour: '2-digit',";
    echo "        minute: '2-digit',";
    echo "        second: '2-digit',";
    echo "        day: '2-digit',";
    echo "        month: '2-digit',";
    echo "        year: 'numeric'";
    echo "    });";
    echo "}";
    echo "const testDate = '2026-04-09 21:00:42';";
    echo "console.log('Test formatDate:', formatDate(testDate));";
    echo "document.write('<p>Test formatDate(\"2026-04-09 21:00:42\"): ' + formatDate(testDate) + '</p>');";
    echo "</script>";
    
    // Check request-detail.js version
    echo "<h3>Frontend File Check:</h3>";
    $js_file = __DIR__ . '/assets/js/request-detail.js';
    if (file_exists($js_file)) {
        $js_modified = filemtime($js_file);
        echo "<p>request-detail.js last modified: " . date('Y-m-d H:i:s', $js_modified) . "</p>";
        
        // Check if assigned_at logic exists
        $js_content = file_get_contents($js_file);
        if (strpos($js_content, 'assigned_at') !== false) {
            echo "<p style='color: green;'>assigned_at logic FOUND in request-detail.js</p>";
        } else {
            echo "<p style='color: red;'>assigned_at logic NOT FOUND in request-detail.js</p>";
        }
        
        if (strpos($js_content, 'Ngày nhân') !== false) {
            echo "<p style='color: green;'>Ngày nhân text FOUND in request-detail.js</p>";
        } else {
            echo "<p style='color: red;'>Ngày nhân text NOT FOUND in request-detail.js</p>";
        }
    } else {
        echo "<p style='color: red;'>request-detail.js file NOT FOUND</p>";
    }
    
    // Check index.html version
    echo "<h3>Index.html Version Check:</h3>";
    $index_file = __DIR__ . '/index.html';
    if (file_exists($index_file)) {
        $index_content = file_get_contents($index_file);
        
        // Extract JS version
        if (preg_match('/request-detail\.js\?v=(\d+)/', $index_content, $matches)) {
            echo "<p>request-detail.js version: {$matches[1]}</p>";
        } else {
            echo "<p>request-detail.js version: NOT FOUND (might be cached)</p>";
        }
    }
    
    // Simulate frontend template logic
    echo "<h3>Template Logic Simulation:</h3>";
    
    // Get request data
    $request_query = "SELECT id, title, status, assigned_to, assigned_name, created_at, assigned_at, resolved_at 
                      FROM service_requests 
                      WHERE id = 81";
    
    $request_stmt = $db->prepare($request_query);
    $request_stmt->execute();
    $request = $request_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<h4>Simulated Frontend Output:</h4>";
        
        // Simulate the template logic
        echo "<p><strong>ID yêu yêu:</strong> #{$request['id']}</p>";
        echo "<p><strong>Tiêu yêu:</strong> " . htmlspecialchars($request['title']) . "</p>";
        echo "<p><strong>Ngày yêu:</strong> " . date('H:i:s d/m/Y', strtotime($request['created_at'])) . "</p>";
        
        // This is the key logic
        if ($request['assigned_at']) {
            echo "<p><strong>Ngày nhân:</strong> " . date('H:i:s d/m/Y', strtotime($request['assigned_at'])) . "</p>";
            echo "<p style='color: green;'>assigned_at condition: TRUE (should display)</p>";
        } else {
            echo "<p style='color: red;'>assigned_at condition: FALSE (should not display)</p>";
        }
        
        echo "<p><strong>Trang thái:</strong> {$request['status']}</p>";
        echo "<p><strong>Ngày nhân:</strong> {$request['assigned_name']}</p>";
        
        echo "</div>";
        
        // Debug the condition
        echo "<h4>Debug Condition:</h4>";
        echo "<pre>";
        echo "request.assigned_at = " . var_export($request['assigned_at'], true) . "\n";
        echo "Boolean check: " . ($request['assigned_at'] ? 'TRUE' : 'FALSE') . "\n";
        echo "Template should display: " . ($request['assigned_at'] ? 'YES' : 'NO');
        echo "</pre>";
    }
    
    echo "<h3>Troubleshooting Steps:</h3>";
    echo "<ol>";
    echo "<li><strong>Clear Browser Cache:</strong> Press Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)</li>";
    echo "<li><strong>Check Network Tab:</strong> Look for cached API responses</li>";
    echo "<li><strong>Console Check:</strong> Open DevTools and check for JavaScript errors</li>";
    echo "<li><strong>Element Inspector:</strong> Check if Ngày nhân element exists in DOM</li>";
    echo "<li><strong>API Response:</strong> Verify assigned_at is present in API response</li>";
    echo "</ol>";
    
    echo "<h3>Manual Test Steps:</h3>";
    echo "<ol>";
    echo "<li>Open main application in new tab</li>";
    echo "<li>Navigate to request #81</li>";
    echo "<li>Open DevTools (F12)</li>";
    echo "<li>Check Console tab for errors</li>";
    echo "<li>Check Network tab for API calls</li>";
    echo "<li>Inspect request details section</li>";
    echo "<li>Look for 'Ngày nhân:' in the HTML</li>";
    echo "</ol>";
    
    // Create a simple test page
    echo "<h3>Isolated Test:</h3>";
    echo "<p><a href='test-assigned-at-isolated.html' target='_blank'>Open isolated test page</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
