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

// Debug session at the start
error_log("=== SESSION DEBUG START ===");
error_log("Session status: " . session_status());
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("User ID from session: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("User role from session: " . ($_SESSION['user_role'] ?? 'not set'));
error_log("=== SESSION DEBUG END ===");

// Get user info from session
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? null;

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
    
    if ($action == 'get') {
        error_log("=== GET REQUEST DEBUG ===");
        error_log("Request ID: " . ($_GET['id'] ?? 'not set'));
        error_log("User ID: " . ($user_id ?? 'not set'));
        error_log("User Role: " . ($user_role ?? 'not set'));
        
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            serviceJsonResponse(false, "Invalid request ID");
            return;
        }
        
        try {
            error_log("Attempting database connection...");
            $db = getDbConnection();
            
            if (!$db) {
                error_log("Database connection is null");
                serviceJsonResponse(false, "Database connection failed");
                return;
            }
            
            // Simple query to test basic functionality
            $query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                            u.email as requester_email, u.phone as requester_phone,
                            assigned.full_name as assigned_name, assigned.email as assigned_email
                     FROM service_requests sr
                     LEFT JOIN categories c ON sr.category_id = c.id
                     LEFT JOIN users u ON sr.user_id = u.id
                     LEFT JOIN users assigned ON sr.assigned_to = assigned.id
                     WHERE sr.id = :id";
            
            error_log("Executing query: " . $query);
            error_log("With ID: " . $id);
            
            $stmt = $db->prepare($query);
            if (!$stmt) {
                error_log("Failed to prepare statement");
                serviceJsonResponse(false, "Failed to prepare statement");
                return;
            }
            
            $stmt->bindParam(":id", $id);
            if (!$stmt->execute()) {
                error_log("Failed to execute statement");
                $error = $stmt->errorInfo();
                error_log("Statement error: " . print_r($error, true));
                serviceJsonResponse(false, "Failed to execute statement");
                return;
            }
            
            error_log("Query executed successfully");
            error_log("Row count: " . $stmt->rowCount());
            
            if ($stmt->rowCount() > 0) {
                $request = $stmt->fetch(PDO::FETCH_ASSOC);
                error_log("Request data fetched: " . print_r($request, true));
                
                // Check access permissions
                if ($user_role != 'admin' && $user_role != 'staff' && 
                    $request['user_id'] != $user_id) {
                    error_log("Access denied - user_role: $user_role, user_id: $user_id, request_user_id: " . $request['user_id']);
                    serviceJsonResponse(false, "Access denied");
                    return;
                }
                
                error_log("Access granted, preparing response...");
                
                // Get support request if exists
                try {
                    $support_query = "SELECT sreq.*, admin.full_name as support_admin_name
                                    FROM support_requests sreq
                                    LEFT JOIN users admin ON sreq.processed_by = admin.id
                                    WHERE sreq.service_request_id = :id";
                    $support_stmt = $db->prepare($support_query);
                    $support_stmt->bindParam(":id", $id);
                    $support_stmt->execute();
                    $support_data = $support_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($support_data) {
                        $request['support_request'] = $support_data;
                    } else {
                        $request['support_request'] = null;
                    }
                } catch (Exception $e) {
                    error_log("Support query error: " . $e->getMessage());
                    $request['support_request'] = null;
                }
                
                // Get resolution if exists
                try {
                    $resolution_query = "SELECT r.*, resolver.full_name as resolver_name
                                       FROM resolutions r
                                       LEFT JOIN users resolver ON r.resolved_by = resolver.id
                                       WHERE r.service_request_id = :id";
                    $resolution_stmt = $db->prepare($resolution_query);
                    $resolution_stmt->bindParam(":id", $id);
                    $resolution_stmt->execute();
                    $resolution_data = $resolution_stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if ($resolution_data) {
                        $request['resolution'] = $resolution_data;
                    } else {
                        $request['resolution'] = null;
                    }
                } catch (Exception $e) {
                    error_log("Resolution query error: " . $e->getMessage());
                    $request['resolution'] = null;
                }
                
                error_log("Sending successful response...");
                serviceJsonResponse(true, "Service request retrieved", $request);
            } else {
                error_log("No request found with ID: " . $id);
                serviceJsonResponse(false, "Service request not found");
            }
        } catch (Exception $e) {
            error_log("Get request error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    } else {
        serviceJsonResponse(false, "Invalid action");
    }
} else {
    serviceJsonResponse(false, "Method not allowed");
}
?>
