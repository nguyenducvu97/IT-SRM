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

// Start session for authentication
startSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Notification helper functions
function createNotification($pdo, $userId, $title, $message, $type = 'info', $relatedId = null, $relatedType = null) {
    try {
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        return $stmt->execute([$userId, $title, $message, $type, $relatedId, $relatedType]);
    } catch (Exception $e) {
        error_log("Failed to create notification: " . $e->getMessage());
        return false;
    }
}

function notifyRole($pdo, $role, $title, $message, $type = 'info', $relatedId = null, $relatedType = null) {
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = ?");
        $stmt->execute([$role]);
        $users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (!empty($users)) {
            $notifyStmt = $pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($users as $userId) {
                try {
                    $notifyStmt->execute([$userId, $title, $message, $type, $relatedId, $relatedType]);
                } catch (Exception $e) {
                    error_log("Failed to notify user $userId: " . $e->getMessage());
                }
            }
        }
    } catch (Exception $e) {
        error_log("Failed to notify role $role: " . $e->getMessage());
    }
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

// Get current user
$current_user = getCurrentUserId();
$user_role = getCurrentUserRole();

// Only admin can access reject requests, except for check_status
if ($user_role !== 'admin' && $_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

// For GET requests, only allow check_status for non-admin users
if ($user_role !== 'admin' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action !== 'check_status') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied - action not allowed']);
        exit;
    }
    
    // Staff can access check_status (no additional restrictions needed)
    // The session check above already verified user is authenticated
}

