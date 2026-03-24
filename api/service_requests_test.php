<?php
// Simple test version to isolate the issue
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/api_errors.log');

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Basic requires only
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Start session for authentication
startSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action == 'list') {
        try {
            $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $offset = ($page - 1) * $limit;
            
            $user_id = getCurrentUserId();
            $user_role = getCurrentUserRole();
            
            $where_clause = "WHERE 1=1";
            $params = [];
            
            if ($user_role != 'admin' && $user_role != 'staff') {
                $where_clause .= " AND sr.user_id = :user_id";
                $params[':user_id'] = $user_id;
            }
            
            $query = "SELECT sr.*, u.username as requester_name, c.name as category_name 
                      FROM service_requests sr 
                      LEFT JOIN users u ON sr.user_id = u.id 
                      LEFT JOIN categories c ON sr.category_id = c.id 
                      $where_clause
                      ORDER BY sr.created_at DESC 
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($query);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            if ($stmt->execute()) {
                $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $count_query = "SELECT COUNT(*) as total FROM service_requests sr $where_clause";
                $count_stmt = $db->prepare($count_query);
                
                foreach ($params as $key => $value) {
                    $count_stmt->bindValue($key, $value);
                }
                
                $count_stmt->execute();
                $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
                
                echo json_encode([
                    'success' => true,
                    'data' => $requests,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => $total,
                        'pages' => ceil($total / $limit)
                    ]
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to fetch requests']);
            }
        } catch (Exception $e) {
            error_log("List requests error: " . $e->getMessage());
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
