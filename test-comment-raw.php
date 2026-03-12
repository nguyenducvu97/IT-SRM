<?php
// Raw test without any includes - just test the function definition
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing raw comments API...<br>";

// Define the function first
function commentsJsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
}

echo "Function defined successfully<br>";

// Test it
echo "Testing function output:<br>";
commentsJsonResponse(true, "Raw test works", ["timestamp" => date('Y-m-d H:i:s')]);
?>
