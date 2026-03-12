<?php
// Isolated test - simulate the API environment
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Mock required functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

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

// Mock session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Mock database connection
class MockPDO {
    public function prepare($sql) { return new MockPDOStatement(); }
}
class MockPDOStatement {
    public function bindParam($param, $value) { return true; }
    public function execute() { return true; }
    public function rowCount() { return 1; }
    public function fetch() { return ['user_id' => 1, 'assigned_to' => null]; }
    public function lastInsertId() { return 123; }
}

$db = new MockPDO();

// Simulate POST request
$method = 'POST';
$user_id = 1;
$user_role = 'admin';

// Test the main logic
$data = json_decode('{"service_request_id": 31, "comment": "Test comment"}');

$service_request_id = isset($data->service_request_id) ? (int)$data->service_request_id : 0;
$comment = isset($data->comment) ? sanitizeInput($data->comment) : '';

if ($service_request_id <= 0 || empty($comment)) {
    commentsJsonResponse(false, "Required fields are missing");
}

// Mock check query
$request = ['user_id' => 1, 'assigned_to' => null];

if ($user_role != 'admin' && $user_role != 'staff' && 
    $request['user_id'] != $user_id) {
    commentsJsonResponse(false, "Access denied");
}

// Success
commentsJsonResponse(true, "Comment added", ['id' => 123]);
?>
