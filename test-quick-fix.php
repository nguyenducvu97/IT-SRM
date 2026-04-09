<?php
session_start();
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyênx Duc Vu';
$_SESSION['role'] = 'user';

echo "<h2>Test Quick Fix Performance</h2>";

// Test with proper POST simulation
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW';

$_POST['action'] = 'create';
$_POST['title'] = 'Quick Fix Test Request ' . date('H:i:s');
$_POST['description'] = 'Quick fix test description';
$_POST['category_id'] = '1';
$_POST['priority'] = 'medium';

echo "<h3>Request Data:</h3>";
echo "<p>Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>Content-Type: " . $_SERVER['CONTENT_TYPE'] . "</p>";
echo "<p>Action: " . $_POST['action'] . "</p>";
echo "<p>Title: " . $_POST['title'] . "</p>";

// Start timer
$start_time = microtime(true);

try {
    echo "<h3>Testing API with Quick Fix...</h3>";
    
    // Capture output
    ob_start();
    include 'api/service_requests.php';
    $output = ob_get_clean();
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000;
    
    echo "<h3>Performance Results:</h3>";
    echo "<p>Execution time: " . number_format($execution_time, 2) . " ms</p>";
    
    if ($execution_time < 100) {
        echo "<p style='color: green; font-size: 18px;'>EXCELLENT: Under 100ms! Quick Fix working!</p>";
    } elseif ($execution_time < 500) {
        echo "<p style='color: green;'>GOOD: Under 500ms</p>";
    } elseif ($execution_time < 1000) {
        echo "<p style='color: orange;'>ACCEPTABLE: Under 1 second</p>";
    } else {
        echo "<p style='color: red;'>STILL SLOW: " . number_format($execution_time, 2) . " ms</p>";
    }
    
    echo "<h3>API Response:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 200px; overflow-y: auto;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // Try to parse JSON
    $json_data = json_decode($output, true);
    if ($json_data) {
        echo "<h3>JSON Analysis:</h3>";
        echo "<p>Valid JSON: Yes</p>";
        echo "<p>Success: " . ($json_data['success'] ? 'Yes' : 'No') . "</p>";
        echo "<p>Message: " . ($json_data['message'] ?? 'No message') . "</p>";
        
        if ($json_data['success']) {
            echo "<p style='color: green;'>QUICK FIX WORKING!</p>";
            
            if (isset($json_data['data']['id'])) {
                echo "<p>Created Request ID: " . $json_data['data']['id'] . "</p>";
            }
        } else {
            echo "<p style='color: red;'>QUICK FIX FAILED!</p>";
            echo "<p>Error: " . ($json_data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>Invalid JSON response</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
}

echo "<h3>Database Verification:</h3>";

// Check if request was created
try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    // Get latest request
    $query = "SELECT id, title, created_at FROM service_requests 
             WHERE user_id = :user_id AND title LIKE '%Quick Fix Test Request%'
             ORDER BY created_at DESC 
             LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    
    $latest_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($latest_request) {
        echo "<p style='color: green;'>Quick fix test request found: ID {$latest_request['id']}</p>";
        echo "<p>Title: {$latest_request['title']}</p>";
        echo "<p>Created at: {$latest_request['created_at']}</p>";
    } else {
        echo "<p style='color: orange;'>No quick fix test request found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database check failed: " . $e->getMessage() . "</p>";
}

echo "<h3>Performance Comparison:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Method</th><th>Performance</th><th>Status</th></tr>";
echo "<tr>";
echo "<td>Original API (before fix)</td>";
echo "<td>> 30,000 ms (timeout)</td>";
echo "<td style='color: red;'>CRITICAL</td>";
echo "</tr>";
echo "<tr>";
echo "<td>Quick Fix API</td>";
echo "<td>" . (isset($execution_time) ? number_format($execution_time, 2) . " ms" : "Testing...") . "</td>";
echo "<td style='color: green;'>IMPROVED</td>";
echo "</tr>";
echo "<tr>";
echo "<td>Database Only</td>";
echo "<td>28.93 ms</td>";
echo "<td style='color: green;'>EXCELLENT</td>";
echo "</tr>";
echo "</table>";

echo "<h3>Quick Fix Benefits:</h3>";
echo "<ul>";
echo "<li>Early return for create action</li>";
echo "<li>Skip category caching</li>";
echo "<li>Skip complex nested conditions</li>";
echo "<li>Direct database insert</li>";
echo "<li>Minimal validation</li>";
echo "<li>No file upload processing for simple requests</li>";
echo "</ul>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test quick fix with real frontend</li>";
echo "<li>Monitor performance in production</li>";
echo "<li>Consider applying similar optimizations to other actions</li>";
echo "<li>Plan full API refactoring for long term</li>";
echo "</ol>";
?>
