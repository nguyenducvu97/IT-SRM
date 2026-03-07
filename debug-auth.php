<?php
// Debug version of auth.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start output buffering to prevent extra characters
ob_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once 'config/database.php';
require_once 'config/session.php';

// Helper functions (only ones not in database.php)
function jsonResponse($success, $message, $data = null) {
    // Clean any previous output
    ob_clean();
    
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

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    error_log("Raw input: " . file_get_contents("php://input"));
    error_log("Decoded data: " . json_encode($data));
    
    $action = isset($data->action) ? $data->action : '';
    
    if ($action == 'register') {
        error_log("Processing registration...");
        
        $username = sanitizeInput($data->username);
        $email = sanitizeInput($data->email);
        $password = $data->password;
        $full_name = sanitizeInput($data->full_name);
        $department = sanitizeInput($data->department);
        $phone = sanitizeInput($data->phone);
        
        error_log("Data sanitized - username: $username, email: $email");
        
        if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($department)) {
            jsonResponse(false, "Vui lòng điền đầy đủ thông tin bắt buộc");
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse(false, "Email không hợp lệ");
        }
        
        if (strlen($password) < 6) {
            jsonResponse(false, "Mật khẩu phải có ít nhất 6 ký tự");
        }
        
        // Check if username already exists
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            jsonResponse(false, "Tên đăng nhập đã tồn tại");
        }
        
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            jsonResponse(false, "Email đã tồn tại");
        }
        
        // Create new user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password_hash, full_name, department, phone, role, created_at) 
                  VALUES (:username, :email, :password_hash, :full_name, :department, :phone, 'user', NOW())";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':phone', $phone);
        
        error_log("Executing insert query...");
        
        if ($stmt->execute()) {
            error_log("User created successfully");
            jsonResponse(true, "Đăng ký thành công");
        } else {
            error_log("User creation failed: " . json_encode($stmt->errorInfo()));
            jsonResponse(false, "Đăng ký thất bại");
        }
    } else {
        jsonResponse(false, "Invalid action");
    }
}

// End output buffering and send clean JSON
ob_end_flush();
?>
