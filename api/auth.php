<?php
// Start output buffering to prevent extra characters
ob_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../config/session.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($method == 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action == 'check_session') {
        error_log("=== DEBUG CHECK SESSION ===");
        
        // Check if session is already started
        if (session_status() == PHP_SESSION_NONE) {
            session_start([
                'cookie_lifetime' => 86400, // 24 hours
                'cookie_httponly' => true,
                'cookie_samesite' => 'Lax'
            ]);
        }
        
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
            jsonResponse(true, "User is logged in", $user_data);
        } else {
            error_log("No active session");
            jsonResponse(false, "No active session");
        }
    } else {
        error_log("Invalid action: " . $action);
        jsonResponse(false, "Invalid action");
    }
}

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    $action = isset($data->action) ? $data->action : '';
    
    if ($action == 'login') {
        $username = sanitizeInput($data->username);
        $password = $data->password;
        
        if (empty($username) || empty($password)) {
            jsonResponse(false, "Username and password are required");
        }
        
        $query = "SELECT id, username, full_name, password_hash, role FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (verifyPassword($password, $row['password_hash'])) {
                session_start([
                    'cookie_lifetime' => 86400, // 24 hours
                    'cookie_httponly' => true,
                    'cookie_samesite' => 'Lax'
                ]);
                
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['role'] = $row['role'];
                
                jsonResponse(true, "Login successful", [
                    'id' => $row['id'],
                    'username' => $row['username'],
                    'full_name' => $row['full_name'],
                    'role' => $row['role']
                ]);
            } else {
                jsonResponse(false, "Invalid password");
            }
        } else {
            jsonResponse(false, "User not found");
        }
    } else if ($action == 'logout') {
        session_start([
            'cookie_lifetime' => 86400, // 24 hours
            'cookie_httponly' => true,
            'cookie_samesite' => 'Lax'
        ]);
        session_destroy();
        jsonResponse(true, "Logout successful");
    } else {
        jsonResponse(false, "Invalid action");
    }
}

// End output buffering and send clean JSON
ob_end_flush();
?>
