<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

// Start session for authenticated requests
if ($method != 'GET') {
    startSession();
    
    // Check authentication for non-GET requests
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => "Unauthorized access"
        ]);
        exit();
    }
}

if ($method == 'GET') {
    $action = $_GET['action'] ?? 'list';
    
    if ($action == 'list') {
        // Get categories with request counts
        $query = "
            SELECT c.*, 
                   COUNT(sr.id) as request_count,
                   COUNT(CASE WHEN sr.status = 'open' THEN 1 END) as open_count,
                   COUNT(CASE WHEN sr.status = 'in_progress' THEN 1 END) as in_progress_count,
                   COUNT(CASE WHEN sr.status = 'resolved' THEN 1 END) as resolved_count,
                   COUNT(CASE WHEN sr.status = 'closed' THEN 1 END) as closed_count
            FROM categories c
            LEFT JOIN service_requests sr ON c.id = sr.category_id
            GROUP BY c.id, c.name, c.description
            ORDER BY c.name ASC
        ";
        $stmt = $db->prepare($query);
        $stmt->execute();
        
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'message' => "Categories retrieved with statistics",
            'data' => $categories
        ], JSON_UNESCAPED_UNICODE);
        exit();
    } elseif ($action == 'requests') {
        // Get requests for a specific category
        $categoryId = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
        $status = isset($_GET['status']) ? $_GET['status'] : 'all';
        
        if ($categoryId <= 0) {
            header('Content-Type: application/json; charset=UTF-8');
            echo json_encode([
                'success' => false,
                'message' => "Invalid category ID"
            ], JSON_UNESCAPED_UNICODE);
            exit();
        }
        
        // Build query based on status filter
        $statusCondition = "";
        if ($status !== 'all') {
            $statusCondition = " AND sr.status = :status";
        }
        
        $query = "
            SELECT sr.*, u.username, u.full_name, c.name as category_name,
                   assigned.username as assigned_username, assigned.full_name as assigned_full_name
            FROM service_requests sr
            LEFT JOIN users u ON sr.user_id = u.id
            LEFT JOIN users assigned ON sr.assigned_to = assigned.id
            LEFT JOIN categories c ON sr.category_id = c.id
            WHERE sr.category_id = :category_id $statusCondition
            ORDER BY sr.created_at DESC
        ";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':category_id', $categoryId);
        
        if ($status !== 'all') {
            $stmt->bindParam(':status', $status);
        }
        
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'message' => "Category requests retrieved",
            'data' => $requests
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

elseif ($method == 'POST') {
    // Start session to check authentication
    if (!session_id()) {
        session_start();
    }
    
    if (!isLoggedIn() || getCurrentUserRole() != 'admin') {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Only administrators can create categories"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    $json_input = file_get_contents("php://input");
    error_log("Category API - Raw input: " . $json_input);
    
    $data = json_decode($json_input);
    error_log("Category API - Parsed data: " . print_r($data, true));
    
    $name = isset($data->name) ? sanitizeInput($data->name) : '';
    $description = isset($data->description) ? sanitizeInput($data->description) : '';
    
    error_log("Category API - Sanitized name: '$name'");
    error_log("Category API - Sanitized description: '$description'");
    
    if (empty($name)) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Category name is required"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    $check_query = "SELECT id FROM categories WHERE name = :name LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":name", $name);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Category already exists"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":description", $description);
    
    if ($stmt->execute()) {
        $new_id = $db->lastInsertId();
        error_log("Category API - Successfully created category with ID: $new_id");
        
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'message' => "Category created",
            'data' => ['id' => $new_id]
        ], JSON_UNESCAPED_UNICODE);
        exit();
    } else {
        error_log("Category API - Insert failed: " . print_r($stmt->errorInfo(), true));
        
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Failed to create category"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

elseif ($method == 'PUT') {
    // Start session to check authentication
    if (!session_id()) {
        session_start();
    }
    
    if (!isLoggedIn() || getCurrentUserRole() != 'admin') {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Only administrators can update categories"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    $id = isset($data->id) ? (int)$data->id : 0;
    $name = isset($data->name) ? sanitizeInput($data->name) : '';
    $description = isset($data->description) ? sanitizeInput($data->description) : '';
    
    if ($id <= 0 || empty($name)) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Invalid category data"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    $query = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":description", $description);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'message' => "Category updated successfully"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    } else {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Failed to update category"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

elseif ($method == 'DELETE') {
    // Start session to check authentication
    if (!session_id()) {
        session_start();
    }
    
    if (!isLoggedIn() || getCurrentUserRole() != 'admin') {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Only administrators can delete categories"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Invalid category ID"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    // Check for existing requests with detailed information
    $check_query = "SELECT COUNT(*) as count FROM service_requests WHERE category_id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":id", $id);
    $check_stmt->execute();
    
    $count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        // Get category name for better error message
        $category_query = "SELECT name FROM categories WHERE id = :id";
        $category_stmt = $db->prepare($category_query);
        $category_stmt->bindParam(":id", $id);
        $category_stmt->execute();
        $category = $category_stmt->fetch(PDO::FETCH_ASSOC);
        $category_name = $category ? $category['name'] : 'Danh muc này';
        
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Không thể xóa danh mục '$category_name' vì còn $count yêu cầu. Bạn phải xóa tất cả các yêu cầu trong danh mục này trước.",
            'error_type' => 'category_has_requests',
            'request_count' => $count,
            'category_name' => $category_name
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
    
    $query = "DELETE FROM categories WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => true,
            'message' => "Category deleted"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    } else {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => "Failed to delete category"
        ], JSON_UNESCAPED_UNICODE);
        exit();
    }
}

else {
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => false,
        'message' => "Method not allowed"
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
?>
