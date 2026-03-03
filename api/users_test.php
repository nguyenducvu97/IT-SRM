<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';

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

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getUser($db, $_GET['id']);
        } else {
            getUsers($db);
        }
        break;
    default:
        jsonResponse(false, "Method not allowed");
}

function getUsers($db) {
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $role = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
    
    $query = "SELECT id, username, email, full_name, department, phone, role, created_at 
              FROM users WHERE 1=1";
    
    if (!empty($search)) {
        $query .= " AND (username LIKE :search OR email LIKE :search OR full_name LIKE :search)";
    }
    
    if (!empty($role)) {
        $query .= " AND role = :role";
    }
    
    $query .= " ORDER BY created_at DESC";
    
    $stmt = $db->prepare($query);
    
    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bindParam(":search", $searchParam);
    }
    
    if (!empty($role)) {
        $stmt->bindParam(":role", $role);
    }
    
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    jsonResponse(true, "Users retrieved successfully", $users);
}

function getUser($db, $id) {
    $query = "SELECT id, username, email, full_name, department, phone, role, created_at 
              FROM users WHERE id = :id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        jsonResponse(true, "User retrieved successfully", $user);
    } else {
        jsonResponse(false, "User not found");
    }
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data
    ]);
}
?>
