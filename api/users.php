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
require_once '../config/session.php';

if (!isLoggedIn()) {
    jsonResponse(false, "Unauthorized access");
    exit();
}

// Check if user is admin
if (getCurrentUserRole() !== 'admin') {
    jsonResponse(false, "Access denied. Admin access required.");
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

switch ($method) {
    case 'GET':
        if (isset($_GET['id'])) {
            getUser($db, $_GET['id']);
        } else {
            getUsers($db);
        }
        break;
    case 'POST':
        createUser($db);
        break;
    case 'PUT':
        updateUser($db);
        break;
    case 'DELETE':
        deleteUser($db, $_GET['id']);
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

function createUser($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    $username = isset($data->username) ? sanitizeInput($data->username) : '';
    $email = isset($data->email) ? sanitizeInput($data->email) : '';
    $password = isset($data->password) ? $data->password : '';
    $full_name = isset($data->full_name) ? sanitizeInput($data->full_name) : '';
    $department = isset($data->department) ? sanitizeInput($data->department) : '';
    $phone = isset($data->phone) ? sanitizeInput($data->phone) : '';
    $role = isset($data->role) ? sanitizeInput($data->role) : 'user';
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name)) {
        jsonResponse(false, "Required fields are missing");
    }
    
    if (!validateEmail($email)) {
        jsonResponse(false, "Invalid email format");
    }
    
    if (strlen($password) < 6) {
        jsonResponse(false, "Password must be at least 6 characters");
    }
    
    // Check if username or email already exists
    $check_query = "SELECT id FROM users WHERE username = :username OR email = :email LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":username", $username);
    $check_stmt->bindParam(":email", $email);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        jsonResponse(false, "Username or email already exists");
    }
    
    $password_hash = hashPassword($password);
    
    $query = "INSERT INTO users (username, email, password_hash, full_name, department, phone, role) 
              VALUES (:username, :email, :password_hash, :full_name, :department, :phone, :role)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":password_hash", $password_hash);
    $stmt->bindParam(":full_name", $full_name);
    $stmt->bindParam(":department", $department);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":role", $role);
    
    if ($stmt->execute()) {
        jsonResponse(true, "User created successfully");
    } else {
        jsonResponse(false, "Failed to create user");
    }
}

function updateUser($db) {
    $data = json_decode(file_get_contents("php://input"));
    
    $id = isset($data->id) ? sanitizeInput($data->id) : '';
    $username = isset($data->username) ? sanitizeInput($data->username) : '';
    $email = isset($data->email) ? sanitizeInput($data->email) : '';
    $password = isset($data->password) ? $data->password : '';
    $full_name = isset($data->full_name) ? sanitizeInput($data->full_name) : '';
    $department = isset($data->department) ? sanitizeInput($data->department) : '';
    $phone = isset($data->phone) ? sanitizeInput($data->phone) : '';
    $role = isset($data->role) ? sanitizeInput($data->role) : '';
    
    if (empty($id) || empty($username) || empty($email) || empty($full_name)) {
        jsonResponse(false, "Required fields are missing");
    }
    
    if (!validateEmail($email)) {
        jsonResponse(false, "Invalid email format");
    }
    
    if (!empty($password) && strlen($password) < 6) {
        jsonResponse(false, "Password must be at least 6 characters");
    }
    
    // Check if username or email already exists (excluding current user)
    $check_query = "SELECT id FROM users WHERE (username = :username OR email = :email) AND id != :id LIMIT 1";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":username", $username);
    $check_stmt->bindParam(":email", $email);
    $check_stmt->bindParam(":id", $id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() > 0) {
        jsonResponse(false, "Username or email already exists");
    }
    
    if (!empty($password)) {
        $password_hash = hashPassword($password);
        $query = "UPDATE users SET username = :username, email = :email, password_hash = :password_hash, 
                  full_name = :full_name, department = :department, phone = :phone, role = :role 
                  WHERE id = :id";
    } else {
        $query = "UPDATE users SET username = :username, email = :email, full_name = :full_name, 
                  department = :department, phone = :phone, role = :role WHERE id = :id";
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->bindParam(":email", $email);
    $stmt->bindParam(":full_name", $full_name);
    $stmt->bindParam(":department", $department);
    $stmt->bindParam(":phone", $phone);
    $stmt->bindParam(":role", $role);
    $stmt->bindParam(":id", $id);
    
    if (!empty($password)) {
        $stmt->bindParam(":password_hash", $password_hash);
    }
    
    if ($stmt->execute()) {
        jsonResponse(true, "User updated successfully");
    } else {
        jsonResponse(false, "Failed to update user");
    }
}

function deleteUser($db, $id) {
    if (empty($id)) {
        jsonResponse(false, "User ID is required");
    }
    
    // Don't allow deletion of the current admin user
    $current_user_id = getCurrentUserId();
    if ($id == $current_user_id) {
        jsonResponse(false, "Cannot delete your own account");
    }
    
    $query = "DELETE FROM users WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        jsonResponse(true, "User deleted successfully");
    } else {
        jsonResponse(false, "Failed to delete user");
    }
}
?>
