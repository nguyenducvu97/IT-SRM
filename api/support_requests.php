<?php
// IT Service Request Support Requests API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../config/database.php';
require_once '../config/session.php';

// Start session
session_start();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
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

// Get current user
$current_user = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';

// Get action from query parameter
$action = $_GET['action'] ?? '';

try {
    $pdo = getDatabaseConnection();
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            handleGet($pdo, $action, $current_user, $user_role);
            break;
        case 'POST':
            handlePost($pdo, $action, $current_user, $user_role);
            break;
        case 'PUT':
            handlePut($pdo, $action, $current_user, $user_role);
            break;
        case 'DELETE':
            handleDelete($pdo, $action, $current_user, $user_role);
            break;
        default:
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Server error: ' . $e->getMessage()]);
}

function handleGet($pdo, $action, $current_user, $user_role) {
    switch ($action) {
        case 'get':
            $support_id = $_GET['id'] ?? null;
            if (!$support_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Support request ID is required']);
                return;
            }
            
            $stmt = $pdo->prepare("
                SELECT sr.*, 
                       u.username as requester_name,
                       srq.title as request_title
                FROM support_requests sr
                JOIN users u ON sr.requester_id = u.id
                JOIN service_requests srq ON sr.service_request_id = srq.id
                WHERE sr.id = ?
            ");
            $stmt->execute([$support_id]);
            $support_request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$support_request) {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Support request not found']);
                return;
            }
            
            echo json_encode(['success' => true, 'data' => $support_request]);
            break;
            
        case 'check_status':
            // Check support request status for a specific service request
            $service_request_id = $_GET['service_request_id'] ?? null;
            if (!$service_request_id) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Service request ID is required']);
                return;
            }
            
            // For staff, only check their own service requests
            if ($user_role === 'staff') {
                $stmt = $pdo->prepare("
                    SELECT sr.*, 
                           u.username as requester_name,
                           admin.username as admin_name
                    FROM support_requests sr
                    JOIN users u ON sr.requester_id = u.id
                    LEFT JOIN users admin ON sr.processed_by = admin.id
                    WHERE sr.service_request_id = ? AND sr.requester_id = ?
                    ORDER BY sr.created_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$service_request_id, $current_user]);
            } elseif ($user_role === 'admin') {
                // Admin can check any service request
                $stmt = $pdo->prepare("
                    SELECT sr.*, 
                           u.username as requester_name,
                           admin.username as admin_name
                    FROM support_requests sr
                    JOIN users u ON sr.requester_id = u.id
                    LEFT JOIN users admin ON sr.processed_by = admin.id
                    WHERE sr.service_request_id = ?
                    ORDER BY sr.created_at DESC
                    LIMIT 1
                ");
                $stmt->execute([$service_request_id]);
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            $support_request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($support_request) {
                echo json_encode(['success' => true, 'data' => $support_request]);
            } else {
                echo json_encode(['success' => true, 'data' => null]);
            }
            break;
            
        case 'list':
            // Admin can view all support requests, staff can view their own
            $status = $_GET['status'] ?? null;
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, intval($_GET['limit'] ?? 20));
            $offset = ($page - 1) * $limit;
            
            if ($user_role === 'admin') {
                // Admin can see all, or filter by status
                if ($status) {
                    $where_clause = "WHERE sr.status = ?";
                    $params = [$status];
                } else {
                    $where_clause = "";
                    $params = [];
                }
            } elseif ($user_role === 'staff') {
                // Staff can only see their own support requests
                if ($status) {
                    $where_clause = "WHERE sr.status = ? AND sr.requester_id = ?";
                    $params = [$status, $current_user];
                } else {
                    $where_clause = "WHERE sr.requester_id = ?";
                    $params = [$current_user];
                }
            } else {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            // Count total records
            $count_stmt = $pdo->prepare("
                SELECT COUNT(*) as total
                FROM support_requests sr
                $where_clause
            ");
            $count_stmt->execute($params);
            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get support requests with pagination
            $stmt = $pdo->prepare("
                SELECT sr.*, 
                       u.username as requester_name,
                       srq.title as request_title
                FROM support_requests sr
                JOIN users u ON sr.requester_id = u.id
                JOIN service_requests srq ON sr.service_request_id = srq.id
                $where_clause
                ORDER BY sr.created_at DESC
                LIMIT $limit OFFSET $offset
            ");
            $stmt->execute($params);
            $support_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Support requests data: " . json_encode($support_requests));
            
            echo json_encode([
                'success' => true,
                'data' => $support_requests,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'pages' => ceil($total / $limit)
                ]
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
}

function handlePost($pdo, $action, $current_user, $user_role) {
    // Only staff can create support requests
    if ($user_role !== 'staff') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    $service_request_id = $input['service_request_id'] ?? null;
    $support_type = $input['support_type'] ?? null;
    $support_details = $input['support_details'] ?? null;
    $support_reason = $input['support_reason'] ?? null;
    
    if (!$service_request_id || !$support_type || !$support_details || !$support_reason) {
        error_log("Missing fields - service_request_id: $service_request_id, support_type: $support_type, support_details: $support_details, support_reason: $support_reason");
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'All fields are required']);
        return;
    }
    
    // Validate support type
    $valid_types = ['equipment', 'person', 'department'];
    if (!in_array($support_type, $valid_types)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid support type']);
        return;
    }
    
    // Check if service request exists and is assigned to current user
    $stmt = $pdo->prepare("
        SELECT id, assigned_to, status 
        FROM service_requests 
        WHERE id = ? AND assigned_to = ? AND status = 'in_progress'
    ");
    $stmt->execute([$service_request_id, $current_user]);
    $service_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service_request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Service request not found or not assigned to you']);
        return;
    }
    
    // Check if support request already exists for this service request
    $stmt = $pdo->prepare("
        SELECT id FROM support_requests 
        WHERE service_request_id = ? AND status = 'pending'
    ");
    $stmt->execute([$service_request_id]);
    $existing_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existing_request) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Support request already exists for this service request']);
        return;
    }
    
    // Create support request
    $stmt = $pdo->prepare("
        INSERT INTO support_requests (
            service_request_id, requester_id, support_type, 
            support_details, support_reason, status, created_at
        ) VALUES (?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    $result = $stmt->execute([
        $service_request_id,
        $current_user,
        $support_type,
        $support_details,
        $support_reason
    ]);
    
    if ($result) {
        $support_id = $pdo->lastInsertId();
        
        // Update service request status to 'request_support'
        $update_stmt = $pdo->prepare("
            UPDATE service_requests 
            SET status = 'request_support' 
            WHERE id = ?
        ");
        $update_result = $update_stmt->execute([$service_request_id]);
        
        echo json_encode([
            'success' => true,
            'message' => 'Support request created successfully',
            'data' => ['id' => $support_id]
        ]);
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to create support request']);
    }
}

function handlePut($pdo, $action, $current_user, $user_role) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }
    
    // Check if this is an update operation
    if (isset($input['action']) && $input['action'] === 'update') {
        // Update support request (admin only)
        if ($user_role !== 'admin') {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied. Admin access required.']);
            return;
        }
        
        $support_id = $input['id'] ?? null;
        $support_type = $input['support_type'] ?? null;
        $support_details = $input['support_details'] ?? null;
        $support_reason = $input['support_reason'] ?? null;
        
        if (!$support_id || !$support_type || !$support_details || !$support_reason) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'All fields are required']);
            return;
        }
        
        // Validate support type
        $valid_types = ['equipment', 'person', 'department'];
        if (!in_array($support_type, $valid_types)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid support type']);
            return;
        }
        
        // Check if support request exists
        $stmt = $pdo->prepare("
            SELECT id, status FROM support_requests 
            WHERE id = ?
        ");
        $stmt->execute([$support_id]);
        $support_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$support_request) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Support request not found']);
            return;
        }
        
        // Update support request
        $stmt = $pdo->prepare("
            UPDATE support_requests 
            SET support_type = ?, support_details = ?, support_reason = ?
            WHERE id = ?
        ");
        
        $result = $stmt->execute([$support_type, $support_details, $support_reason, $support_id]);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Support request updated successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to update support request']);
        }
        return;
    }
    
    // Original processing logic
    if ($user_role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $support_id = $input['id'] ?? null;
    $decision = $input['decision'] ?? null;
    $reason = $input['reason'] ?? null;
    
    if (!$support_id || !$decision || !$reason) {
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
    
    // Check if support request exists and is pending
    $stmt = $pdo->prepare("
        SELECT id, status FROM support_requests 
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->execute([$support_id]);
    $support_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$support_request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Support request not found or already processed']);
        return;
    }
    
    // Update support request and service request
    $stmt = $pdo->prepare("
        UPDATE support_requests 
        SET status = ?, admin_reason = ?, processed_by = ?, processed_at = NOW()
        WHERE id = ?
    ");
    
    $result = $stmt->execute([$decision, $reason, $current_user, $support_id]);
    
    if ($result) {
        // Create notifications for support request decision
        try {
            // Get support request details
            $support_stmt = $pdo->prepare("
                SELECT sr.*, u.full_name as requester_name 
                FROM support_requests sr
                LEFT JOIN users u ON sr.user_id = u.id
                WHERE sr.id = ?
            ");
            $support_stmt->execute([$support_id]);
            $support_data = $support_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Get service request ID
            $service_req_stmt = $pdo->prepare("
                SELECT service_request_id FROM support_requests WHERE id = ?
            ");
            $service_req_stmt->execute([$support_id]);
            $service_req_data = $service_req_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($service_req_data) {
                $service_request_id = $service_req_data['service_request_id'];
                
                // Notify all staff and admin about support request decision
                $title = "Yêu cầu hỗ trợ #" . $support_id . " đã được " . ($decision === 'approved' ? 'duyệt' : 'từ chối');
                $message = "Yêu cầu hỗ trợ từ " . $support_data['requester_name'] . " đã được " . ($decision === 'approved' ? 'duyệt' : 'từ chối') . ". Lý do: " . $reason;
                $type = $decision === 'approved' ? 'success' : 'warning';
                
                notifyRole($pdo, 'staff', $title, $message, $type, $support_id, 'support_request');
                notifyRole($pdo, 'admin', $title, $message, $type, $support_id, 'support_request');
                
                // Also notify original requester
                if ($support_data['user_id'] != $current_user) {
                    createNotification($pdo, $support_data['user_id'], $title, $message, $type, $support_id, 'support_request');
                }
                
                // Update service request based on decision
                if ($decision === 'approved') {
                    // Assign to admin and set to in_progress
                    $update_stmt = $pdo->prepare("
                        UPDATE service_requests 
                        SET assigned_to = ?, status = 'in_progress'
                        WHERE id = ?
                    ");
                    $update_result = $update_stmt->execute([$current_user, $service_request_id]);
                    
                    // Notify about assignment if successful
                    if ($update_result) {
                        $assign_title = "Yêu cầu #" . $service_request_id . " đã được giao";
                        $assign_message = "Bạn được giao yêu cầu: (Hỗ trợ) " . $support_data['requester_name'];
                        createNotification($pdo, $current_user, $assign_title, $assign_message, 'info', $service_request_id, 'assignment');
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Failed to create support request notifications: " . $e->getMessage());
        }
        
        // Get service request ID to update
        $service_req_stmt = $pdo->prepare("
            SELECT service_request_id FROM support_requests WHERE id = ?
        ");
        $service_req_stmt->execute([$support_id]);
        $service_req_data = $service_req_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service_req_data) {
            $service_request_id = $service_req_data['service_request_id'];
            
            // Update service request based on decision
            if ($decision === 'approved') {
                // Assign to admin and set to in_progress
                $update_stmt = $pdo->prepare("
                    UPDATE service_requests 
                    SET assigned_to = ?, status = 'in_progress'
                    WHERE id = ?
                ");
                $update_result = $update_stmt->execute([$current_user, $service_request_id]);
                
                error_log("DEBUG: Updating service request $service_request_id to in_progress, assigned to $current_user");
                error_log("DEBUG: Update result: " . ($update_result ? 'SUCCESS' : 'FAILED'));
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Support request approved and assigned to admin',
                    'data' => [
                        'decision' => $decision,
                        'reason' => $reason,
                        'service_request_id' => $service_request_id,
                        'service_request_status' => 'in_progress'
                    ]
                ]);
                
            } elseif ($decision === 'rejected') {
                // Set to rejected
                $update_stmt = $pdo->prepare("
                    UPDATE service_requests 
                    SET status = 'rejected'
                    WHERE id = ?
                ");
                $update_result = $update_stmt->execute([$service_request_id]);
                
                echo json_encode([
                    'success' => true,
                    'message' => 'Support request rejected',
                    'data' => [
                        'decision' => $decision,
                        'reason' => $reason,
                        'service_request_id' => $service_request_id,
                        'service_request_status' => 'rejected'
                    ]
                ]);
                
            } else {
                echo json_encode([
                    'success' => true,
                    'message' => 'Support request processed',
                    'data' => [
                        'decision' => $decision,
                        'reason' => $reason,
                        'service_request_id' => $service_request_id,
                        'service_request_status' => 'processed'
                    ]
                ]);
            }
        }
    } else {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Failed to process support request']);
    }
}

function handleDelete($pdo, $action, $current_user, $user_role) {
    // Only admin can delete support requests
    if ($user_role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        return;
    }
    
    $support_id = $_GET['id'] ?? null;
    
    if (!$support_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Support request ID is required']);
        return;
    }
    
    try {
        // Check if support request exists
        $stmt = $pdo->prepare("
            SELECT sr.*, srq.title as request_title 
            FROM support_requests sr
            JOIN service_requests srq ON sr.service_request_id = srq.id
            WHERE sr.id = ?
        ");
        $stmt->execute([$support_id]);
        $support_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$support_request) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Support request not found']);
            return;
        }
        
        // Check if support request is processed - if so, don't allow deletion
        if ($support_request['status'] !== 'pending') {
            echo json_encode([
                'success' => false, 
                'message' => 'Không thể xóa yêu cầu hỗ trợ này vì đã được xử lý. Trạng thái: ' . $support_request['status']
            ]);
            return;
        }
        
        // Delete support request
        $stmt = $pdo->prepare("DELETE FROM support_requests WHERE id = ?");
        $result = $stmt->execute([$support_id]);
        
        if ($result) {
            // Update service request status back to in_progress
            $update_stmt = $pdo->prepare("
                UPDATE service_requests 
                SET status = 'in_progress' 
                WHERE id = ?
            ");
            $update_result = $update_stmt->execute([$support_request['service_request_id']]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Support request deleted successfully'
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Failed to delete support request']);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
