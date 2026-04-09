<?php
session_start();

// Set user session
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyênx Duc Vu';
$_SESSION['role'] = 'user';

echo "<h2>Test Optimized API</h2>";

// Test with proper POST simulation
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW';

$_POST['action'] = 'create';
$_POST['title'] = 'Optimized Test Request ' . date('H:i:s');
$_POST['description'] = 'Optimized test description';
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
    echo "<h3>Testing Optimized API...</h3>";
    
    // Capture output
    ob_start();
    include 'api/service_requests_optimized.php';
    $output = ob_get_clean();
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000;
    
    echo "<h3>Performance Comparison:</h3>";
    echo "<p>Execution time: " . number_format($execution_time, 2) . " ms</p>";
    
    if ($execution_time < 100) {
        echo "<p style='color: green;'>EXCELLENT: Under 100ms!</p>";
    } elseif ($execution_time < 500) {
        echo "<p style='color: green;'>GOOD: Under 500ms</p>";
    } elseif ($execution_time < 1000) {
        echo "<p style='color: orange;'>ACCEPTABLE: Under 1 second</p>";
    } else {
        echo "<p style='color: red;'>SLOW: Over 1 second</p>";
    }
    
    echo "<h3>API Response:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
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
            echo "<p style='color: green;'>OPTIMIZED CREATE ACTION WORKING!</p>";
            
            if (isset($json_data['data']['id'])) {
                echo "<p>Created Request ID: " . $json_data['data']['id'] . "</p>";
            }
        } else {
            echo "<p style='color: red;'>OPTIMIZED CREATE ACTION FAILED!</p>";
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
             WHERE user_id = :user_id AND title LIKE '%Optimized Test Request%'
             ORDER BY created_at DESC 
             LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":user_id", $_SESSION['user_id']);
    $stmt->execute();
    
    $latest_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($latest_request) {
        echo "<p style='color: green;'>Optimized test request found: ID {$latest_request['id']}</p>";
        echo "<p>Title: {$latest_request['title']}</p>";
        echo "<p>Created at: {$latest_request['created_at']}</p>";
    } else {
        echo "<p style='color: orange;'>No optimized test request found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database check failed: " . $e->getMessage() . "</p>";
}

echo "<h3>Recommendation:</h3>";
echo "<p>If optimized API is much faster, consider:</p>";
echo "<ul>";
echo "<li>1. Replace the original API with the optimized version</li>";
echo "<li>2. Keep the optimized version as a backup</li>";
echo "<li>3. Test the optimized version thoroughly before deployment</li>";
echo "<li>4. Monitor performance after deployment</li>";
echo "</ul>";
?>