try {
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGet($db, $user_role);
            break;
        case 'PUT':
            handlePut($db, $current_user);
            break;
        case 'DELETE':
            handleDelete($db, $current_user);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGet($db, $user_role) {
    $action = $_GET['action'] ?? 'list';
    
    if ($action === 'get') {
        $reject_id = $_GET['id'] ?? null;
        if (!$reject_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Reject request ID is required']);
            return;
        }
        
        $stmt = $db->prepare("
            SELECT rr.*, 
                   u.full_name as requester_name,
                   sr.title as request_title
            FROM reject_requests rr
            JOIN users u ON rr.rejected_by = u.id
            JOIN service_requests sr ON rr.service_request_id = sr.id
            WHERE rr.id = ?
        ");
        $stmt->execute([$reject_id]);
        $reject_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reject_request) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Reject request not found']);
            return;
        }
        
        echo json_encode(['success' => true, 'data' => $reject_request]);
        return;
    }
    
    if ($action === 'check_status') {
        // Check reject request status for a specific service request
        $service_request_id = $_GET['service_request_id'] ?? null;
        if (!$service_request_id) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Service request ID is required']);
            return;
        }
        
        // For staff, only check their own service requests
        if ($user_role === 'staff') {
            $stmt = $db->prepare("
                SELECT rr.*, 
                       u.full_name as requester_name,
                       admin.full_name as admin_name
                FROM reject_requests rr
                JOIN users u ON rr.rejected_by = u.id
                LEFT JOIN users admin ON rr.processed_by = admin.id
                WHERE rr.service_request_id = ? AND rr.rejected_by = ?
                ORDER BY rr.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$service_request_id, $current_user]);
        } elseif ($user_role === 'admin') {
            // Admin can check any service request
            $stmt = $db->prepare("
                SELECT rr.*, 
                       u.full_name as requester_name,
                       admin.full_name as admin_name
                FROM reject_requests rr
                JOIN users u ON rr.rejected_by = u.id
                LEFT JOIN users admin ON rr.processed_by = admin.id
                WHERE rr.service_request_id = ?
                ORDER BY rr.created_at DESC
                LIMIT 1
            ");
            $stmt->execute([$service_request_id]);
        } else {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied']);
            return;
        }
        
        $reject_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reject_request) {
            echo json_encode(['success' => true, 'data' => $reject_request]);
        } else {
            echo json_encode(['success' => true, 'data' => null]);
        }
        return;
    }
    
    $status = $_GET['status'] ?? 'pending';
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = max(1, intval($_GET['limit'] ?? 20));
    $offset = ($page - 1) * $limit;
    
    // Count total records
    $count_stmt = $db->prepare("
        SELECT COUNT(*) as total
        FROM reject_requests
        WHERE status = ?
    ");
    $count_stmt->execute([$status]);
    $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Get reject requests with pagination
    $stmt = $db->prepare("
        SELECT rr.*, 
               u.full_name as requester_name,
               sr.title as request_title
        FROM reject_requests rr
        JOIN users u ON rr.rejected_by = u.id
        JOIN service_requests sr ON rr.service_request_id = sr.id
        WHERE rr.status = ?
        ORDER BY rr.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
    $stmt->execute([$status]);
    $reject_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $reject_requests,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function handlePut($db, $current_user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    // Check if this is an update operation
    if (isset($input['action']) && $input['action'] === 'update') {
        // Update reject request (admin only)
        $reject_id = $input['id'] ?? null;
        $reject_reason = $input['reject_reason'] ?? null;
        $reject_details = $input['reject_details'] ?? null;
        
        if (!$reject_id || !$reject_reason) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Reject request ID and reason are required']);
            return;
        }
        
        // Check if reject request exists
        $stmt = $db->prepare("
            SELECT id, status FROM reject_requests 
            WHERE id = ?
        ");
        $stmt->execute([$reject_id]);
        $reject_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reject_request) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Reject request not found']);
            return;
        }
        
        // Update reject request
        $stmt = $db->prepare("
            UPDATE reject_requests 
            SET reject_reason = ?, reject_details = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$reject_reason, $reject_details, $reject_id]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Reject request updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update reject request']);
        }
        return;
    }
    
    // Original processing logic
    $reject_id = $input['reject_id'] ?? null;
    $decision = $input['decision'] ?? null;
    $admin_reason = $input['admin_reason'] ?? null;
    
    if (!$reject_id || !$decision || !$admin_reason) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate decision
    $valid_decisions = ['approved', 'rejected'];
    if (!in_array($decision, $valid_decisions)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid decision']);
        return;
    }
    
    // Check if reject request exists and is pending
    $stmt = $db->prepare("
        SELECT id, status, service_request_id FROM reject_requests 
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->execute([$reject_id]);
    $reject_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reject_request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Reject request not found or already processed']);
        return;
    }
    
    // Create notifications for reject request decision
    try {
        // Get reject request details with user info
        $reject_stmt = $db->prepare("
            SELECT rr.*, u.full_name as requester_name, sr.title as request_title
            FROM reject_requests rr
            LEFT JOIN users u ON rr.rejected_by = u.id
            LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
            WHERE rr.id = ?
        ");
        $reject_stmt->execute([$reject_id]);
        $reject_data = $reject_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Create title and message
        $title = "Yêu cầu từ chối #" . $reject_id . " đã được " . ($decision === 'approved' ? 'duyệt' : 'từ chối');
        $message = "Yêu cầu từ chối từ " . $reject_data['requester_name'] . " về '" . $reject_data['request_title'] . "' đã được " . ($decision === 'approved' ? 'duyệt' : 'từ chối') . ". Lý do: " . $admin_reason;
        $type = $decision === 'approved' ? 'success' : 'warning';
        
        // Notify all staff and admin about the decision
        notifyRole($db, 'staff', $title, $message, $type, $reject_id, 'reject_request');
        notifyRole($db, 'admin', $title, $message, $type, $reject_id, 'reject_request');
        
        // Also notify original requester
        if ($reject_data['rejected_by'] != $current_user) {
            createNotification($db, $reject_data['rejected_by'], $title, $message, $type, $reject_id, 'reject_request');
        }
        
    } catch (Exception $e) {
        error_log("Failed to create reject request notifications: " . $e->getMessage());
    }
    
    // Update reject request
    $stmt = $db->prepare("
        UPDATE reject_requests 
        SET status = ?, admin_reason = ?, processed_by = ?, processed_at = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$decision, $admin_reason, $current_user, $reject_id]);
    
    if ($result) {
        // If approved, change service request status to 'rejected'
        if ($decision == 'approved') {
            $close_stmt = $db->prepare("
                UPDATE service_requests 
                SET status = 'rejected', updated_at = NOW()
                WHERE id = ?
            ");
            $close_stmt->execute([$reject_request['service_request_id']]);
        }
        
        echo json_encode([
            'success' => true,
            'message' => $decision == 'approved' ? 
                'Yêu cầu từ chối đã được duyệt, yêu cầu đã bị từ chối' : 
                'Yêu cầu từ chối đã bị từ chối'
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to process reject request']);
    }
}

function handleDelete($db, $current_user) {
    // Only admin can delete reject requests
    $reject_id = $_GET['id'] ?? null;
    
    if (!$reject_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Reject request ID is required']);
        return;
    }
    
    try {
        // Check if reject request exists
        $stmt = $db->prepare("
            SELECT rr.*, sr.title as request_title 
            FROM reject_requests rr
            JOIN service_requests sr ON rr.service_request_id = sr.id
            WHERE rr.id = ?
        ");
        $stmt->execute([$reject_id]);
        $reject_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$reject_request) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Reject request not found']);
            return;
        }
        
        // Check if reject request is processed - if so, don't allow deletion
        if ($reject_request['status'] !== 'pending') {
            echo json_encode([
                'success' => false, 
                'message' => 'Không thể xóa yêu cầu từ chối này vì đã được xử lý. Trạng thái: ' . $reject_request['status']
            ]);
            return;
        }
        
        // Delete reject request
        $stmt = $db->prepare("DELETE FROM reject_requests WHERE id = ?");
        $result = $stmt->execute([$reject_id]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Reject request deleted successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete reject request']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
