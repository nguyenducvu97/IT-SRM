<?php
// Remove output buffering to fix session issues
// ob_start();

header("Content-Type: application/json; charset=UTF-8");

// Dynamic CORS origin
$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : 'http://localhost';
error_log("HTTP_ORIGIN: " . $origin);
error_log("Request headers: " . json_encode(getallheaders()));

if (in_array($origin, ['http://localhost', 'http://localhost:80', 'http://localhost:8080'])) {
    header("Access-Control-Allow-Origin: " . $origin);
    error_log("CORS allowed for origin: " . $origin);
} else {
    error_log("CORS blocked for origin: " . $origin);
}
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");
header("Cache-Control: no-cache, must-revalidate");
header("Pragma: no-cache");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../config/session.php';

// Start session for all requests
startSession();

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action == 'check_session') {
        error_log("=== DEBUG CHECK SESSION ===");
        
        error_log("Session ID: " . session_id());
        error_log("Cookie data: " . json_encode($_COOKIE));
        error_log("Session data: " . json_encode($_SESSION));
        
        if (isset($_SESSION['user_id'])) {
            // User is logged in, return user data
            $user_data = [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'],
                'full_name' => $_SESSION['full_name'],
                'role' => $_SESSION['role']
            ];
            
            error_log("User logged in: " . json_encode($user_data));
            
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => "User is logged in",
    'data' => $user_data
]);
exit();
        } else {
            error_log("No active session");
            
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "No active session"
]);
exit();
        }
    } else {
        error_log("Invalid action: " . $action);
        
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "Invalid action"
]);
exit();
    }
}

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    $action = isset($data->action) ? $data->action : '';
    
    if ($action == 'login') {
        $username = sanitizeInput($data->username);
        $password = $data->password;
        
        if (empty($username) || empty($password)) {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "Username and password are required"
]);
exit();
        }
        
        $query = "SELECT id, username, full_name, password_hash, role FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (verifyPassword($password, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['role'] = $row['role'];
                
                error_log("Login successful - Session ID: " . session_id());
                error_log("Session data after login: " . json_encode($_SESSION));
                
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => "Login successful",
    'data' => [
        'id' => $row['id'],
        'username' => $row['username'],
        'full_name' => $row['full_name'],
        'role' => $row['role']
    ]
]);
exit();
            } else {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "Invalid password"
]);
exit();
            }
        } else {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "User not found"
]);
exit();
        }
    } else if ($action == 'logout') {
        startSession(); // Start session for logout
        session_destroy();
        
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => "Logout successful"
]);
exit();
    } else if ($action == 'register') {
        $username = sanitizeInput($data->username);
        $email = sanitizeInput($data->email);
        $password = $data->password;
        $full_name = sanitizeInput($data->full_name);
        $department = sanitizeInput($data->department);
        $phone = sanitizeInput($data->phone);
        
        if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($department)) {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "All fields are required"
]);
exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "Invalid email format"
]);
exit();
        }
        
        if (strlen($password) < 6) {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "Password must be at least 6 characters"
]);
exit();
        }
        
        // Check if username exists
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "Username already exists"
]);
exit();
        }
        
        // Check if email exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "Email already exists"
]);
exit();
        }
        
        // Create user
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $query = "INSERT INTO users (username, email, password_hash, full_name, department, phone, role) 
                  VALUES (:username, :email, :password_hash, :full_name, :department, :phone, 'user')";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password_hash', $password_hash);
        $stmt->bindParam(':full_name', $full_name);
        $stmt->bindParam(':department', $department);
        $stmt->bindParam(':phone', $phone);
        
        if ($stmt->execute()) {
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => "Registration successful"
]);
exit();
        } else {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "Registration failed"
]);
exit();
        }
    } else {
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => "Invalid action"
]);
exit();
    }
}

// Helper functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}
?>
