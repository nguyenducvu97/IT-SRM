<?php
// Test service_requests.php directly
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Service Requests API Test</h2>";

// Simulate the environment
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'list';

echo "Calling service_requests.php...<br>";

// Capture output
ob_start();
include __DIR__ . '/api/service_requests.php';
$output = ob_get_clean();

echo "<h3>Output:</h3>";
echo "<pre>" . htmlspecialchars($output) . "</pre>";

echo "<h3>Headers sent:</h3>";
if (function_exists('headers_list')) {
    $headers = headers_list();
    foreach ($headers as $header) {
        echo htmlspecialchars($header) . "<br>";
    }
}

?>
