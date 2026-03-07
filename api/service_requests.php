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
require_once '../lib/ImprovedEmailHelper.php';
require_once '../lib/PHPMailerEmailHelper.php'; // Quay lại PHPMailer

// Start session for authentication
startSession();

// Helper function for JSON responses (avoid conflict with database.php)
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
    exit();
}


if (!isLoggedIn()) {
    serviceJsonResponse(false, "Unauthorized access");
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

// Ensure session is started with proper cookie settings
$user_id = getCurrentUserId();
$user_role = getCurrentUserRole();

if ($method == 'GET') {
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action == 'list') {
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
        $offset = ($page - 1) * $limit;
        
        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
        $priority_filter = isset($_GET['priority']) ? $_GET['priority'] : '';
        $category_filter = isset($_GET['category']) ? $_GET['category'] : '';
        
        $where_clause = "WHERE 1=1";
        $params = [];
        
        if ($user_role != 'admin' && $user_role != 'staff') {
            $where_clause .= " AND (sr.user_id = :user_id OR sr.assigned_to = :user_id)";
            $params[':user_id'] = $user_id;
        }
        
        if (!empty($status_filter)) {
            $where_clause .= " AND sr.status = :status";
            $params[':status'] = $status_filter;
        }
        
        if (!empty($priority_filter)) {
            $where_clause .= " AND sr.priority = :priority";
            $params[':priority'] = $priority_filter;
        }
        
        if (!empty($category_filter)) {
            $where_clause .= " AND sr.category_id = :category";
            $params[':category'] = $category_filter;
        }
        
        $query = "SELECT sr.*, u.username as requester_name, c.name as category_name 
                  FROM service_requests sr 
                  LEFT JOIN users u ON sr.user_id = u.id 
                  LEFT JOIN categories c ON sr.category_id = c.id 
                  $where_clause 
                  ORDER BY sr.created_at DESC 
                  LIMIT :limit OFFSET :offset";
        
        $stmt = $db->prepare($query);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        if ($stmt->execute()) {
            $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $count_query = "SELECT COUNT(*) as total FROM service_requests sr $where_clause";
            $count_stmt = $db->prepare($count_query);
            
            foreach ($params as $key => $value) {
                $count_stmt->bindValue($key, $value);
            }
            $count_stmt->execute();
            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get status counts
            $status_counts_array = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0, 'request_support' => 0, 'closed' => 0];
            
            // Calculate actual status counts
            $status_query = "SELECT status, COUNT(*) as count FROM service_requests";
            if ($user_role != 'admin' && $user_role != 'staff') {
                $status_query .= " WHERE user_id = :user_id OR assigned_to = :user_id";
            }
            $status_query .= " GROUP BY status";
            
            $status_stmt = $db->prepare($status_query);
            if ($user_role != 'admin' && $user_role != 'staff') {
                $status_stmt->bindParam(":user_id", $user_id);
            }
            $status_stmt->execute();
            
            $status_results = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($status_results as $result) {
                $status_counts_array[$result['status']] = $result['count'];
            }
            
            serviceJsonResponse(true, "Service requests retrieved", [
                'requests' => $requests,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => ceil($total / $limit)
                ],
                'status_counts' => $status_counts_array
            ]);
        } else {
            serviceJsonResponse(false, "Failed to retrieve service requests");
        }
    }
    
    elseif ($action == 'get') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            serviceJsonResponse(false, "Invalid request ID");
        }
        
        $query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                        u.email as requester_email, u.phone as requester_phone,
                        assigned.full_name as assigned_name, assigned.email as assigned_email,
                        sreq.id as support_request_id, sreq.support_type, sreq.support_details, 
                        sreq.support_reason, sreq.status as support_status, sreq.admin_reason,
                        sreq.processed_by, sreq.processed_at, sreq.created_at as support_created_at,
                        sreq_admin.full_name as support_admin_name
                 FROM service_requests sr
                 LEFT JOIN categories c ON sr.category_id = c.id
                 LEFT JOIN users u ON sr.user_id = u.id
                 LEFT JOIN users assigned ON sr.assigned_to = assigned.id
                 LEFT JOIN support_requests sreq ON sr.id = sreq.service_request_id
                 LEFT JOIN users sreq_admin ON sreq.processed_by = sreq_admin.id
                 WHERE sr.id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            $request = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user_role != 'admin' && $user_role != 'staff' && 
                $request['user_id'] != $user_id && $request['assigned_to'] != $user_id) {
                serviceJsonResponse(false, "Access denied");
            }
            
            // Get attachments for this request
            try {
                $attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 
                                     FROM attachments 
                                     WHERE service_request_id = :id 
                                     ORDER BY uploaded_at ASC";
                $attachments_stmt = $db->prepare($attachments_query);
                $attachments_stmt->bindParam(":id", $id);
                $attachments_stmt->execute();
                
                $attachments = $attachments_stmt->fetchAll(PDO::FETCH_ASSOC);
                $request['attachments'] = $attachments;
            } catch (Exception $e) {
                $request['attachments'] = [];
            }
            
            // Get comments for this request
            try {
                $comments_query = "SELECT c.*, u.full_name as user_name
                                  FROM comments c
                                  LEFT JOIN users u ON c.user_id = u.id
                                  WHERE c.service_request_id = :id
                                  ORDER BY c.created_at ASC";
                
                $comments_stmt = $db->prepare($comments_query);
                $comments_stmt->bindParam(":id", $id);
                $comments_stmt->execute();
                
                $comments = $comments_stmt->fetchAll(PDO::FETCH_ASSOC);
                $request['comments'] = $comments;
            } catch (Exception $e) {
                $request['comments'] = [];
            }
            
            // Get reject request data for this request
            try {
                $reject_query = "SELECT rr.*, 
                                       u.full_name as requester_name,
                                       admin.full_name as admin_name
                                FROM reject_requests rr
                                LEFT JOIN users u ON rr.rejected_by = u.id
                                LEFT JOIN users admin ON rr.processed_by = admin.id
                                WHERE rr.service_request_id = :id
                                ORDER BY rr.created_at DESC
                                LIMIT 1";
                
                $reject_stmt = $db->prepare($reject_query);
                $reject_stmt->bindParam(":id", $id);
                $reject_stmt->execute();
                
                $reject_request = $reject_stmt->fetch(PDO::FETCH_ASSOC);
                $request['reject_request'] = $reject_request ?: null;
            } catch (Exception $e) {
                $request['reject_request'] = null;
            }
            
            // Format support request data if exists
            if ($request['support_request_id']) {
                $request['support_request'] = [
                    'id' => $request['support_request_id'],
                    'support_type' => $request['support_type'],
                    'support_details' => $request['support_details'],
                    'support_reason' => $request['support_reason'],
                    'status' => $request['support_status'],
                    'admin_reason' => $request['admin_reason'],
                    'processed_by' => $request['processed_by'],
                    'processed_at' => $request['processed_at'],
                    'created_at' => $request['support_created_at'],
                    'admin_name' => $request['support_admin_name']
                ];
                
                // Clean up the original fields
                unset($request['support_request_id'], $request['support_type'], 
                      $request['support_details'], $request['support_reason'],
                      $request['support_status'], $request['admin_reason'],
                      $request['processed_by'], $request['processed_at'],
                      $request['support_created_at'], $request['support_admin_name']);
            } else {
                $request['support_request'] = null;
            }
            
            serviceJsonResponse(true, "Service request retrieved", $request);
        } else {
            serviceJsonResponse(false, "Service request not found");
        }
    }
}

