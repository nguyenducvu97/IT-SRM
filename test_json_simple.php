<?php
// Simple test for API JSON response
session_start();

// Create valid admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';

echo "<h2>Session Created</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test service_requests API
$_GET['action'] = 'get';
$_GET['id'] = 43;

echo "<h2>Testing service_requests API...</h2>";

// Test basic JSON response
echo "<h3>Basic JSON Test:</h3>";
$testData = [
    'success' => true,
    'message' => 'Test response',
    'data' => ['id' => 43, 'title' => 'Test Request']
];

header('Content-Type: application/json');
echo json_encode($testData);
?>
