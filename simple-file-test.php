<?php
session_start();
$_SESSION['user_id'] = 4;

// Create test file
$test_content = "Simple test content";
$temp_file = tempnam(sys_get_temp_dir(), 'simple_');
file_put_contents($temp_file, $test_content);

// Setup POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'multipart/form-data';

$_POST['action'] = 'create';
$_POST['title'] = 'Simple File Test';
$_POST['description'] = 'Simple test';
$_POST['category_id'] = '1';
$_POST['priority'] = 'medium';

// Setup file - try different structure
$_FILES['attachments'] = [
    'name' => ['simple.txt'],
    'type' => ['text/plain'],
    'size' => [strlen($test_content)],
    'tmp_name' => [$temp_file],
    'error' => [0]
];

echo "Simple file test\n";
echo "Temp file: $temp_file\n";
echo "File exists: " . (file_exists($temp_file) ? 'Yes' : 'No') . "\n";
echo "Is readable: " . (is_readable($temp_file) ? 'Yes' : 'No') . "\n";

// Test file processing manually
$file_attachments = $_FILES['attachments'];
echo "File attachments loaded\n";

if (is_array($file_attachments['name'])) {
    $file_count = count($file_attachments['name']);
    echo "Processing $file_count files\n";
    
    for ($i = 0; $i < $file_count; $i++) {
        echo "File $i:\n";
        echo "  Name: " . $file_attachments['name'][$i] . "\n";
        echo "  Error: " . $file_attachments['error'][$i] . "\n";
        echo "  Size: " . $file_attachments['size'][$i] . "\n";
        echo "  Temp: " . $file_attachments['tmp_name'][$i] . "\n";
        echo "  Temp exists: " . (file_exists($file_attachments['tmp_name'][$i]) ? 'Yes' : 'No') . "\n";
        
        if ($file_attachments['error'][$i] === UPLOAD_ERR_OK) {
            echo "  Error check: PASSED\n";
            
            // Test move_uploaded_file
            $test_dest = tempnam(sys_get_temp_dir(), 'dest_');
            if (move_uploaded_file($file_attachments['tmp_name'][$i], $test_dest)) {
                echo "  Move test: SUCCESS\n";
                unlink($test_dest);
            } else {
                echo "  Move test: FAILED\n";
            }
        } else {
            echo "  Error check: FAILED\n";
        }
    }
}

// Test API
try {
    ob_start();
    include 'api/service_requests.php';
    $output = ob_get_clean();
    
    echo "\nAPI Response:\n";
    $json = json_decode($output, true);
    if ($json && $json['success']) {
        echo "Request ID: " . $json['data']['id'] . "\n";
        echo "Uploaded files: " . $json['data']['debug']['uploaded_files_count'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

unlink($temp_file);
?>
