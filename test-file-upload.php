<?php
session_start();
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyênx Duc Vu';
$_SESSION['role'] = 'user';

echo "<h2>Test File Upload Performance</h2>";

// Create a test file
$test_file_content = "This is a test file for upload performance testing.\nCreated at: " . date('Y-m-d H:i:s');
$temp_file = tempnam(sys_get_temp_dir(), 'test_upload_');
file_put_contents($temp_file, $test_file_content);

// Simulate POST with file upload
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW';

$_POST['action'] = 'create';
$_POST['title'] = 'File Upload Test ' . date('H:i:s');
$_POST['description'] = 'Test with file attachment';
$_POST['category_id'] = '1';
$_POST['priority'] = 'medium';

// Simulate file upload
$_FILES['attachments'] = [
    'name' => ['test_file.txt'],
    'type' => ['text/plain'],
    'size' => [strlen($test_file_content)],
    'tmp_name' => [$temp_file],
    'error' => [0]
];

echo "<h3>Test Setup:</h3>";
echo "<p>Method: " . $_SERVER['REQUEST_METHOD'] . "</p>";
echo "<p>Content-Type: " . $_SERVER['CONTENT_TYPE'] . "</p>";
echo "<p>Action: " . $_POST['action'] . "</p>";
echo "<p>Title: " . $_POST['title'] . "</p>";
echo "<p>File attachments: " . (isset($_FILES['attachments']) ? 'Yes' : 'No') . "</p>";
echo "<p>File name: " . $_FILES['attachments']['name'][0] . "</p>";
echo "<p>File size: " . $_FILES['attachments']['size'][0] . " bytes</p>";

// Start timer
$start_time = microtime(true);

try {
    echo "<h3>Testing API with File Upload...</h3>";
    
    // Capture output
    ob_start();
    include 'api/service_requests.php';
    $output = ob_get_clean();
    
    $end_time = microtime(true);
    $execution_time = ($end_time - $start_time) * 1000;
    
    echo "<h3>Performance Results:</h3>";
    echo "<p>Execution time: " . number_format($execution_time, 2) . " ms</p>";
    
    if ($execution_time < 500) {
        echo "<p style='color: green; font-size: 18px;'>EXCELLENT! File upload under 500ms</p>";
    } elseif ($execution_time < 1000) {
        echo "<p style='color: green;'>GOOD! File upload under 1 second</p>";
    } elseif ($execution_time < 3000) {
        echo "<p style='color: orange;'>ACCEPTABLE! File upload under 3 seconds</p>";
    } else {
        echo "<p style='color: red;'>STILL SLOW! File upload took " . number_format($execution_time, 2) . " ms</p>";
    }
    
    echo "<h3>API Response:</h3>";
    echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 5px; max-height: 300px; overflow-y: auto;'>";
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
            echo "<p style='color: green;'>FILE UPLOAD WORKING!</p>";
            
            if (isset($json_data['data']['id'])) {
                echo "<p>Created Request ID: " . $json_data['data']['id'] . "</p>";
            }
            
            if (isset($json_data['data']['uploaded_files'])) {
                echo "<p>Uploaded Files: " . count($json_data['data']['uploaded_files']) . "</p>";
                foreach ($json_data['data']['uploaded_files'] as $file) {
                    echo "<p>- " . $file['original_name'] . " (" . number_format($file['file_size']) . " bytes)</p>";
                }
            }
        } else {
            echo "<p style='color: red;'>FILE UPLOAD FAILED!</p>";
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

// Clean up temp file
unlink($temp_file);

echo "<h3>File Upload Optimization:</h3>";
echo "<ul>";
echo "<li>Quick file validation (size check only)</li>";
echo "<li>Simplified file processing</li>";
echo "<li>Direct database insert for attachments</li>";
echo "<li>No complex file type validation</li>";
echo "<li>Minimal error handling for speed</li>";
echo "</ul>";

echo "<h3>Expected Performance:</h3>";
echo "<p>Without files: ~50ms</p>";
echo "<p>With small files: ~200-500ms</p>";
echo "<p>With large files: ~1-2 seconds</p>";
echo "<p>Original API: >30 seconds (timeout)</p>";
?>
