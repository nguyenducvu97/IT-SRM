<?php
// Test JSON output
header("Content-Type: application/json; charset=UTF-8");

// Test data
$test_data = [
    'success' => true,
    'message' => 'Test successful',
    'data' => ['id' => 1, 'name' => 'Test']
];

echo json_encode($test_data);
?>
