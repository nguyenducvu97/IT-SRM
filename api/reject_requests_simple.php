<?php
// Simplified Reject Requests API for testing
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/session.php';
require_once '../config/database.php';

// Start session
startSession();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get user information
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

// Get database connection
$db = getDatabaseConnection();

// Get HTTP method
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action == 'list') {
        // Only admin and staff can view reject requests
        if (!in_array($user_role, ['admin', 'staff'])) {
            echo json_encode(['success' => false, 'message' => 'Chỉ admin và staff mới có quyền xem yêu cầu từ chối']);
            exit;
        }
        
        try {
            // Simple query first - just check if table exists and has data
            $table_check = $db->query("SELECT COUNT(*) as total FROM reject_requests");
            $total = $table_check->fetch(PDO::FETCH_ASSOC)['total'];
            
            if ($total > 0) {
                // Get basic reject requests
                $query = "SELECT rr.*, sr.title as service_request_title 
                         FROM reject_requests rr 
                         LEFT JOIN service_requests sr ON rr.service_request_id = sr.id 
                         ORDER BY rr.created_at DESC 
                         LIMIT 10";
                
                $stmt = $db->prepare($query);
                $stmt->execute();
                $reject_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Lấy danh sách yêu cầu từ chối thành công',
                    'data' => [
                        'reject_requests' => $reject_requests,
                        'total' => $total
                    ]
                ]);
            } else {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Không có yêu cầu từ chối nào',
                    'data' => [
                        'reject_requests' => [],
                        'total' => 0
                    ]
                ]);
            }
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false, 
                'message' => 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage()
            ]);
        }
        
    } else {
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
    }
    
} else {
    echo json_encode(['success' => false, 'message' => 'Method không được hỗ trợ']);
}
?>
