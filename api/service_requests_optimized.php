<?php
// Optimized Service Requests API
session_start();
header('Content-Type: application/json');

// Disable error display
error_reporting(0);
ini_set('display_errors', 0);

// CORS headers
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Authentication
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Database connection
require_once '../config/database.php';
$db = getDatabaseConnection();

if ($db === null) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit();
}

// Helper function
function optimizedJsonResponse($success, $message, $data = null) {
    $response = ['success' => $success, 'message' => $message];
    if ($data !== null) $response['data'] = $data;
    echo json_encode($response);
    exit();
}

$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

if ($method == 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action == 'list') {
        // Simplified list query
        $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
        $limit = 10;
        $offset = ($page - 1) * $limit;
        
        $where_clause = "WHERE 1=1";
        $params = [];
        
        // Role-based filtering
        if ($user_role != 'admin' && $user_role != 'staff') {
            $where_clause .= " AND sr.user_id = :user_id";
            $params[':user_id'] = $user_id;
        }
        
        // Filters
        if (!empty($_GET['status'])) {
            $where_clause .= " AND sr.status = :status";
            $params[':status'] = $_GET['status'];
        }
        
        if (!empty($_GET['category_id'])) {
            $where_clause .= " AND sr.category_id = :category_id";
            $params[':category_id'] = (int)$_GET['category_id'];
        }
        
        // Main query
        $query = "SELECT sr.*, c.name as category_name, u.full_name as user_name
                 FROM service_requests sr
                 LEFT JOIN categories c ON sr.category_id = c.id
                 LEFT JOIN users u ON sr.user_id = u.id
                 $where_clause
                 ORDER BY sr.created_at DESC
                 LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Count query
        $count_query = "SELECT COUNT(*) as total FROM service_requests sr $where_clause";
        $count_stmt = $db->prepare($count_query);
        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }
        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        optimizedJsonResponse(true, "Requests retrieved", [
            'requests' => $requests,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'pages' => ceil($total / $limit)
            ]
        ]);
    }
    
    elseif ($action == 'get') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            optimizedJsonResponse(false, "Invalid request ID");
        }
        
        $query = "SELECT sr.*, c.name as category_name, u.full_name as user_name
                 FROM service_requests sr
                 LEFT JOIN categories c ON sr.category_id = c.id
                 LEFT JOIN users u ON sr.user_id = u.id
                 WHERE sr.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$request) {
            optimizedJsonResponse(false, "Request not found");
        }
        
        // Check permissions
        if ($user_role != 'admin' && $user_role != 'staff' && $request['user_id'] != $user_id) {
            optimizedJsonResponse(false, "Access denied");
        }
        
        optimizedJsonResponse(true, "Request retrieved", $request);
    }
    
    else {
        optimizedJsonResponse(false, "Unknown action");
    }
}

elseif ($method == 'POST') {
    // Handle POST requests
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'multipart/form-data') !== false) {
        // Handle FormData
        $action = $_POST['action'] ?? '';
    } else {
        // Handle JSON
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
    }
    
    if ($action == 'create') {
        // Optimized create action
        if (strpos($content_type, 'multipart/form-data') !== false) {
            // FormData
            $title = trim($_POST['title'] ?? '');
            $description = trim($_POST['description'] ?? '');
            $category_id = (int)($_POST['category_id'] ?? 0);
            $priority = $_POST['priority'] ?? 'medium';
        } else {
            // JSON
            $title = trim($input['title'] ?? '');
            $description = trim($input['description'] ?? '');
            $category_id = (int)($input['category_id'] ?? 0);
            $priority = $input['priority'] ?? 'medium';
        }
        
        // Validation
        if (empty($title) || empty($description) || $category_id <= 0) {
            optimizedJsonResponse(false, "Title, description, and category are required");
        }
        
        // Insert request
        $query = "INSERT INTO service_requests 
                  (user_id, category_id, title, description, priority, status, created_at, updated_at)
                  VALUES (:user_id, :category_id, :title, :description, :priority, 'open', NOW(), NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":category_id", $category_id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":priority", $priority);
        
        if ($stmt->execute()) {
            $request_id = $db->lastInsertId();
            
            optimizedJsonResponse(true, "Request created successfully", [
                'id' => $request_id,
                'title' => $title,
                'description' => $description,
                'category_id' => $category_id,
                'priority' => $priority,
                'status' => 'open',
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } else {
            optimizedJsonResponse(false, "Failed to create request");
        }
    }
    
    else {
        optimizedJsonResponse(false, "Unknown action");
    }
}

else {
    optimizedJsonResponse(false, "Method not allowed");
}
?>
