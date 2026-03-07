<?php
// Minimal auth test
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

try {
    require_once '../config/database.php';
    require_once '../config/session.php';
    
    if ($_SERVER['REQUEST_METHOD'] == 'GET' && isset($_GET['action']) && $_GET['action'] == 'check_session') {
        startSession();
        
        if (isset($_SESSION['user_id'])) {
            $user_data = [
                'id' => $_SESSION['user_id'],
                'username' => $_SESSION['username'] ?? '',
                'full_name' => $_SESSION['full_name'] ?? '',
                'role' => $_SESSION['role'] ?? ''
            ];
            
            echo json_encode([
                'success' => true,
                'message' => 'User is logged in',
                'data' => $user_data
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'No active session'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid request'
        ]);
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>
