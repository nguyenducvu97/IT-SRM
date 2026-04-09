<?php
// Simple performance test
$start = microtime(true);

// Simulate session
session_start();
$_SESSION['user_id'] = 4;

// Simulate POST
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'multipart/form-data';
$_POST['action'] = 'create';
$_POST['title'] = 'Simple Test';
$_POST['description'] = 'Test';
$_POST['category_id'] = '1';
$_POST['priority'] = 'medium';

// Load API
ob_start();
include 'api/service_requests.php';
$output = ob_get_clean();

$end = microtime(true);
$time = ($end - $start) * 1000;

echo "Time: " . number_format($time, 2) . " ms\n";
echo "Output: " . substr($output, 0, 100) . "\n";

$json = json_decode($output, true);
echo "Success: " . ($json['success'] ? 'YES' : 'NO') . "\n";

if ($json['success']) {
    echo "Request ID: " . $json['data']['id'] . "\n";
}
?>