elseif ($method == 'POST') {
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
    
    if (empty($title) || empty($description) || $category_id <= 0) {
        serviceJsonResponse(false, "Title, description, and category are required");
        return;
    }
    
    try {
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
            
            // Get request details for email notification
            $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, c.name as category
                              FROM service_requests sr
                              LEFT JOIN users u ON sr.user_id = u.id
                              LEFT JOIN categories c ON sr.category_id = c.id
                              WHERE sr.id = :request_id";
            $request_stmt = $db->prepare($request_query);
            $request_stmt->bindParam(":request_id", $request_id);
            $request_stmt->execute();
            $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
            
            // Map data to template variables
            $email_data = array(
                'id' => $request_data['id'],
                'title' => $request_data['title'],
                'requester_name' => $request_data['requester_name'],
                'category' => $request_data['category'],
                'priority' => $request_data['priority'],
                'description' => $request_data['description']
            );
            
            // Send email notification to staff and admin
            try {
                $emailHelper = new PHPMailerEmailHelper(); // Use PHPMailerEmailHelper with new notification logic
                $emailHelper->sendNewRequestNotification($email_data);
            } catch (Exception $e) {
                error_log("Email notification failed: " . $e->getMessage());
                // Continue even if email fails
            }
            
            // Handle file uploads if any
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $upload_dir = 'uploads/requests/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $files = $_FILES['attachments'];
                foreach ($files['name'] as $key => $name) {
                    if ($files['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $files['tmp_name'][$key];
                        $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '_' . $name;
                        $upload_path = $upload_dir . $new_filename;
                        
                        if (move_uploaded_file($tmp_name, $upload_path)) {
                            // Insert attachment record
                            $attach_query = "INSERT INTO attachments 
                                           (service_request_id, filename, original_name, file_size, mime_type, uploaded_by, uploaded_at) 
                                           VALUES (:request_id, :filename, :original_name, :file_size, :mime_type, :uploaded_by, NOW())";
                            $attach_stmt = $db->prepare($attach_query);
                            $attach_stmt->bindParam(":request_id", $request_id);
                            $attach_stmt->bindParam(":filename", $new_filename);
                            $attach_stmt->bindParam(":original_name", $name);
                            $attach_stmt->bindParam(":file_size", $files['size'][$key]);
                            $attach_stmt->bindParam(":mime_type", $files['type'][$key]);
                            $attach_stmt->bindParam(":uploaded_by", $user_id);
                            $attach_stmt->execute();
                        }
                    }
                }
            }
            
            serviceJsonResponse(true, "Service request created successfully", ['id' => $request_id]);
        } else {
            serviceJsonResponse(false, "Failed to create service request");
        }
    } catch (Exception $e) {
        serviceJsonResponse(false, "Database error: " . $e->getMessage());
    }
}

