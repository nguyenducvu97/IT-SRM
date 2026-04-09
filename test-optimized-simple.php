<?php
session_start();
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyênx Duc Vu';
$_SESSION['role'] = 'user';

echo "<h2>Test Optimized Simple</h2>";

// Simulate JSON POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'application/json';

// Simulate JSON input
$json_input = '{"action":"create","title":"Optimized Simple Test","description":"Test","category_id":"1","priority":"medium"}';

// Override php://input
$stream = fopen('php://memory', 'r+');
fwrite($stream, $json_input);
rewind($stream);
stream_filter_register('php_filter_input', 'php_filter_input');

$start_time = microtime(true);

try {
    echo "<h3>Testing Optimized API (JSON)...</h3>";
    
    // Capture output
    ob_start();
    
    // Manually set the input for JSON decode
    $input = json_decode($json_input, true);
    $_POST = []; // Clear POST
    foreach ($input as $key => $value) {
        $_POST[$key] = $value;
    }
    
    include 'api/service_requests_optimized.php';
    $output = ob_get_clean();
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000;
    
    echo "<h3>Performance:</h3>";
    echo "<p>Execution time: " . number_format($execution_time, 2) . " ms</p>";
    
    if ($execution_time < 100) {
        echo "<p style='color: green;'>EXCELLENT: Under 100ms!</p>";
    } elseif ($execution_time < 500) {
        echo "<p style='color: green;'>GOOD: Under 500ms</p>";
    } else {
        echo "<p style='color: orange;'>NEEDS OPTIMIZATION: " . number_format($execution_time, 2) . " ms</p>";
    }
    
    echo "<h3>API Response:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    $json_data = json_decode($output, true);
    if ($json_data && $json_data['success']) {
        echo "<p style='color: green;'>OPTIMIZED API WORKING!</p>";
        echo "<p>Created Request ID: " . ($json_data['data']['id'] ?? 'N/A') . "</p>";
    } else {
        echo "<p style='color: red;'>OPTIMIZED API FAILED!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
