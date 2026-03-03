<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit();
}

session_start([
    'cookie_lifetime' => 86400, // 24 hours
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax'
]);

// Allow GET requests without login for dropdown population
if ($method != 'GET' && !isset($_SESSION['user_id'])) {
    jsonResponse(false, "Unauthorized access");
}

if ($method == 'GET') {
    $query = "SELECT * FROM categories ORDER BY name ASC";
    $stmt = $db->prepare($query);
    $stmt->execute();
    
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse(true, "Categories retrieved", $categories);
}

elseif ($method == 'POST') {
    if ($_SESSION['role'] != 'admin') {
        jsonResponse(false, "Only administrators can create categories");
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    $name = isset($data->name) ? sanitizeInput($data->name) : '';
    $description = isset($data->description) ? sanitizeInput($data->description) : '';
    
    if (empty($name)) {
        jsonResponse(false, "Category name is required");
    }
    
    $check_query = "SELECT id FROM categories WHERE name = :name LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":name", $name);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        jsonResponse(false, "Category already exists");
    }
    
    $query = "INSERT INTO categories (name, description) VALUES (:name, :description)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":description", $description);
    
    if ($stmt->execute()) {
        jsonResponse(true, "Category created", ['id' => $db->lastInsertId()]);
    } else {
        jsonResponse(false, "Failed to create category");
    }
}

elseif ($method == 'PUT') {
    if ($_SESSION['role'] != 'admin') {
        jsonResponse(false, "Only administrators can update categories");
    }
    
    $data = json_decode(file_get_contents("php://input"));
    
    $id = isset($data->id) ? (int)$data->id : 0;
    $name = isset($data->name) ? sanitizeInput($data->name) : '';
    $description = isset($data->description) ? sanitizeInput($data->description) : '';
    
    if ($id <= 0 || empty($name)) {
        jsonResponse(false, "Invalid input");
    }
    
    $query = "UPDATE categories SET name = :name, description = :description WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":name", $name);
    $stmt->bindParam(":description", $description);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        jsonResponse(true, "Category updated");
    } else {
        jsonResponse(false, "Failed to update category");
    }
}

elseif ($method == 'DELETE') {
    if ($_SESSION['role'] != 'admin') {
        jsonResponse(false, "Only administrators can delete categories");
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        jsonResponse(false, "Invalid category ID");
    }
    
    $check_query = "SELECT COUNT(*) as count FROM service_requests WHERE category_id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":id", $id);
    $check_stmt->execute();
    
    $count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($count > 0) {
        jsonResponse(false, "Cannot delete category with existing requests");
    }
    
    $query = "DELETE FROM categories WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        jsonResponse(true, "Category deleted");
    } else {
        jsonResponse(false, "Failed to delete category");
    }
}

else {
    jsonResponse(false, "Method not allowed");
}
?>