elseif ($method == 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';
    
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
        
        if ($request_id <= 0 || empty($title) || empty($description) || $category_id <= 0) {
            serviceJsonResponse(false, "Request ID, title, description, and category are required");
            return;
        }
        
        try {
            // Check if request exists
            $check_query = "SELECT id FROM service_requests WHERE id = :request_id";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":request_id", $request_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                serviceJsonResponse(false, "Service request not found");
                return;
            }
            
            // Validate assigned_to if provided
            if ($assigned_to && $assigned_to > 0) {
                $user_check_query = "SELECT id FROM users WHERE id = :user_id AND role IN ('admin', 'staff')";
                $user_check_stmt = $db->prepare($user_check_query);
                $user_check_stmt->bindParam(":user_id", $assigned_to);
                $user_check_stmt->execute();
                
                if ($user_check_stmt->rowCount() == 0) {
                    serviceJsonResponse(false, "Invalid staff member assigned");
                    return;
                }
            }
            
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
                serviceJsonResponse(true, "Service request updated successfully");
            } else {
                serviceJsonResponse(false, "Failed to update service request");
            }
        } catch (Exception $e) {
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    }
    
    elseif ($action == 'reject_request') {
        $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
        $reject_reason = isset($input['reject_reason']) ? trim($input['reject_reason']) : '';
        $reject_details = isset($input['reject_details']) ? trim($input['reject_details']) : '';
        
        if ($request_id <= 0 || empty($reject_reason)) {
            serviceJsonResponse(false, "Request ID and reject reason are required");
            return;
        }
        
        // Only staff can reject requests
        if ($user_role != 'staff') {
            serviceJsonResponse(false, "Access denied");
            return;
        }
        
        try {
            // Check if request exists and is assigned to current user
            $check_query = "SELECT id, assigned_to, status FROM service_requests 
                           WHERE id = :request_id AND assigned_to = :user_id AND status = 'in_progress'";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":request_id", $request_id);
            $check_stmt->bindParam(":user_id", $user_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                serviceJsonResponse(false, "Request not found or not assigned to you");
                return;
            }
            
            // Calculate actual status counts
            $status_query = "SELECT status, COUNT(*) as count FROM service_requests";
            if ($user_role != 'admin' && $user_role != 'staff') {
                $status_query .= " WHERE user_id = :user_id OR assigned_to = :user_id";
            }
            $status_query .= " GROUP BY status";
            
            $status_stmt = $db->prepare($status_query);
            if ($user_role != 'admin' && $user_role != 'staff') {
                $status_stmt->bindParam(":user_id", $user_id);
            }
            $status_stmt->execute();
            
            $status_results = $status_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($status_results as $result) {
                $status_counts_array[$result['status']] = $result['count'];
            }
            
            // Check if reject request already exists
            $existing_query = "SELECT id FROM reject_requests 
                               WHERE service_request_id = :request_id AND status = 'pending'";
            $existing_stmt = $db->prepare($existing_query);
            $existing_stmt->bindParam(":request_id", $request_id);
            $existing_stmt->execute();
            
            if ($existing_stmt->rowCount() > 0) {
                serviceJsonResponse(false, "Reject request already exists for this service request");
                return;
            }
            
            // Create reject request
            $insert_query = "INSERT INTO reject_requests 
                             (service_request_id, rejected_by, reject_reason, reject_details, status, created_at) 
                             VALUES (:request_id, :rejected_by, :reject_reason, :reject_details, 'pending', NOW())";
            $insert_stmt = $db->prepare($insert_query);
            $insert_stmt->bindParam(":request_id", $request_id);
            $insert_stmt->bindParam(":rejected_by", $user_id);
            $insert_stmt->bindParam(":reject_reason", $reject_reason);
            $insert_stmt->bindParam(":reject_details", $reject_details);
            
            if ($insert_stmt->execute()) {
                serviceJsonResponse(true, "Reject request submitted successfully");
            } else {
                serviceJsonResponse(false, "Failed to submit reject request");
            }
        } catch (Exception $e) {
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    }
    
    elseif ($action == 'accept_request') {
        $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
        
        if ($request_id <= 0) {
            serviceJsonResponse(false, "Request ID is required");
            return;
        }
        
        // Only staff can accept requests
        if ($user_role != 'staff') {
            serviceJsonResponse(false, "Access denied");
            return;
        }
        
        try {
            // Check if request exists and is available for assignment
            // Available statuses: 'open' or 'request_support' (when support request is rejected)
            $check_query = "SELECT id, assigned_to, status FROM service_requests 
                           WHERE id = :request_id AND (status = 'open' OR status = 'request_support') 
                           AND (assigned_to IS NULL OR assigned_to = 0)";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":request_id", $request_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                // Get detailed info for debugging
                $debug_query = "SELECT id, assigned_to, status FROM service_requests WHERE id = :request_id";
                $debug_stmt = $db->prepare($debug_query);
                $debug_stmt->bindParam(":request_id", $request_id);
                $debug_stmt->execute();
                $debug_info = $debug_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($debug_info) {
                    $status = $debug_info['status'];
                    $assigned = $debug_info['assigned_to'];
                    serviceJsonResponse(false, "Request not available for assignment. Current status: '$status', Assigned to: '$assigned'");
                } else {
                    serviceJsonResponse(false, "Request not found with ID: $request_id");
                }
                return;
            }
            
            // Update request to assign to staff and set to in_progress
            $update_query = "UPDATE service_requests 
                           SET assigned_to = :user_id, status = 'in_progress', updated_at = NOW() 
                           WHERE id = :request_id";
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":request_id", $request_id);
            $update_stmt->bindParam(":user_id", $user_id);
            
            if ($update_stmt->execute()) {
                // Get request details for email notification AFTER the update
                $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 
                                         staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name
                                  FROM service_requests sr
                                  LEFT JOIN users u ON sr.user_id = u.id
                                  LEFT JOIN users staff ON sr.assigned_to = staff.id
                                  LEFT JOIN categories c ON sr.category_id = c.id
                                  WHERE sr.id = :request_id";
                $request_stmt = $db->prepare($request_query);
                $request_stmt->bindParam(":request_id", $request_id);
                $request_stmt->execute();
                $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
                
                // Send email notification to requester about assignment
                try {
                    $emailHelper = new PHPMailerEmailHelper(); // Use PHPMailerEmailHelper for actual email sending
                    $emailHelper->sendStatusUpdateNotification($request_data, $request_data['assigned_name']);
                } catch (Exception $e) {
                    error_log("Email notification failed: " . $e->getMessage());
                    // Continue even if email fails
                }
                
                serviceJsonResponse(true, "Request accepted successfully");
            } else {
                serviceJsonResponse(false, "Failed to accept request");
            }
        } catch (Exception $e) {
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    }
    
    else {
        serviceJsonResponse(false, "Invalid action");
    }
}

