<?php
// Start output buffering to prevent extra characters
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
        startSession(); // Start session for this action
        error_log("=== DEBUG CHECK SESSION ===");
        
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
            
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "User is logged in",
                'data' => $user_data
            ]);
            exit();
        } else {
            error_log("No active session");
            
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "No active session"
            ]);
            exit();
        }
    } else {
        error_log("Invalid action: " . $action);
        
        // Clean any previous output
        if (ob_get_length()) {
            ob_clean();
        }
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
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
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
                startSession(); // Start session for login
                
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['username'] = $row['username'];
                $_SESSION['full_name'] = $row['full_name'];
                $_SESSION['role'] = $row['role'];
                
                // Clean any previous output
                if (ob_get_length()) {
                    ob_clean();
                }
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
                // Clean any previous output
                if (ob_get_length()) {
                    ob_clean();
                }
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'message' => "Invalid password"
                ]);
                exit();
            }
        } else {
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
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
        
        // Clean any previous output
        if (ob_get_length()) {
            ob_clean();
        }
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
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Vui lòng điền đầy đủ thông tin bắt buộc"
            ]);
            exit();
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Email không hợp lệ"
            ]);
            exit();
        }
        
        if (strlen($password) < 6) {
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Mật khẩu phải có ít nhất 6 ký tự"
            ]);
            exit();
        }
        
        // Check if username already exists
        $query = "SELECT id FROM users WHERE username = :username";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':username', $username);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Tên đăng nhập đã tồn tại"
            ]);
            exit();
        }
        
        // Check if email already exists
        $query = "SELECT id FROM users WHERE email = :email";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Email đã tồn tại"
            ]);
            exit();
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
        
        if ($stmt->execute()) {
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'message' => "Đăng ký thành công"
            ]);
            exit();
        } else {
            // Clean any previous output
            if (ob_get_length()) {
                ob_clean();
            }
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => "Đăng ký thất bại"
            ]);
            exit();
        }
    } else {
        // Clean any previous output
        if (ob_get_length()) {
            ob_clean();
        }
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "Invalid action"
        ]);
        exit();
    }
}

// End output buffering and send clean JSON
ob_end_flush();
?>
