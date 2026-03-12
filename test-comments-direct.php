<?php
// Direct test of comments API without includes
echo "Testing comments API directly...\n";

// Simulate the API call
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Mock the input
$input_data = json_encode([
    'service_request_id' => 31,
    'comment' => 'Test comment from admin'
]);

// Save php://input
file_put_contents('php://temp', $input_data);

// Test the function directly
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

// Test the function
echo "Testing commentsJsonResponse function:\n";
commentsJsonResponse(true, "Test successful", ["test" => "data"]);
?>
