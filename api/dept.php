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

// Check if user is authenticated and is admin
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

session_start();
$user_role = $_SESSION['role'] ?? '';

if ($user_role !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Admin access required.']);
    exit();
}

// Get database connection
$db = new Database();
$pdo = $db->getConnection();

// Get request method and action
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    switch ($method) {
        case 'GET':
            handleGet($pdo, $action);
            break;
        case 'POST':
            handlePost($pdo, $action);
            break;
        case 'PUT':
            handlePut($pdo, $action);
            break;
        case 'DELETE':
            handleDelete($pdo, $action);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGet($pdo, $action) {
    if ($action === 'dropdown') {
        // Return only names for dropdown compatibility
        $stmt = $pdo->prepare("SELECT name FROM departments WHERE is_active = TRUE ORDER BY name");
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode(['success' => true, 'data' => $departments]);
    } elseif ($action === 'get') {
        $id = $_GET['id'] ?? null;
        
        if ($id) {
            // Get single department
            $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
            $stmt->execute([$id]);
            $department = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($department) {
                echo json_encode(['success' => true, 'data' => $department]);
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Department not found']);
            }
        } else {
            // Get all departments as objects
            $stmt = $pdo->prepare("SELECT * FROM departments ORDER BY name");
            $stmt->execute();
            $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode(['success' => true, 'data' => $departments]);
        }
    } else {
        // Default: return full objects for departments management
        $stmt = $pdo->prepare("SELECT * FROM departments ORDER BY name");
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode(['success' => true, 'data' => $departments]);
    }
}

function handlePost($pdo, $action) {
    if ($action !== 'create') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    
    if (empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Department name is required']);
        return;
    }
    
    // Check if department already exists
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ?");
    $stmt->execute([$name]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Department already exists']);
        return;
    }
    
    // Create department
    $stmt = $pdo->prepare("
        INSERT INTO departments (name, description) 
        VALUES (?, ?)
    ");
    
    $result = $stmt->execute([$name, $description]);
    
    if ($result) {
        $department_id = $pdo->lastInsertId();
        echo json_encode([
            'success' => true,
            'message' => 'Department created successfully',
            'data' => ['id' => $department_id]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create department']);
    }
}

function handlePut($pdo, $action) {
    if ($action !== 'update') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    $id = (int)($input['id'] ?? 0);
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $is_active = isset($input['is_active']) ? (bool)$input['is_active'] : true;
    
    if ($id <= 0 || empty($name)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Department ID and name are required']);
        return;
    }
    
    // Check if department exists
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    
    if (!$stmt->fetch()) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Department not found']);
        return;
    }
    
    // Check if name conflicts with other department
    $stmt = $pdo->prepare("SELECT id FROM departments WHERE name = ? AND id != ?");
    $stmt->execute([$name, $id]);
    
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(['success' => false, 'message' => 'Department name already exists']);
        return;
    }
    
    // Update department
    $stmt = $pdo->prepare("
        UPDATE departments 
        SET name = ?, description = ?, is_active = ?
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$name, $description, $is_active, $id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Department updated successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to update department']);
    }
}

function handleDelete($pdo, $action) {
    if ($action !== 'delete') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        return;
    }
    
    $id = (int)($_GET['id'] ?? 0);
    
    if ($id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Department ID is required']);
        return;
    }
    
    // Check if department exists
    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
    $stmt->execute([$id]);
    $department = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$department) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Department not found']);
        return;
    }
    
    // Check if department is being used by users
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE department = ?");
    $stmt->execute([$department['name']]);
    $user_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($user_count > 0) {
        http_response_code(409);
        echo json_encode([
            'success' => false, 
            'message' => "Không thể xóa phòng ban này vì có {$user_count} người dùng đang sử dụng. Vui lòng chuyển người dùng sang phòng ban khác trước."
        ]);
        return;
    }
    
    // Delete department
    $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
    $result = $stmt->execute([$id]);
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => 'Department deleted successfully'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to delete department']);
    }
}
?>