elseif ($method == 'DELETE') {
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
        // Check if request exists
        $check_query = "SELECT id, title FROM service_requests WHERE id = :request_id";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->bindParam(":request_id", $request_id);
        $check_stmt->execute();
        
        if ($check_stmt->rowCount() == 0) {
            serviceJsonResponse(false, "Service request not found");
            return;
        }
        
        $request = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Check for foreign key constraints and get counts for confirmation
        $constraints = [];
        
        // Check comments
        $comments_query = "SELECT COUNT(*) as count FROM comments WHERE service_request_id = :request_id";
        $comments_stmt = $db->prepare($comments_query);
        $comments_stmt->bindParam(":request_id", $request_id);
        $comments_stmt->execute();
        $comments_count = $comments_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($comments_count > 0) {
            $constraints[] = "{$comments_count} bình luận";
        }
        
        // Check attachments
        $attachments_query = "SELECT COUNT(*) as count FROM attachments WHERE service_request_id = :request_id";
        $attachments_stmt = $db->prepare($attachments_query);
        $attachments_stmt->bindParam(":request_id", $request_id);
        $attachments_stmt->execute();
        $attachments_count = $attachments_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($attachments_count > 0) {
            $constraints[] = "{$attachments_count} tệp đính kèm";
        }
        
        // Check resolutions
        $resolutions_query = "SELECT COUNT(*) as count FROM resolutions WHERE service_request_id = :request_id";
        $resolutions_stmt = $db->prepare($resolutions_query);
        $resolutions_stmt->bindParam(":request_id", $request_id);
        $resolutions_stmt->execute();
        $resolutions_count = $resolutions_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($resolutions_count > 0) {
            $constraints[] = "{$resolutions_count} giải quyết";
        }
        
        // Check support requests
        $support_query = "SELECT COUNT(*) as count FROM support_requests WHERE service_request_id = :request_id";
        $support_stmt = $db->prepare($support_query);
        $support_stmt->bindParam(":request_id", $request_id);
        $support_stmt->execute();
        $support_count = $support_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($support_count > 0) {
            $constraints[] = "{$support_count} yêu cầu hỗ trợ";
        }
        
        // Check reject requests
        $reject_query = "SELECT COUNT(*) as count FROM reject_requests WHERE service_request_id = :request_id";
        $reject_stmt = $db->prepare($reject_query);
        $reject_stmt->bindParam(":request_id", $request_id);
        $reject_stmt->execute();
        $reject_count = $reject_stmt->fetch(PDO::FETCH_ASSOC)['count'];
        if ($reject_count > 0) {
            $constraints[] = "{$reject_count} yêu cầu từ chối";
        }
        
        // Show confirmation message with related data counts
        if (!empty($constraints) && !$force_delete) {
            $constraint_list = implode(", ", $constraints);
            serviceJsonResponse(false, "Xóa yêu cầu '{$request['title']}' sẽ xóa cả các dữ liệu liên quan: {$constraint_list}. Bạn có chắc chắn muốn tiếp tục?", "confirm_delete");
            return;
        }
        
        // Start transaction for cascade deletion
        $db->beginTransaction();
        
        try {
            // Delete related data in correct order to respect foreign keys
            
            // Delete comments first
            if ($comments_count > 0) {
                $delete_comments = "DELETE FROM comments WHERE service_request_id = :request_id";
                $delete_comments_stmt = $db->prepare($delete_comments);
                $delete_comments_stmt->bindParam(":request_id", $request_id);
                $delete_comments_stmt->execute();
            }
            
            // Delete attachments
            if ($attachments_count > 0) {
                $delete_attachments = "DELETE FROM attachments WHERE service_request_id = :request_id";
                $delete_attachments_stmt = $db->prepare($delete_attachments);
                $delete_attachments_stmt->bindParam(":request_id", $request_id);
                $delete_attachments_stmt->execute();
            }
            
            // Delete resolutions
            if ($resolutions_count > 0) {
                $delete_resolutions = "DELETE FROM resolutions WHERE service_request_id = :request_id";
                $delete_resolutions_stmt = $db->prepare($delete_resolutions);
                $delete_resolutions_stmt->bindParam(":request_id", $request_id);
                $delete_resolutions_stmt->execute();
            }
            
            // Delete support requests
            if ($support_count > 0) {
                $delete_support = "DELETE FROM support_requests WHERE service_request_id = :request_id";
                $delete_support_stmt = $db->prepare($delete_support);
                $delete_support_stmt->bindParam(":request_id", $request_id);
                $delete_support_stmt->execute();
            }
            
            // Delete reject requests
            if ($reject_count > 0) {
                $delete_reject = "DELETE FROM reject_requests WHERE service_request_id = :request_id";
                $delete_reject_stmt = $db->prepare($delete_reject);
                $delete_reject_stmt->bindParam(":request_id", $request_id);
                $delete_reject_stmt->execute();
            }
            
            // Finally delete the service request
            $delete_query = "DELETE FROM service_requests WHERE id = :request_id";
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->bindParam(":request_id", $request_id);
            
            if ($delete_stmt->execute()) {
                $db->commit();
                $deleted_items = [];
                if ($comments_count > 0) $deleted_items[] = "{$comments_count} bình luận";
                if ($attachments_count > 0) $deleted_items[] = "{$attachments_count} tệp đính kèm";
                if ($resolutions_count > 0) $deleted_items[] = "{$resolutions_count} giải quyết";
                if ($support_count > 0) $deleted_items[] = "{$support_count} yêu cầu hỗ trợ";
                if ($reject_count > 0) $deleted_items[] = "{$reject_count} yêu cầu từ chối";
                
                $deleted_text = !empty($deleted_items) ? " (đã xóa: " . implode(", ", $deleted_items) . ")" : "";
                serviceJsonResponse(true, "Service request deleted successfully{$deleted_text}");
            } else {
                $db->rollBack();
                serviceJsonResponse(false, "Failed to delete service request");
            }
        } catch (Exception $e) {
            $db->rollBack();
            serviceJsonResponse(false, "Database error: " . $e->getMessage());
        }
    } catch (Exception $e) {
        serviceJsonResponse(false, "Database error: " . $e->getMessage());
    }
}

else {
    serviceJsonResponse(false, "Method not allowed");
}
?>
