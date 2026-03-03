<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start([
    'cookie_lifetime' => 86400, // 24 hours
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

if (!isset($_SESSION['user_id'])) {
    jsonResponse(false, "Unauthorized access");
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    $service_request_id = isset($data->service_request_id) ? (int)$data->service_request_id : 0;
    $comment = isset($data->comment) ? sanitizeInput($data->comment) : '';
    
    if ($service_request_id <= 0 || empty($comment)) {
        jsonResponse(false, "Required fields are missing");
    }
    
    $check_query = "SELECT user_id, assigned_to FROM service_requests WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":id", $service_request_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        jsonResponse(false, "Service request not found");
    }
    
    $request = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_role != 'admin' && $user_role != 'staff' && 
        $request['user_id'] != $user_id && $request['assigned_to'] != $user_id) {
        jsonResponse(false, "Access denied");
    }
    
    $query = "INSERT INTO comments (service_request_id, user_id, comment) 
             VALUES (:service_request_id, :user_id, :comment)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":service_request_id", $service_request_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":comment", $comment);
    
    if ($stmt->execute()) {
        jsonResponse(true, "Comment added", ['id' => $db->lastInsertId()]);
    } else {
        jsonResponse(false, "Failed to add comment");
    }
}

elseif ($method == 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        jsonResponse(false, "Invalid comment ID");
    }
    
    $check_query = "SELECT user_id FROM comments WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":id", $id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        jsonResponse(false, "Comment not found");
    }
    
    $comment = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_role != 'admin' && $comment['user_id'] != $user_id) {
        jsonResponse(false, "Access denied");
    }
    
    $query = "DELETE FROM comments WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        jsonResponse(true, "Comment deleted");
    } else {
        jsonResponse(false, "Failed to delete comment");
    }
}

else {
    jsonResponse(false, "Method not allowed");
}
?>
