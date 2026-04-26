<?php
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

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/session.php';

// Start session for authentication
startSession();

if (!isLoggedIn()) {
    jsonResponse(false, "Unauthorized access");
    exit();
}

// Check if user is admin or staff (staff can view users)
$user_role = getCurrentUserRole();
if (!in_array($user_role, ['admin', 'staff'])) {
    jsonResponse(false, "Access denied. Admin or Staff access required.");
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
    error_log("USERS API: getUsers called");
    $search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';
    $role = isset($_GET['role']) ? sanitizeInput($_GET['role']) : '';
    // Check if this is a paginated request or get all users
    $is_paginated = isset($_GET['page']) || isset($_GET['limit']);
    
    if ($is_paginated) {
        $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);
        $limit = max(1, isset($_GET['limit']) ? (int)$_GET['limit'] : 9);
        $offset = ($page - 1) * $limit;
        error_log("USERS API: search='$search', role='$role', page=$page, limit=$limit");
    } else {
        error_log("USERS API: search='$search', role='$role' (no pagination)");
    }
    
    // Build WHERE clause
    $where_clause = "WHERE 1=1";
    $params = [];
    
    if (!empty($search)) {
        $where_clause .= " AND (username LIKE :search_username OR email LIKE :search_email OR full_name LIKE :search_full_name)";
        $params[':search_username'] = "%$search%";
        $params[':search_email'] = "%$search%";
        $params[':search_full_name'] = "%$search%";
    }
    
    if (!empty($role)) {
        $where_clause .= " AND role = :role";
        $params[':role'] = $role;
    }

    if (!empty($_GET['department'])) {
        $where_clause .= " AND department = :department";
        $params[':department'] = $_GET['department'];
    }

    if ($is_paginated) {
        // Get total count for pagination
        $count_query = "SELECT COUNT(*) as total FROM users $where_clause";
        $count_stmt = $db->prepare($count_query);

        foreach ($params as $key => $value) {
            $count_stmt->bindValue($key, $value);
        }

        $count_stmt->execute();
        $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

        // Get users with pagination
        $query = "SELECT id, username, email, full_name, department, phone, role, created_at
                  FROM users $where_clause
                  ORDER BY created_at DESC
                  LIMIT :limit OFFSET :offset";

        $stmt = $db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(true, "Users retrieved successfully", [
            'users' => $users,
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'total_pages' => ceil($total / $limit)
            ]
        ]);
    } else {
        // Get all users without pagination
        $query = "SELECT id, username, email, full_name, department, phone, role, created_at
                  FROM users $where_clause
                  ORDER BY created_at DESC";

        $stmt = $db->prepare($query);

        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        jsonResponse(true, "Users retrieved successfully", [
            'users' => $users
        ]);
    }
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
        exit();
    }
    
    if (!validateEmail($email)) {
        jsonResponse(false, "Invalid email format");
        exit();
    }
    
    if (!empty($password) && strlen($password) < 6) {
        jsonResponse(false, "Password must be at least 6 characters");
        exit();
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
        exit();
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
    
    // Check if user exists
    $check_query = "SELECT id, username FROM users WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":id", $id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() === 0) {
        jsonResponse(false, "User not found");
    }
    
    $user = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check for foreign key constraints
    $constraints = [];
    
    // Check service requests created by this user
    $sr_query = "SELECT COUNT(*) as count FROM service_requests WHERE user_id = :id";
    $sr_stmt = $db->prepare($sr_query);
    $sr_stmt->bindParam(":id", $id);
    $sr_stmt->execute();
    $sr_count = $sr_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($sr_count > 0) {
        $constraints[] = "{$sr_count} yêu cầu dịch vụ";
    }
    
    // Check service requests assigned to this user
    $assigned_query = "SELECT COUNT(*) as count FROM service_requests WHERE assigned_to = :id";
    $assigned_stmt = $db->prepare($assigned_query);
    $assigned_stmt->bindParam(":id", $id);
    $assigned_stmt->execute();
    $assigned_count = $assigned_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($assigned_count > 0) {
        $constraints[] = "{$assigned_count} yêu cầu được giao";
    }
    
    // Check comments by this user
    $comments_query = "SELECT COUNT(*) as count FROM comments WHERE user_id = :id";
    $comments_stmt = $db->prepare($comments_query);
    $comments_stmt->bindParam(":id", $id);
    $comments_stmt->execute();
    $comments_count = $comments_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($comments_count > 0) {
        $constraints[] = "{$comments_count} bình luận";
    }
    
    // Check attachments uploaded by this user
    $attachments_query = "SELECT COUNT(*) as count FROM attachments WHERE uploaded_by = :id";
    $attachments_stmt = $db->prepare($attachments_query);
    $attachments_stmt->bindParam(":id", $id);
    $attachments_stmt->execute();
    $attachments_count = $attachments_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($attachments_count > 0) {
        $constraints[] = "{$attachments_count} tệp đính kèm";
    }
    
    // Check resolutions by this user
    $resolutions_query = "SELECT COUNT(*) as count FROM resolutions WHERE resolved_by = :id";
    $resolutions_stmt = $db->prepare($resolutions_query);
    $resolutions_stmt->bindParam(":id", $id);
    $resolutions_stmt->execute();
    $resolutions_count = $resolutions_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    if ($resolutions_count > 0) {
        $constraints[] = "{$resolutions_count} giải quyết";
    }
    
    // If there are constraints, don't delete and show detailed error
    if (!empty($constraints)) {
        $constraint_list = implode(", ", $constraints);
        jsonResponse(false, "Không thể xóa người dùng '{$user['username']}' vì có dữ liệu liên quan: {$constraint_list}. Vui lòng xóa hoặc chuyển dữ liệu liên quan trước.");
    }
    
    // If no constraints, proceed with deletion
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
