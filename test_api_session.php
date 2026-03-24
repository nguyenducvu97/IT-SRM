<?php
// Test API with session
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Mock authenticated user
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyễn Đức Vũ';
$_SESSION['role'] = 'user';

echo "Testing API with session...\n";

// Mock GET request
$_GET['action'] = 'get';
$_GET['id'] = '5';

try {
    ob_start();
    include_once 'api/service_requests.php';
    $output = ob_get_clean();
    echo "✅ API executed successfully\n";
    echo "Output: $output\n";
} catch (Exception $e) {
    echo "❌ Error executing API: " . $e->getMessage() . "\n";
} catch (Error $e) {
    echo "❌ Fatal error executing API: " . $e->getMessage() . "\n";
}

echo "Test completed.\n";
?>
