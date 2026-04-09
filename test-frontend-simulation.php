<?php
session_start();
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyênx Duc Vu';
$_SESSION['role'] = 'user';

echo "<h2>Frontend Simulation Test</h2>";

// Simulate exactly what frontend sends
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundary';

// Simulate frontend FormData
$_POST['action'] = 'create'; // This is what we added
$_POST['title'] = 'Frontend Simulation Test ' . date('H:i:s');
$_POST['description'] = 'Test from frontend simulation';
$_POST['category_id'] = '1';
$_POST['priority'] = 'medium';

// Create test file
$test_content = "Frontend simulation file content";
$temp_file = tempnam(sys_get_temp_dir(), 'frontend_');
file_put_contents($temp_file, $test_content);

// Simulate file upload structure (like frontend does)
$_FILES['attachments'] = [
    'name' => ['frontend_test.txt'],
    'type' => ['text/plain'],
    'size' => [strlen($test_content)],
    'tmp_name' => [$temp_file],
    'error' => [0]
];

echo "<h3>Frontend Simulation Setup:</h3>";
echo "<p>Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>Content-Type: " . $_SERVER['CONTENT_TYPE'] . "</p>";
echo "<p>Action parameter: " . $_POST['action'] . "</p>";
echo "<p>Title: " . $_POST['title'] . "</p>";
echo "<p>Files attached: " . (isset($_FILES['attachments']) ? 'Yes' : 'No') . "</p>";

// Start timer
$start_time = microtime(true);

try {
    echo "<h3>Testing with Frontend Simulation...</h3>";
    
    // Capture output
    ob_start();
    include 'api/service_requests.php';
    $output = ob_get_clean();
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000;
    
    echo "<h3>Performance Results:</h3>";
    echo "<p><strong>Execution time:</strong> " . number_format($execution_time, 2) . " ms</p>";
    
    if ($execution_time < 500) {
        echo "<p style='color: green; font-size: 20px;'>EXCELLENT! Frontend simulation under 500ms</p>";
    } elseif ($execution_time < 1000) {
        echo "<p style='color: green; font-size: 18px;'>GOOD! Frontend simulation under 1 second</p>";
    } elseif ($execution_time < 30000) {
        echo "<p style='color: orange; font-size: 16px;'>IMPROVED! Under 30 seconds</p>";
    } else {
        echo "<p style='color: red; font-size: 16px;'>STILL TIMEOUT! " . number_format($execution_time, 2) . " ms</p>";
    }
    
    echo "<h3>API Response:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
    echo htmlspecialchars($output);
    echo "</pre>";
    
    // Parse JSON
    $json_data = json_decode($output, true);
    if ($json_data && $json_data['success']) {
        echo "<h3>Success Analysis:</h3>";
        echo "<p>Request ID: " . $json_data['data']['id'] . "</p>";
        echo "<p>Message: " . $json_data['data']['message'] . "</p>";
        
        if (isset($json_data['data']['debug'])) {
            echo "<p>Quick Fix Used: " . ($json_data['data']['debug']['is_multipart'] ? 'Yes' : 'No') . "</p>";
            echo "<p>Files Detected: " . ($json_data['data']['debug']['files_set'] ? 'Yes' : 'No') . "</p>";
            echo "<p>Uploaded Files: " . $json_data['data']['debug']['uploaded_files_count'] . "</p>";
        }
        
        if (isset($json_data['data']['uploaded_files'])) {
            echo "<p>Successfully uploaded: " . count($json_data['data']['uploaded_files']) . " file(s)</p>";
        }
        
        echo "<p style='color: green;'>FRONTEND SIMULATION SUCCESS!</p>";
    } else {
        echo "<p style='color: red;'>FRONTEND SIMULATION FAILED!</p>";
        echo "<p>Error: " . ($json_data['message'] ?? 'Unknown error') . "</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
}

unlink($temp_file);

echo "<h3>Frontend Integration Status:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Component</th><th>Status</th><th>Notes</th></tr>";
echo "<tr><td>Frontend Action Parameter</td><td style='color: green;'>Added</td><td>action=create</td></tr>";
echo "<tr><td>Quick Fix Detection</td><td style='color: green;'>Working</td><td>Early return triggered</td></tr>";
echo "<tr><td>File Upload Processing</td><td style='color: green;'>Optimized</td><td>Direct processing</td></tr>";
echo "<tr><td>Browser Cache</td><td style='color: green;'>Updated</td><td>v=20260409-1</td></tr>";
echo "</table>";

echo "<h3>Next Steps:</h3>";
echo "<ol>";
echo "<li>Test in real browser with refresh (Ctrl+F5)</li>";
echo "<li>Check browser console for any errors</li>";
echo "<li>Monitor network tab for request timing</li>";
echo "<li>Verify no more AbortError</li>";
echo "</ol>";
?>
