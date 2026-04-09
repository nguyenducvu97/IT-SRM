<?php
session_start();
$_SESSION['user_id'] = 4;

// Create test file
$test_content = "Test file content";
$temp_file = tempnam(sys_get_temp_dir(), 'test_');
file_put_contents($temp_file, $test_content);

// Setup POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'multipart/form-data';

$_POST['action'] = 'create';
$_POST['title'] = 'Debug File Test';
$_POST['description'] = 'Debug test';
$_POST['category_id'] = '1';
$_POST['priority'] = 'medium';

// Setup file
$_FILES['attachments'] = [
    'name' => ['debug.txt'],
    'type' => ['text/plain'],
    'size' => [strlen($test_content)],
    'tmp_name' => [$temp_file],
    'error' => [0]
];

echo "Debug file upload test\n";
echo "Temp file: $temp_file\n";
echo "File exists: " . (file_exists($temp_file) ? 'Yes' : 'No') . "\n";
echo "File size: " . filesize($temp_file) . "\n";

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    ob_start();
    include 'api/service_requests.php';
    $output = ob_get_clean();
    
    echo "API Output: " . substr($output, 0, 200) . "\n";
    
    $json = json_decode($output, true);
    if ($json && $json['success']) {
        echo "Request ID: " . $json['data']['id'] . "\n";
        
        // Check if uploaded_files is in response
        if (isset($json['data']['uploaded_files'])) {
            echo "Uploaded files in response: " . count($json['data']['uploaded_files']) . "\n";
        } else {
            echo "No uploaded_files in response\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

unlink($temp_file);
?>
