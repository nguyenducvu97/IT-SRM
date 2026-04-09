<?php
session_start();
$_SESSION['user_id'] = 4;

// Create test file
$test_content = "Test file content";
$temp_file = tempnam(sys_get_temp_dir(), 'test_');
file_put_contents($temp_file, $test_content);

// Setup POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundary';

$_POST['action'] = 'create';
$_POST['title'] = 'Debug Logs Test';
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

echo "Debug file upload test with logs\n";
echo "Content-Type: " . $_SERVER['CONTENT_TYPE'] . "\n";
echo "Is multipart: " . (strpos($_SERVER['CONTENT_TYPE'], 'multipart/form-data') !== false ? 'Yes' : 'No') . "\n";
echo "FILES attachments set: " . (isset($_FILES['attachments']) ? 'Yes' : 'No') . "\n";

// Capture error log
$log_file = __DIR__ . '/../logs/api_errors.log';
$initial_log_size = file_exists($log_file) ? filesize($log_file) : 0;

try {
    ob_start();
    include 'api/service_requests.php';
    $output = ob_get_clean();
    
    echo "API Response: " . substr($output, 0, 100) . "\n";
    
    // Read new log entries
    if (file_exists($log_file)) {
        $log_content = file_get_contents($log_file);
        $new_logs = substr($log_content, $initial_log_size);
        
        echo "\nDebug Logs:\n";
        echo $new_logs;
    } else {
        echo "\nNo log file found\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

unlink($temp_file);
?>
