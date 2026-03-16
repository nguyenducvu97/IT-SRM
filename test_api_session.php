<?php
// Test API with session management
session_start();

// Create valid admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';
$_SESSION['email'] = 'admin@test.com';

echo "<h2>Session Created</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test API call by including the API file
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET['action'] = 'get';
$_GET['id'] = 43;

echo "<h2>Testing API...</h2>";

// Include and test the API directly
include __DIR__ . '/api/service_requests.php';

// Simulate server variables for testing
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['action'] = 'test_login';
$_POST['username'] = 'admin';
$_POST['password'] = 'password';

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once 'config/database.php';
require_once 'config/session.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    $action = isset($data->action) ? $data->action : '';
    
    if ($action == 'test_login') {
        $username = isset($data->username) ? $data->username : '';
        $password = isset($data->password) ? $data->password : '';
        
        // Start session
        startSession();
        
        // Test database connection
        try {
            $db = getDatabaseConnection();
            
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
                    
                    echo json_encode([
                        'success' => true,
                        'message' => "Login successful",
                        'data' => [
                            'id' => $row['id'],
                            'username' => $row['username'],
                            'full_name' => $row['full_name'],
                            'role' => $row['role']
                        ],
                        'session_id' => session_id(),
                        'session_data' => $_SESSION
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => "Invalid password",
                        'debug' => [
                            'username_received' => $username,
                            'password_received_length' => strlen($password),
                            'hash_exists' => !empty($row['password_hash'])
                        ]
                    ]);
                }
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => "User not found",
                    'debug' => [
                        'username_received' => $username,
                        'password_received_length' => strlen($password)
                    ]
                ]);
            }
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => "Database error: " . $e->getMessage()
            ]);
        }
    } elseif ($action == 'test_session') {
        startSession();
        
        if (isLoggedIn()) {
            $user = getCurrentUser();
            echo json_encode([
                'success' => true,
                'message' => "Session active",
                'data' => $user,
                'session_id' => session_id(),
                'session_data' => $_SESSION
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "No active session",
                'session_id' => session_id(),
                'session_data' => $_SESSION
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Invalid action: " . $action
        ]);
    }
} elseif ($method == 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action == 'test_session') {
        startSession();
        
        if (isLoggedIn()) {
            $user = getCurrentUser();
            echo json_encode([
                'success' => true,
                'message' => "Session active",
                'data' => $user,
                'session_id' => session_id(),
                'session_data' => $_SESSION
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => "No active session",
                'session_id' => session_id(),
                'session_data' => $_SESSION
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => "Invalid action: " . $action
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => "Method not allowed"
    ]);
}
?>
