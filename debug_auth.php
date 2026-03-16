<?php
// Debug auth endpoint
ob_start();

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../config/session.php';

$method = $_SERVER['REQUEST_METHOD'];

// Debug information
$debug = [
    'method' => $method,
    'raw_input' => file_get_contents("php://input"),
    'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
    'content_length' => $_SERVER['CONTENT_LENGTH'] ?? 'not set'
];

if ($method == 'POST') {
    $raw_input = file_get_contents("php://input");
    $debug['raw_input_hex'] = bin2hex($raw_input);
    
    $data = json_decode($raw_input);
    $debug['json_error'] = json_last_error();
    $debug['json_error_msg'] = json_last_error_msg();
    $debug['parsed_data'] = $data;
    
    if ($data && isset($data->action)) {
        $action = $data->action;
        $debug['action'] = $action;
        
        if ($action == 'login') {
            $debug['username'] = $data->username ?? 'not set';
            $debug['password_length'] = strlen($data->password ?? '');
            
            // Try to process login
            try {
                $db = getDatabaseConnection();
                $username = sanitizeInput($data->username);
                $password = $data->password;
                
                $query = "SELECT id, username, full_name, password_hash, role FROM users WHERE username = :username";
                $stmt = $db->prepare($query);
                $stmt->bindParam(':username', $username);
                $stmt->execute();
                
                if ($stmt->rowCount() > 0) {
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (verifyPassword($password, $row['password_hash'])) {
                        startSession();
                        
                        $_SESSION['user_id'] = $row['id'];
                        $_SESSION['username'] = $row['username'];
                        $_SESSION['full_name'] = $row['full_name'];
                        $_SESSION['role'] = $row['role'];
                        
                        $debug['login_success'] = true;
                        $debug['user_data'] = [
                            'id' => $row['id'],
                            'username' => $row['username'],
                            'full_name' => $row['full_name'],
                            'role' => $row['role']
                        ];
                    } else {
                        $debug['login_success'] = false;
                        $debug['login_error'] = 'Invalid password';
                    }
                } else {
                    $debug['login_success'] = false;
                    $debug['login_error'] = 'User not found';
                }
            } catch (Exception $e) {
                $debug['login_error'] = $e->getMessage();
            }
        }
    }
}

// Clean any previous output
if (ob_get_length()) {
    ob_clean();
}

echo json_encode([
    'success' => $debug['login_success'] ?? false,
    'debug' => $debug
]);
?>
