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
require_once 'notification_helper.php';

// Start session for authentication
startSession();

// Debug session at the start
error_log("=== SESSION DEBUG START ===");
error_log("Session status: " . session_status());
error_log("Session ID: " . session_id());
error_log("Session data: " . print_r($_SESSION, true));
error_log("User ID from session: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("User role from session: " . ($_SESSION['role'] ?? 'not set'));
error_log("=== SESSION DEBUG END ===");

// Get user info from session
$user_id = getCurrentUserId();
$user_role = getCurrentUserRole();

// Debug function results
error_log("=== FUNCTION DEBUG ===");
error_log("getCurrentUserId(): " . ($user_id ?? 'null'));
error_log("getCurrentUserRole(): " . ($user_role ?? 'null'));
error_log("=== FUNCTION DEBUG END ===");

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
        // Load requests list
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
        $priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
        $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
        
        try {
            $db = getDatabaseConnection();
            
            // Build WHERE clause
            $where_conditions = [];
            $params = [];
            
            if (!empty($status_filter)) {
                $where_conditions[] = "sr.status = :status";
                $params[':status'] = $status_filter;
            }
            
            if (!empty($priority_filter)) {
                $where_conditions[] = "sr.priority = :priority";
                $params[':priority'] = $priority_filter;
            }
            
            if (!empty($category_filter)) {
                $where_conditions[] = "sr.category_id = :category";
                $params[':category'] = $category_filter;
            }
            
            // Add role-based filtering
            if ($user_role != 'admin') {
                if ($user_role == 'staff') {
                    // Staff can see all requests
                    // No additional filtering needed
                } else {
                    // Regular users only see their own requests
                    $where_conditions[] = "sr.user_id = :user_id";
                    $params[':user_id'] = $user_id;
                }
            }
            
            $where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';
            
            // Count total requests
            $count_query = "SELECT COUNT(*) as total FROM service_requests sr $where_clause";
            $count_stmt = $db->prepare($count_query);
            foreach ($params as $key => $value) {
                $count_stmt->bindValue($key, $value);
            }
            $count_stmt->execute();
            $total_requests = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get requests with pagination
            $query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name,
                             assigned.full_name as assigned_name
                      FROM service_requests sr
                      LEFT JOIN categories c ON sr.category_id = c.id
                      LEFT JOIN users u ON sr.user_id = u.id
                      LEFT JOIN users assigned ON sr.assigned_to = assigned.id
                      $where_clause
                      ORDER BY sr.created_at DESC
                      LIMIT :limit OFFSET :offset";
            
            $stmt = $db->prepare($query);
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get status counts
            $status_counts_query = "SELECT status, COUNT(*) as count FROM service_requests sr";
            $status_counts_where = '';
            $status_counts_params = [];
            
            if ($user_role != 'admin') {
                if ($user_role == 'staff') {
                    // Staff sees all requests
                } else {
                    // Regular users only see their own requests
                    $status_counts_where = " WHERE sr.user_id = :user_id";
                    $status_counts_params[':user_id'] = $user_id;
                }
            }
            
            $status_counts_query .= $status_counts_where . " GROUP BY status";
            $status_counts_stmt = $db->prepare($status_counts_query);
            foreach ($status_counts_params as $key => $value) {
                $status_counts_stmt->bindValue($key, $value);
            }
            $status_counts_stmt->execute();
            $status_counts = [];
            while ($row = $status_counts_stmt->fetch(PDO::FETCH_ASSOC)) {
                $status_counts[$row['status']] = $row['count'];
            }
            
            $total_pages = ceil($total_requests / $limit);
            
            serviceJsonResponse(true, "Requests loaded successfully", [
                'requests' => $requests,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $total_pages,
                    'total_requests' => $total_requests,
                    'limit' => $limit
                ],
                'status_counts' => $status_counts
            ]);
            
        } catch (Exception $e) {
            error_log("List requests error: " . $e->getMessage());
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    }
    elseif ($action == 'get') {
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
            $db = getDatabaseConnection();
            
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
                error_log("Current user: ID=$user_id, Role=$user_role");
                error_log("Request user_id: " . $request['user_id']);
                
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
} elseif ($method == 'DELETE') {
    // Delete service request (admin only)
    if ($user_role != 'admin') {
        serviceJsonResponse(false, "Access denied. Admin access required.");
        return;
    }
    
    $request_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    $force_delete = isset($_GET['force']) ? $_GET['force'] === 'true' : false;
    
    if ($request_id <= 0) {
        serviceJsonResponse(false, "Request ID is required");
        return;
    }
    
    try {
        error_log("=== DELETE REQUEST DEBUG ===");
        error_log("Request ID: " . $request_id);
        error_log("Force delete: " . ($force_delete ? 'true' : 'false'));
        error_log("User ID: " . $user_id);
        error_log("User Role: " . $user_role);
        
        $db = getDatabaseConnection();
        
        // Check if request exists
        $check_query = "SELECT id, title FROM service_requests WHERE id = :request_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":request_id", $request_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            serviceJsonResponse(false, "Service request not found");
            return;
        }
        
        $request_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        error_log("Request to delete: " . print_r($request_data, true));
        
        // Check for related data
        $related_data = [];
        
        // Check for comments
        $comments_query = "SELECT COUNT(*) as count FROM comments WHERE service_request_id = :request_id";
        $comments_stmt = $db->prepare($comments_query);
        $comments_stmt->bindParam(":request_id", $request_id);
        $comments_stmt->execute();
        $related_data['comments'] = $comments_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Check for attachments
        $attachments_query = "SELECT COUNT(*) as count FROM attachments WHERE service_request_id = :request_id";
        $attachments_stmt = $db->prepare($attachments_query);
        $attachments_stmt->bindParam(":request_id", $request_id);
        $attachments_stmt->execute();
        $related_data['attachments'] = $attachments_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Check for support requests
        $support_query = "SELECT COUNT(*) as count FROM support_requests WHERE service_request_id = :request_id";
        $support_stmt = $db->prepare($support_query);
        $support_stmt->bindParam(":request_id", $request_id);
        $support_stmt->execute();
        $related_data['support_requests'] = $support_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Check for resolutions
        $resolution_query = "SELECT COUNT(*) as count FROM resolutions WHERE service_request_id = :request_id";
        $resolution_stmt = $db->prepare($resolution_query);
        $resolution_stmt->bindParam(":request_id", $request_id);
        $resolution_stmt->execute();
        $related_data['resolutions'] = $resolution_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Check for reject requests
        $reject_query = "SELECT COUNT(*) as count FROM reject_requests WHERE service_request_id = :request_id";
        $reject_stmt = $db->prepare($reject_query);
        $reject_stmt->bindParam(":request_id", $request_id);
        $reject_stmt->execute();
        $related_data['reject_requests'] = $reject_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        // Check for feedback
        $feedback_query = "SELECT COUNT(*) as count FROM request_feedback WHERE service_request_id = :request_id";
        $feedback_stmt = $db->prepare($feedback_query);
        $feedback_stmt->bindParam(":request_id", $request_id);
        $feedback_stmt->execute();
        $related_data['feedback'] = $feedback_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        
        error_log("Related data: " . print_r($related_data, true));
        
        // If not force delete and there's related data, return error with details
        if (!$force_delete && (
            $related_data['comments'] > 0 || 
            $related_data['attachments'] > 0 || 
            $related_data['support_requests'] > 0 || 
            $related_data['resolutions'] > 0 || 
            $related_data['reject_requests'] > 0 || 
            $related_data['feedback'] > 0
        )) {
            $message = "Cannot delete request #{$request_id} because it has related data:\n";
            if ($related_data['comments'] > 0) $message .= "- {$related_data['comments']} comment(s)\n";
            if ($related_data['attachments'] > 0) $message .= "- {$related_data['attachments']} attachment(s)\n";
            if ($related_data['support_requests'] > 0) $message .= "- {$related_data['support_requests']} support request(s)\n";
            if ($related_data['resolutions'] > 0) $message .= "- {$related_data['resolutions']} resolution(s)\n";
            if ($related_data['reject_requests'] > 0) $message .= "- {$related_data['reject_requests']} reject request(s)\n";
            if ($related_data['feedback'] > 0) $message .= "- {$related_data['feedback']} feedback(s)\n";
            $message .= "Use force=true to delete anyway.";
            
            serviceJsonResponse(false, $message);
            return;
        }
        
        // Start transaction for safe deletion
        $db->beginTransaction();
        
        try {
            error_log("Starting deletion process...");
            
            // Delete comments
            if ($related_data['comments'] > 0) {
                $delete_comments = "DELETE FROM comments WHERE service_request_id = :request_id";
                $delete_comments_stmt = $db->prepare($delete_comments);
                $delete_comments_stmt->bindParam(":request_id", $request_id);
                $delete_comments_stmt->execute();
                error_log("Deleted comments");
            }
            
            // Delete attachments
            if ($related_data['attachments'] > 0) {
                $delete_attachments = "DELETE FROM attachments WHERE service_request_id = :request_id";
                $delete_attachments_stmt = $db->prepare($delete_attachments);
                $delete_attachments_stmt->bindParam(":request_id", $request_id);
                $delete_attachments_stmt->execute();
                error_log("Deleted attachments");
            }
            
            // Delete support requests
            if ($related_data['support_requests'] > 0) {
                $delete_support = "DELETE FROM support_requests WHERE service_request_id = :request_id";
                $delete_support_stmt = $db->prepare($delete_support);
                $delete_support_stmt->bindParam(":request_id", $request_id);
                $delete_support_stmt->execute();
                error_log("Deleted support requests");
            }
            
            // Delete reject requests
            if ($related_data['reject_requests'] > 0) {
                $delete_reject = "DELETE FROM reject_requests WHERE service_request_id = :request_id";
                $delete_reject_stmt = $db->prepare($delete_reject);
                $delete_reject_stmt->bindParam(":request_id", $request_id);
                $delete_reject_stmt->execute();
                error_log("Deleted reject requests");
            }
            
            // Delete resolutions
            if ($related_data['resolutions'] > 0) {
                $delete_resolutions = "DELETE FROM resolutions WHERE service_request_id = :request_id";
                $delete_resolutions_stmt = $db->prepare($delete_resolutions);
                $delete_resolutions_stmt->bindParam(":request_id", $request_id);
                $delete_resolutions_stmt->execute();
                error_log("Deleted resolutions");
            }
            
            // Delete feedback
            if ($related_data['feedback'] > 0) {
                $delete_feedback = "DELETE FROM request_feedback WHERE service_request_id = :request_id";
                $delete_feedback_stmt = $db->prepare($delete_feedback);
                $delete_feedback_stmt->bindParam(":request_id", $request_id);
                $delete_feedback_stmt->execute();
                error_log("Deleted feedback");
            }
            
            // Finally delete the main request
            $delete_request = "DELETE FROM service_requests WHERE id = :request_id";
            $delete_request_stmt = $db->prepare($delete_request);
            $delete_request_stmt->bindParam(":request_id", $request_id);
            $delete_request_stmt->execute();
            
            $db->commit();
            
            error_log("Successfully deleted request #{$request_id}");
            serviceJsonResponse(true, "Service request #{$request_id} deleted successfully");
            
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Delete error: " . $e->getMessage());
            error_log("Error trace: " . $e->getTraceAsString());
            serviceJsonResponse(false, "Error deleting request: " . $e->getMessage());
        }
        
    } catch (Exception $e) {
        error_log("General delete error: " . $e->getMessage());
        serviceJsonResponse(false, "Database error: " . $e->getMessage());
    }
} elseif ($method == 'POST') {
    // Create new service request
    error_log("=== POST REQUEST DEBUG ===");
    error_log("User ID: " . ($user_id ?? 'not set'));
    error_log("User Role: " . ($user_role ?? 'not set'));
    
    // Check if this is FormData (file upload) or JSON
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'multipart/form-data') !== false) {
        // Handle FormData (file upload)
        $title = isset($_POST['title']) ? trim($_POST['title']) : '';
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';
    } else {
        // Handle JSON
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            serviceJsonResponse(false, "Invalid JSON data");
            return;
        }
        
        $title = isset($input['title']) ? trim($input['title']) : '';
        $description = isset($input['description']) ? trim($input['description']) : '';
        $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;
        $priority = isset($input['priority']) ? $input['priority'] : 'medium';
    }
    
    error_log("Request data: title=" . $title . ", category_id=" . $category_id . ", priority=" . $priority);
    
    if (empty($title) || empty($description) || $category_id <= 0) {
        serviceJsonResponse(false, "Title, description, and category are required");
        return;
    }
    
    try {
        $db = getDatabaseConnection();
        
        // Validate category exists
        $category_check = "SELECT id FROM categories WHERE id = :category_id";
        $category_stmt = $db->prepare($category_check);
        $category_stmt->bindParam(":category_id", $category_id);
        $category_stmt->execute();
        
        if ($category_stmt->rowCount() == 0) {
            serviceJsonResponse(false, "Invalid category");
            return;
        }
        
        error_log("Inserting new request...");
        
        // Insert new request
        $query = "INSERT INTO service_requests 
                  (user_id, category_id, title, description, priority, status, created_at, updated_at) 
                  VALUES (:user_id, :category_id, :title, :description, :priority, 'open', NOW(), NOW())";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":category_id", $category_id);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":priority", $priority);
        
        if ($stmt->execute()) {
            $request_id = $db->lastInsertId();
            error_log("Request created with ID: " . $request_id);
            
            // Create notifications for new request
            try {
                $currentUser = [
                    'id' => $user_id,
                    'full_name' => $_SESSION['full_name'] ?? '',
                    'role' => $user_role,
                    'email' => $_SESSION['email'] ?? ''
                ];
                if ($currentUser) {
                    createNotifications('request_created', $request_id, [], $currentUser);
                }
            } catch (Exception $e) {
                error_log("Failed to create notifications: " . $e->getMessage());
                // Continue even if notification creation fails
            }
            
            serviceJsonResponse(true, "Service request created successfully", ['id' => $request_id]);
        } else {
            error_log("Failed to create request");
            serviceJsonResponse(false, "Failed to create service request");
        }
    } catch (Exception $e) {
        error_log("Create request error: " . $e->getMessage());
        error_log("Error trace: " . $e->getTraceAsString());
        serviceJsonResponse(false, "Database error: " . $e->getMessage());
    }
} elseif ($method == 'PUT') {
    // Update service request (status, assignment, etc.)
    error_log("=== PUT REQUEST DEBUG ===");
    error_log("User ID: " . ($user_id ?? 'not set'));
    error_log("User Role: " . ($user_role ?? 'not set'));
    
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        serviceJsonResponse(false, "Invalid JSON data");
        return;
    }
    
    $action = isset($input['action']) ? $input['action'] : '';
    error_log("PUT action: " . $action);
    
    if ($action == 'update') {
        // Update service request (admin only)
        if ($user_role != 'admin') {
            serviceJsonResponse(false, "Access denied. Admin access required.");
            return;
        }
        
        $request_id = isset($input['id']) ? (int)$input['id'] : 0;
        $title = isset($input['title']) ? trim($input['title']) : '';
        $description = isset($input['description']) ? trim($input['description']) : '';
        $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;
        $priority = isset($input['priority']) ? $input['priority'] : 'medium';
        $status = isset($input['status']) ? $input['status'] : 'open';
        $assigned_to = isset($input['assigned_to']) ? (int)$input['assigned_to'] : null;
        
        if ($request_id <= 0) {
            serviceJsonResponse(false, "Request ID is required");
            return;
        }
        
        try {
            $db = getDatabaseConnection();
            
            // Check if request exists
            $check_query = "SELECT id FROM service_requests WHERE id = :request_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":request_id", $request_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                serviceJsonResponse(false, "Service request not found");
                return;
            }
            
            // Get old status for comparison
            $old_status_query = "SELECT status, assigned_to FROM service_requests WHERE id = :request_id";
            $old_stmt = $db->prepare($old_status_query);
            $old_stmt->bindParam(":request_id", $request_id);
            $old_stmt->execute();
            $old_data = $old_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update the request
            $update_query = "UPDATE service_requests 
                             SET title = :title, description = :description, category_id = :category_id, 
                                 priority = :priority, status = :status, assigned_to = :assigned_to, 
                                 updated_at = NOW() 
                             WHERE id = :request_id";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":title", $title);
            $update_stmt->bindParam(":description", $description);
            $update_stmt->bindParam(":category_id", $category_id);
            $update_stmt->bindParam(":priority", $priority);
            $update_stmt->bindParam(":status", $status);
            $update_stmt->bindParam(":assigned_to", $assigned_to, PDO::PARAM_INT);
            $update_stmt->bindParam(":request_id", $request_id);
            
            if ($update_stmt->execute()) {
                // Create notifications using new helper
                try {
                    $currentUser = [
                        'id' => $user_id,
                        'full_name' => $_SESSION['full_name'] ?? '',
                        'role' => $user_role,
                        'email' => $_SESSION['email'] ?? ''
                    ];
                    if ($currentUser) {
                        // Create status update notification
                        if ($old_data['status'] !== $status) {
                            createNotifications('status_update', $request_id, ['new_status' => $status], $currentUser);
                        }
                        
                        // Create assignment notification
                        if ($assigned_to && $old_data['assigned_to'] != $assigned_to) {
                            createNotifications('request_assigned', $request_id, [], $currentUser);
                        }
                    }
                } catch (Exception $e) {
                    error_log("Failed to create notifications: " . $e->getMessage());
                    // Continue even if notification creation fails
                }
                
                serviceJsonResponse(true, "Service request updated successfully");
            } else {
                serviceJsonResponse(false, "Failed to update service request");
            }
        } catch (Exception $e) {
            error_log("Update request error: " . $e->getMessage());
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    }
    elseif ($action == 'close_request') {
        // Close request (user only)
        $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
        $rating = isset($input['rating']) ? (int)$input['rating'] : null;
        $feedback = isset($input['feedback']) ? trim($input['feedback']) : null;
        $software_feedback = isset($input['software_feedback']) ? trim($input['software_feedback']) : null;
        $would_recommend = isset($input['would_recommend']) ? $input['would_recommend'] : null;
        $ease_of_use = isset($input['ease_of_use']) ? (int)$input['ease_of_use'] : null;
        $speed_stability = isset($input['speed_stability']) ? (int)$input['speed_stability'] : null;
        $requirement_meeting = isset($input['requirement_meeting']) ? (int)$input['requirement_meeting'] : null;
        
        if ($request_id <= 0) {
            serviceJsonResponse(false, "Request ID is required");
            return;
        }
        
        // Only request owner or admin can close
        try {
            $db = getDatabaseConnection();
            
            // Check if request exists and user owns it
            $check_query = "SELECT user_id, status FROM service_requests WHERE id = :request_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":request_id", $request_id);
            $check_stmt->execute();
            $request = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$request) {
                serviceJsonResponse(false, "Service request not found");
                return;
            }
            
            if ($user_role != 'admin' && $request['user_id'] != $user_id) {
                serviceJsonResponse(false, "Access denied. You can only close your own requests.");
                return;
            }
            
            if ($request['status'] != 'resolved') {
                serviceJsonResponse(false, "Only resolved requests can be closed.");
                return;
            }
            
            $db->beginTransaction();
            
            // Insert feedback record
            $insert_feedback_query = "INSERT INTO request_feedback 
                                     (service_request_id, created_by, rating, feedback, software_feedback, would_recommend, 
                                      ease_of_use, speed_stability, requirement_meeting) 
                                     VALUES (:request_id, :created_by, :rating, :feedback, :software_feedback, :would_recommend,
                                            :ease_of_use, :speed_stability, :requirement_meeting)";
            $insert_feedback_stmt = $db->prepare($insert_feedback_query);
            $insert_feedback_stmt->bindParam(":request_id", $request_id);
            $insert_feedback_stmt->bindParam(":created_by", $user_id);
            $insert_feedback_stmt->bindParam(":rating", $rating);
            $insert_feedback_stmt->bindParam(":feedback", $feedback);
            $insert_feedback_stmt->bindParam(":software_feedback", $software_feedback);
            $insert_feedback_stmt->bindParam(":would_recommend", $would_recommend);
            $insert_feedback_stmt->bindParam(":ease_of_use", $ease_of_use);
            $insert_feedback_stmt->bindParam(":speed_stability", $speed_stability);
            $insert_feedback_stmt->bindParam(":requirement_meeting", $requirement_meeting);
            
            if (!$insert_feedback_stmt->execute()) {
                $db->rollBack();
                serviceJsonResponse(false, "Failed to create feedback record");
                return;
            }
            
            // Update service request status to closed
            $update_request_query = "UPDATE service_requests 
                                    SET status = 'closed', updated_at = NOW() 
                                    WHERE id = :request_id";
            $update_request_stmt = $db->prepare($update_request_query);
            $update_request_stmt->bindParam(":request_id", $request_id);
            
            if (!$update_request_stmt->execute()) {
                $db->rollBack();
                serviceJsonResponse(false, "Failed to update request status");
                return;
            }
            
            // Create notifications for request closure and feedback
            try {
                $currentUser = [
                    'id' => $user_id,
                    'full_name' => $_SESSION['full_name'] ?? '',
                    'role' => $user_role,
                    'email' => $_SESSION['email'] ?? ''
                ];
                if ($currentUser) {
                    // Create close notification
                    createNotifications('request_closed', $request_id, [], $currentUser);
                    
                    // If there's a rating, create rating notification
                    if ($rating && $rating > 0) {
                        // Create custom message for rating
                        $ratingData = [
                            'rating' => $rating,
                            'feedback' => $feedback,
                            'has_feedback' => !empty($feedback)
                        ];
                        createNotifications('request_rated', $request_id, $ratingData, $currentUser);
                    }
                }
            } catch (Exception $e) {
                error_log("Failed to create notifications: " . $e->getMessage());
                // Continue even if notification creation fails
            }
            
            $db->commit();
            serviceJsonResponse(true, "Request closed successfully with feedback");
            
        } catch (Exception $e) {
            if (isset($db) && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Close request error: " . $e->getMessage());
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    }
    else {
        serviceJsonResponse(false, "Invalid action");
    }
} else {
    serviceJsonResponse(false, "Method not allowed");
}
?>
