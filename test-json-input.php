<?php
// Test JSON input
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing JSON input...\n";

// Get raw POST data
$raw_data = file_get_contents("php://input");
echo "Raw data: " . $raw_data . "\n";

// Get POST data
$post_data = $_POST;
echo "POST data: " . json_encode($post_data) . "\n";

// Get JSON decoded data
$json_data = json_decode($raw_data);
echo "JSON decoded: " . json_encode($json_data) . "\n";

// Test with hardcoded data
$test_data = '{"action":"register","username":"testuser"}';
echo "Test data: " . $test_data . "\n";
$test_decoded = json_decode($test_data);
echo "Test decoded: " . json_encode($test_decoded) . "\n";
?>
