<?php
header("Content-Type: application/json; charset=UTF-8");
header('Access-Control-Allow-Origin: http://localhost');
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
require_once 'notification_helper.php';

// Start session for authentication
startSession();

// Get user info from session
$user_id = getCurrentUserId();
$user_role = getCurrentUserRole();

// Helper function for JSON responses
function serviceJsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action == 'list') {
        // Simple list without filters first
        try {
            $db = getDatabaseConnection();
            
            // Get total count
            $count_query = "SELECT COUNT(*) as total FROM service_requests";
            $count_stmt = $db->prepare($count_query);
            $count_stmt->execute();
            $total = $count_stmt->fetchColumn();
            
            // Get all requests
            $query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                             CASE WHEN sr.assigned_to IS NOT NULL THEN u2.full_name ELSE NULL END as assignee_name
                     FROM service_requests sr
                     LEFT JOIN categories c ON sr.category_id = c.id
                     LEFT JOIN users u ON sr.user_id = u.id
                     LEFT JOIN users u2 ON sr.assigned_to = u2.id
                     ORDER BY sr.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->execute();
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            serviceJsonResponse(true, "Requests loaded successfully", [
                'requests' => $requests,
                'pagination' => [
                    'page' => 1,
                    'limit' => $total,
                    'total' => $total,
                    'pages' => 1
                ]
            ]);
            
        } catch (Exception $e) {
            error_log("List requests error: " . $e->getMessage());
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    } elseif ($action == 'detail') {
        // Get request details
        $request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($request_id <= 0) {
            serviceJsonResponse(false, "Request ID is required");
        }
        
        try {
            $db = getDatabaseConnection();
            
            $query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                             CASE WHEN sr.assigned_to IS NOT NULL THEN u2.full_name ELSE NULL END as assignee_name
                     FROM service_requests sr
                     LEFT JOIN categories c ON sr.category_id = c.id
                     LEFT JOIN users u ON sr.user_id = u.id
                     LEFT JOIN users u2 ON sr.assigned_to = u2.id
                     WHERE sr.id = :request_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':request_id', $request_id);
            $stmt->execute();
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                serviceJsonResponse(false, "Request not found");
            }
            
            serviceJsonResponse(true, "Request details loaded successfully", $request);
            
        } catch (Exception $e) {
            error_log("Get request detail error: " . $e->getMessage());
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    } else {
        serviceJsonResponse(false, "Invalid action");
    }
} else {
    serviceJsonResponse(false, "Method not allowed");
}
?>
