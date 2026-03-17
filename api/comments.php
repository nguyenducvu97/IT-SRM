<?php
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../lib/EmailHelper.php';

// Helper function for JSON responses (avoid conflict with database.php)
function commentsJsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit();
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

function notifyUsers($pdo, $userIds, $title, $message, $type = 'info', $relatedId = null, $relatedType = null) {
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    foreach ($userIds as $userId) {
        try {
            $stmt->execute([$userId, $title, $message, $type, $relatedId, $relatedType]);
        } catch (Exception $e) {
            error_log("Failed to notify user $userId: " . $e->getMessage());
        }
    }
}

function notifyRequestParticipants($pdo, $requestId, $excludeUserId = null, $title, $message, $type = 'info') {
    try {
        // Get request owner and assigned staff
        $stmt = $pdo->prepare("
            SELECT user_id, assigned_to 
            FROM service_requests 
            WHERE id = ?
        ");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $notifyUsers = [];
        
        // Add request owner if not excluded
        if ($request['user_id'] != $excludeUserId) {
            $notifyUsers[] = $request['user_id'];
        }
        
        // Add assigned staff if not excluded and exists
        if ($request['assigned_to'] && $request['assigned_to'] != $excludeUserId) {
            $notifyUsers[] = $request['assigned_to'];
        }
        
        // Add all admin users
        $stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
        $stmt->execute();
        $admins = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($admins as $adminId) {
            if ($adminId != $excludeUserId) {
                $notifyUsers[] = $adminId;
            }
        }
        
        // Remove duplicates
        $notifyUsers = array_unique($notifyUsers);
        
        if (!empty($notifyUsers)) {
            notifyUsers($pdo, $notifyUsers, $title, $message, $type, $requestId, 'request');
        }
    } catch (Exception $e) {
        error_log("Failed to notify request participants: " . $e->getMessage());
    }
}

// Connect to database using the same configuration as other APIs
$database = new Database();
$db = $database->getConnection();

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
startSession();

if (!isset($_SESSION['user_id'])) {
    commentsJsonResponse(false, "Unauthorized access");
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];

if ($method == 'POST') {
    $data = json_decode(file_get_contents("php://input"));
    
    $service_request_id = isset($data->service_request_id) ? (int)$data->service_request_id : 0;
    $comment = isset($data->comment) ? trim($data->comment) : '';
    
    if ($service_request_id <= 0 || empty($comment)) {
        commentsJsonResponse(false, "Required fields are missing");
    }
    
    $check_query = "SELECT user_id, assigned_to FROM service_requests WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":id", $service_request_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        commentsJsonResponse(false, "Service request not found");
    }
    
    $request = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_role != 'admin' && $user_role != 'staff' && 
        $request['user_id'] != $user_id) {
        commentsJsonResponse(false, "Access denied");
    }
    
    $query = "INSERT INTO comments (service_request_id, user_id, comment) 
             VALUES (:service_request_id, :user_id, :comment)";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":service_request_id", $service_request_id);
    $stmt->bindParam(":user_id", $user_id);
    $stmt->bindParam(":comment", $comment);
    
    if ($stmt->execute()) {
        // Get request details for email notification
        $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email,
                                 staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name
                          FROM service_requests sr
                          LEFT JOIN users u ON sr.user_id = u.id
                          LEFT JOIN users staff ON sr.assigned_to = staff.id
                          LEFT JOIN categories c ON sr.category_id = c.id
                          WHERE sr.id = :id";
        $request_stmt = $db->prepare($request_query);
        $request_stmt->bindParam(":id", $service_request_id);
        $request_stmt->execute();
        $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get commenter info
        $commenter_query = "SELECT full_name, email FROM users WHERE id = :user_id";
        $commenter_stmt = $db->prepare($commenter_query);
        $commenter_stmt->bindParam(":user_id", $user_id);
        $commenter_stmt->execute();
        $commenter_data = $commenter_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Send email notification
        try {
            // Temporarily disable email to test comment functionality
            // $emailHelper = new EmailHelper();
            // $emailHelper->sendNewCommentNotification($request_data, $comment, $commenter_data);
            error_log("Email notification temporarily disabled for testing");
        } catch (Exception $e) {
            error_log("Email notification failed: " . $e->getMessage());
            // Continue even if email fails
        }
        
        // Create notifications for comment
        try {
            // Create title and message
            $title = "Bình luận mới cho yêu cầu #" . $service_request_id;
            $message = $commenter_data['full_name'] . " đã bình luận: " . substr($comment, 0, 100) . (strlen($comment) > 100 ? '...' : '');
            
            // Notify request participants (owner, assigned staff, admins)
            notifyRequestParticipants($db, $service_request_id, $user_id, $title, $message, 'info');
            
        } catch (Exception $e) {
            error_log("Failed to create comment notifications: " . $e->getMessage());
            // Continue even if notification creation fails
        }
        
        commentsJsonResponse(true, "Comment added", ['id' => $db->lastInsertId()]);
    } else {
        commentsJsonResponse(false, "Failed to add comment");
    }
}

elseif ($method == 'DELETE') {
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        commentsJsonResponse(false, "Invalid comment ID");
    }
    
    $check_query = "SELECT user_id FROM comments WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":id", $id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        commentsJsonResponse(false, "Comment not found");
    }
    
    $comment = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_role != 'admin' && $comment['user_id'] != $user_id) {
        commentsJsonResponse(false, "Access denied");
    }
    
    $query = "DELETE FROM comments WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        commentsJsonResponse(true, "Comment deleted");
    } else {
        commentsJsonResponse(false, "Failed to delete comment");
    }
}

else {
    commentsJsonResponse(false, "Method not allowed");
}
?>
