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
            
            jsonResponse(true, "Service requests retrieved", [
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
            jsonResponse(false, "Failed to retrieve service requests");
        }
    }
    
    elseif ($action == 'get') {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        if ($id <= 0) {
            jsonResponse(false, "Invalid request ID");
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
                jsonResponse(false, "Access denied");
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
            
            jsonResponse(true, "Service request retrieved", $request);
        } else {
            jsonResponse(false, "Service request not found");
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
            jsonResponse(false, "Invalid JSON data");
            return;
        }
        
        $title = isset($input['title']) ? trim($input['title']) : '';
        $description = isset($input['description']) ? trim($input['description']) : '';
        $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;
        $priority = isset($input['priority']) ? $input['priority'] : 'medium';
    }
    
    if (empty($title) || empty($description) || $category_id <= 0) {
        jsonResponse(false, "Title, description, and category are required");
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
                                           (service_request_id, filename, original_name, file_size, mime_type, uploaded_at) 
                                           VALUES (:request_id, :filename, :original_name, :file_size, :mime_type, NOW())";
                            $attach_stmt = $db->prepare($attach_query);
                            $attach_stmt->bindParam(":request_id", $request_id);
                            $attach_stmt->bindParam(":filename", $new_filename);
                            $attach_stmt->bindParam(":original_name", $name);
                            $attach_stmt->bindParam(":file_size", $files['size'][$key]);
                            $attach_stmt->bindParam(":mime_type", $files['type'][$key]);
                            $attach_stmt->execute();
                        }
                    }
                }
            }
            
            jsonResponse(true, "Service request created successfully", ['id' => $request_id]);
        } else {
            jsonResponse(false, "Failed to create service request");
        }
    } catch (Exception $e) {
        jsonResponse(false, "Database error: " . $e->getMessage());
    }
}

elseif ($method == 'PUT') {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = isset($input['action']) ? $input['action'] : '';
    
    if ($action == 'reject_request') {
        $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
        $reject_reason = isset($input['reject_reason']) ? trim($input['reject_reason']) : '';
        $reject_details = isset($input['reject_details']) ? trim($input['reject_details']) : '';
        
        if ($request_id <= 0 || empty($reject_reason)) {
            jsonResponse(false, "Request ID and reject reason are required");
            return;
        }
        
        // Only staff can reject requests
        if ($user_role != 'staff') {
            jsonResponse(false, "Access denied");
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
                jsonResponse(false, "Request not found or not assigned to you");
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
                jsonResponse(false, "Reject request already exists for this service request");
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
                jsonResponse(true, "Reject request submitted successfully");
            } else {
                jsonResponse(false, "Failed to submit reject request");
            }
        } catch (Exception $e) {
            jsonResponse(false, "Database error: " . $e->getMessage());
        }
    }
    
    elseif ($action == 'accept_request') {
        $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
        
        if ($request_id <= 0) {
            jsonResponse(false, "Request ID is required");
            return;
        }
        
        // Only staff can accept requests
        if ($user_role != 'staff') {
            jsonResponse(false, "Access denied");
            return;
        }
        
        try {
            // Check if request exists and is open
            $check_query = "SELECT id, assigned_to, status FROM service_requests 
                           WHERE id = :request_id AND status = 'open'";
            $check_stmt = $db->prepare($check_query);
            $check_stmt->bindParam(":request_id", $request_id);
            $check_stmt->execute();
            
            if ($check_stmt->rowCount() == 0) {
                jsonResponse(false, "Request not found or not available for assignment");
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
                jsonResponse(true, "Request accepted successfully");
            } else {
                jsonResponse(false, "Failed to accept request");
            }
        } catch (Exception $e) {
            jsonResponse(false, "Database error: " . $e->getMessage());
        }
    }
    
    else {
        jsonResponse(false, "Invalid action");
    }
}

else {
    jsonResponse(false, "Method not allowed");
}
?>
