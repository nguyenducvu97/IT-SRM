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
if (session_status() == PHP_SESSION_NONE) {
    session_start([
        'cookie_lifetime' => 86400,
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax',
        'cookie_path' => '/',
        'cookie_domain' => 'localhost'
    ]);
}

if (!isLoggedIn()) {
    jsonResponse(false, "Unauthorized access");
    exit();
}

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
            $status_counts_array = ['open' => 0, 'in_progress' => 0, 'resolved' => 0, 'rejected' => 0, 'closed' => 0];
            
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
                        assigned.full_name as assigned_name, assigned.email as assigned_email
                 FROM service_requests sr
                 LEFT JOIN categories c ON sr.category_id = c.id
                 LEFT JOIN users u ON sr.user_id = u.id
                 LEFT JOIN users assigned ON sr.assigned_to = assigned.id
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
            $attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 
                                 FROM attachments 
                                 WHERE service_request_id = :id 
                                 ORDER BY uploaded_at ASC";
            $attachments_stmt = $db->prepare($attachments_query);
            $attachments_stmt->bindParam(":id", $id);
            $attachments_stmt->execute();
            
            $attachments = $attachments_stmt->fetchAll(PDO::FETCH_ASSOC);
            $request['attachments'] = $attachments;
            
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
            
            // Get support request data if exists
            try {
                $support_query = "SELECT sr.*, u.full_name as requester_name, 
                                       processor.full_name as processor_name
                                FROM support_requests sr
                                LEFT JOIN users u ON sr.requester_id = u.id
                                LEFT JOIN users processor ON sr.processed_by = processor.id
                                WHERE sr.service_request_id = :id
                                ORDER BY sr.created_at DESC
                                LIMIT 1";
                
                $support_stmt = $db->prepare($support_query);
                $support_stmt->bindParam(":id", $id);
                $support_stmt->execute();
                
                if ($support_stmt->rowCount() > 0) {
                    $request['support_request'] = $support_stmt->fetch(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                error_log("Support requests table may not exist: " . $e->getMessage());
                // Continue without support request data if table doesn't exist
            }
            
            // Get reject request data if exists
            try {
                $reject_query = "SELECT rr.*, u.full_name as requester_name, 
                                        processor.full_name as processor_name
                                 FROM reject_requests rr
                                 LEFT JOIN users u ON rr.rejected_by = u.id
                                 LEFT JOIN users processor ON rr.processed_by = processor.id
                                 WHERE rr.service_request_id = :id
                                 ORDER BY rr.created_at DESC
                                 LIMIT 1";
                
                $reject_stmt = $db->prepare($reject_query);
                $reject_stmt->bindParam(":id", $id);
                $reject_stmt->execute();
                
                if ($reject_stmt->rowCount() > 0) {
                    $request['reject_request'] = $reject_stmt->fetch(PDO::FETCH_ASSOC);
                }
            } catch (Exception $e) {
                error_log("Reject requests table may not exist: " . $e->getMessage());
                // Continue without reject request data if table doesn't exist
            }
            
            // Get resolution data if request is resolved
            if ($request['status'] === 'resolved') {
                $resolution_query = "SELECT r.*, u.full_name as resolver_name 
                                   FROM resolutions r
                                   LEFT JOIN users u ON r.resolved_by = u.id
                                   WHERE r.service_request_id = :id";
                
                $resolution_stmt = $db->prepare($resolution_query);
                $resolution_stmt->bindParam(":id", $id);
                $resolution_stmt->execute();
                
                if ($resolution_stmt->rowCount() > 0) {
                    $request['resolution'] = $resolution_stmt->fetch(PDO::FETCH_ASSOC);
                }
            }
            
            jsonResponse(true, "Service request retrieved", $request);
        } else {
            jsonResponse(false, "Service request not found");
        }
    }
}

elseif ($method == 'POST') {
    error_log("Service request POST received");
    
    // Handle both JSON and FormData requests
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($contentType, 'multipart/form-data') !== false) {
        // File upload request
        $title = isset($_POST['title']) ? sanitizeInput($_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitizeInput($_POST['description']) : '';
        $category_id = isset($_POST['category_id']) ? (int)$_POST['category_id'] : 0;
        $priority = isset($_POST['priority']) ? $_POST['priority'] : 'medium';
        
        error_log("Form data - title: $title, category_id: $category_id, priority: $priority");
        error_log("User ID from session: " . $user_id);
        error_log("User role from session: " . $user_role);
        
        if (empty($title) || empty($description) || $category_id <= 0) {
            error_log("Validation failed: title=$title, description=$description, category_id=$category_id");
            jsonResponse(false, "Required fields are missing");
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Insert service request
            $query = "INSERT INTO service_requests (title, description, category_id, user_id, priority) 
                     VALUES (:title, :description, :category_id, :user_id, :priority)";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(":title", $title);
            $stmt->bindParam(":description", $description);
            $stmt->bindParam(":category_id", $category_id);
            $stmt->bindParam(":user_id", $user_id);
            $stmt->bindParam(":priority", $priority);
            
            if (!$stmt->execute()) {
                throw new Exception("Failed to create service request");
            }
            
            $request_id = $db->lastInsertId();
            error_log("Created request with ID: $request_id");
            
            // Handle file uploads
            if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
                $uploadDir = '../uploads/requests/';
                
                // Create upload directory if it doesn't exist
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                
                $files = $_FILES['attachments'];
                $fileCount = count($files['name']);
                
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($files['error'][$i] === UPLOAD_ERR_OK) {
                        $fileName = $files['name'][$i];
                        $fileTmpName = $files['tmp_name'][$i];
                        $fileSize = $files['size'][$i];
                        $fileType = $files['type'][$i];
                        
                        // Validate file
                        $maxSize = 5 * 1024 * 1024; // 5MB
                        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 
                                       'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                                       'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                                       'text/plain'];
                        
                        if ($fileSize > $maxSize) {
                            error_log("File too large: $fileName");
                            continue;
                        }
                        
                        if (!in_array($fileType, $allowedTypes)) {
                            error_log("File type not allowed: $fileName ($fileType)");
                            continue;
                        }
                        
                        // Generate unique filename
                        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                        $uniqueName = uniqid() . '_' . time() . '.' . $fileExtension;
                        $filePath = $uploadDir . $uniqueName;
                        
                        if (move_uploaded_file($fileTmpName, $filePath)) {
                            // Insert file record
                            $fileQuery = "INSERT INTO attachments (service_request_id, filename, original_name, file_size, mime_type, uploaded_by) 
                                          VALUES (:request_id, :filename, :original_name, :file_size, :mime_type, :uploaded_by)";
                            
                            $fileStmt = $db->prepare($fileQuery);
                            $fileStmt->bindParam(":request_id", $request_id);
                            $fileStmt->bindParam(":filename", $uniqueName);
                            $fileStmt->bindParam(":original_name", $fileName);
                            $fileStmt->bindParam(":file_size", $fileSize);
                            $fileStmt->bindParam(":mime_type", $fileType);
                            $fileStmt->bindParam(":uploaded_by", $user_id);
                            
                            if (!$fileStmt->execute()) {
                                error_log("Failed to save file record: $fileName");
                            } else {
                                error_log("Successfully uploaded: $fileName -> $uniqueName");
                            }
                        } else {
                            error_log("Failed to move uploaded file: $fileName");
                        }
                    } else {
                        error_log("Upload error for file: " . $files['name'][$i] . " - Error: " . $files['error'][$i]);
                    }
                }
            }
            
            $db->commit();
            jsonResponse(true, "Service request created", ['id' => $request_id]);
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Transaction failed: " . $e->getMessage());
            jsonResponse(false, "Failed to create service request: " . $e->getMessage());
        }
        
    } else {
        // Original JSON request (backward compatibility)
        $data = json_decode(file_get_contents("php://input"));
        error_log("Request data: " . print_r($data, true));
        
        $title = isset($data->title) ? sanitizeInput($data->title) : '';
        $description = isset($data->description) ? sanitizeInput($data->description) : '';
        $category_id = isset($data->category_id) ? (int)$data->category_id : 0;
        $priority = isset($data->priority) ? $data->priority : 'medium';
        
        error_log("User ID from session: " . $user_id);
        error_log("User role from session: " . $user_role);
        
        if (empty($title) || empty($description) || $category_id <= 0) {
            error_log("Validation failed: title=$title, description=$description, category_id=$category_id");
            jsonResponse(false, "Required fields are missing");
        }
        
        $query = "INSERT INTO service_requests (title, description, category_id, user_id, priority) 
                 VALUES (:title, :description, :category_id, :user_id, :priority)";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":title", $title);
        $stmt->bindParam(":description", $description);
        $stmt->bindParam(":category_id", $category_id);
        $stmt->bindParam(":user_id", $user_id);
        $stmt->bindParam(":priority", $priority);
        
        if ($stmt->execute()) {
            $request_id = $db->lastInsertId();
            jsonResponse(true, "Service request created", ['id' => $request_id]);
        } else {
            jsonResponse(false, "Failed to create service request");
        }
    }
}

elseif ($method == 'PUT') {
    $data = json_decode(file_get_contents("php://input"));
    
    // Support both 'id' and 'request_id' for compatibility
    $id = isset($data->id) ? (int)$data->id : (isset($data->request_id) ? (int)$data->request_id : 0);
    $status = isset($data->status) ? $data->status : '';
    $assigned_to = isset($data->assigned_to) ? (int)$data->assigned_to : null;
    $action = isset($data->action) ? $data->action : '';
    
    if ($id <= 0) {
        jsonResponse(false, "Invalid request ID");
    }
    
    $check_query = "SELECT user_id, assigned_to, status FROM service_requests WHERE id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":id", $id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        jsonResponse(false, "Service request not found");
    }
    
    $request = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user_role != 'admin' && $user_role != 'staff' && 
        $request['user_id'] != $user_id && $request['assigned_to'] != $user_id) {
        jsonResponse(false, "Access denied");
    }
    
    // Handle resolve request action
    if ($action == 'resolve' && ($user_role == 'staff' || $user_role == 'admin')) {
        if ($request['status'] != 'in_progress') {
            jsonResponse(false, "Chỉ có thể giải quyết yêu cầu đang ở trạng thái 'đang xử lý'");
        }
        
        $error_description = isset($data->error_description) ? sanitizeInput($data->error_description) : '';
        $error_type = isset($data->error_type) ? sanitizeInput($data->error_type) : '';
        $replacement_materials = isset($data->replacement_materials) ? sanitizeInput($data->replacement_materials) : '';
        $solution_method = isset($data->solution_method) ? sanitizeInput($data->solution_method) : '';
        
        if (empty($error_description) || empty($error_type) || empty($solution_method)) {
            jsonResponse(false, "Vui lòng điền đầy đủ thông tin bắt buộc: Mô tả lỗi, Loại lỗi, Cách sửa lỗi");
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Insert resolution record
            $resolution_query = "INSERT INTO resolutions (service_request_id, error_description, error_type, replacement_materials, solution_method, resolved_by) 
                                VALUES (:service_request_id, :error_description, :error_type, :replacement_materials, :solution_method, :resolved_by)";
            
            $resolution_stmt = $db->prepare($resolution_query);
            $resolution_stmt->bindParam(":service_request_id", $id);
            $resolution_stmt->bindParam(":error_description", $error_description);
            $resolution_stmt->bindParam(":error_type", $error_type);
            $resolution_stmt->bindParam(":replacement_materials", $replacement_materials);
            $resolution_stmt->bindParam(":solution_method", $solution_method);
            $resolution_stmt->bindParam(":resolved_by", $user_id);
            
            if (!$resolution_stmt->execute()) {
                throw new Exception("Failed to create resolution record");
            }
            
            // Update service request status to resolved
            $update_query = "UPDATE service_requests SET 
                              status = 'resolved',
                              resolved_at = CURRENT_TIMESTAMP,
                              updated_at = CURRENT_TIMESTAMP
                              WHERE id = :id";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":id", $id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update request status");
            }
            
            $db->commit();
            jsonResponse(true, "Yêu cầu đã được giải quyết thành công");
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Resolve transaction failed: " . $e->getMessage());
            jsonResponse(false, "Failed to resolve request: " . $e->getMessage());
        }
        exit();
    }
    
    // Handle reject request action
    if ($action == 'reject_request' && ($user_role == 'staff' || $user_role == 'admin')) {
        error_log("=== DEBUG REJECT REQUEST API ===");
        error_log("Request data: " . json_encode($request));
        error_log("User ID: " . $user_id);
        error_log("Request status: " . $request['status']);
        
        if ($request['status'] != 'in_progress') {
            error_log("Error: Request not in progress");
            jsonResponse(false, "Chỉ có thể từ chối yêu cầu đang ở trạng thái 'đang xử lý'");
        }
        
        if ($request['assigned_to'] != $user_id) {
            error_log("Error: Request not assigned to current user");
            jsonResponse(false, "Chỉ người được giao yêu cầu mới có thể từ chối");
        }
        
        $reject_reason = isset($data->reject_reason) ? sanitizeInput($data->reject_reason) : '';
        $reject_details = isset($data->reject_details) ? sanitizeInput($data->reject_details) : '';
        
        if (empty($reject_reason)) {
            jsonResponse(false, "Vui lòng nhập lý do từ chối");
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Insert reject request for admin approval
            $reject_query = "INSERT INTO reject_requests (service_request_id, rejected_by, reject_reason, reject_details, status, created_at) 
                             VALUES (:service_request_id, :rejected_by, :reject_reason, :reject_details, 'pending', NOW())";
            
            $reject_stmt = $db->prepare($reject_query);
            $reject_stmt->bindParam(":service_request_id", $id);
            $reject_stmt->bindParam(":rejected_by", $user_id);
            $reject_stmt->bindParam(":reject_reason", $reject_reason);
            $reject_stmt->bindParam(":reject_details", $reject_details);
            
            if (!$reject_stmt->execute()) {
                throw new Exception("Failed to create reject request");
            }
            
            // Update service request - set back to open and unassign
            $update_fields = [
                "status = 'open'",
                "assigned_to = NULL",
                "updated_at = CURRENT_TIMESTAMP"
            ];
            
            if ($accepted_at_exists) {
                $update_fields[] = "accepted_at = NULL";
            }
            
            $update_query = "UPDATE service_requests SET " . implode(", ", $update_fields) . " WHERE id = :id";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":id", $id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update request status");
            }
            
            $db->commit();
            jsonResponse(true, "Yêu cầu từ chối đã được gửi đến admin để duyệt");
            
        } catch (Exception $e) {
            error_log("Reject transaction failed: " . $e->getMessage());
            jsonResponse(false, "Failed to reject request: " . $e->getMessage());
        }
        exit();
    }
    
    // Handle process reject request action (admin only)
    if ($action == 'process_reject_request' && $user_role == 'admin') {
        error_log("=== DEBUG PROCESS REJECT REQUEST API ===");
        
        $reject_id = isset($data->reject_id) ? (int)$data->reject_id : 0;
        $decision = isset($data->decision) ? $data->decision : '';
        $admin_reason = isset($data->admin_reason) ? sanitizeInput($data->admin_reason) : '';
        
        if ($reject_id <= 0) {
            jsonResponse(false, "Invalid reject request ID");
        }
        
        if (!in_array($decision, ['approved', 'rejected'])) {
            jsonResponse(false, "Invalid decision");
        }
        
        if (empty($admin_reason)) {
            jsonResponse(false, "Vui lòng nhập lý do xử lý");
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Get reject request details
            $reject_query = "SELECT * FROM reject_requests WHERE id = :id AND status = 'pending'";
            $reject_stmt = $db->prepare($reject_query);
            $reject_stmt->bindParam(":id", $reject_id);
            $reject_stmt->execute();
            $reject_request = $reject_stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$reject_request) {
                throw new Exception("Reject request not found or already processed");
            }
            
            // Update reject request
            $update_reject_query = "UPDATE reject_requests 
                                   SET status = :status, admin_reason = :admin_reason, 
                                       processed_by = :processed_by, processed_at = NOW()
                                   WHERE id = :id";
            
            $update_reject_stmt = $db->prepare($update_reject_query);
            $update_reject_stmt->bindParam(":status", $decision);
            $update_reject_stmt->bindParam(":admin_reason", $admin_reason);
            $update_reject_stmt->bindParam(":processed_by", $user_id);
            $update_reject_stmt->bindParam(":id", $reject_id);
            
            if (!$update_reject_stmt->execute()) {
                throw new Exception("Failed to update reject request");
            }
            
            // If approved, close the service request
            if ($decision == 'approved') {
                $close_query = "UPDATE service_requests 
                               SET status = 'closed', closed_at = NOW(), updated_at = NOW()
                               WHERE id = :service_request_id";
                
                $close_stmt = $db->prepare($close_query);
                $close_stmt->bindParam(":service_request_id", $reject_request['service_request_id']);
                
                if (!$close_stmt->execute()) {
                    throw new Exception("Failed to close service request");
                }
            }
            
            $db->commit();
            $message = $decision == 'approved' ? 
                "Yêu cầu từ chối đã được duyệt, yêu cầu đã được đóng" : 
                "Yêu cầu từ chối đã bị từ chối";
            jsonResponse(true, $message);
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Process reject transaction failed: " . $e->getMessage());
            jsonResponse(false, "Failed to process reject request: " . $e->getMessage());
        }
        exit();
    }
    
    // Handle close request action
    if ($action == 'close_request') {
        error_log("=== DEBUG CLOSE REQUEST API ===");
        error_log("Request data: " . json_encode($request));
        error_log("User ID: " . $user_id);
        error_log("Request status: " . $request['status']);
        
        if ($request['status'] != 'resolved') {
            error_log("Error: Request not resolved");
            jsonResponse(false, "Chỉ có thể đóng yêu cầu đã được giải quyết");
        }
        
        // Check if current user is the requester (support both user_id and requester_id fields)
        $requester_id = $request['user_id'] ?? $request['requester_id'] ?? null;
        error_log("Requester ID: " . $requester_id);
        
        if ($requester_id != $user_id) {
            error_log("Error: User not authorized. Requester: $requester_id, Current: $user_id");
            jsonResponse(false, "Chỉ người tạo yêu cầu mới có thể đóng yêu cầu");
        }
        
        $rating = isset($data->rating) ? (int)$data->rating : 0;
        $feedback = isset($data->feedback) ? sanitizeInput($data->feedback) : '';
        $would_recommend = isset($data->would_recommend) ? sanitizeInput($data->would_recommend) : '';
        
        if ($rating < 1 || $rating > 5) {
            jsonResponse(false, "Vui lòng chọn đánh giá từ 1 đến 5 sao");
        }
        
        // Start transaction
        $db->beginTransaction();
        
        try {
            // Insert feedback record
            $feedback_query = "INSERT INTO request_feedback (service_request_id, rating, feedback, would_recommend, created_by) 
                              VALUES (:service_request_id, :rating, :feedback, :would_recommend, :created_by)";
            
            $feedback_stmt = $db->prepare($feedback_query);
            $feedback_stmt->bindParam(":service_request_id", $id);
            $feedback_stmt->bindParam(":rating", $rating);
            $feedback_stmt->bindParam(":feedback", $feedback);
            $feedback_stmt->bindParam(":would_recommend", $would_recommend);
            $feedback_stmt->bindParam(":created_by", $user_id);
            
            if (!$feedback_stmt->execute()) {
                throw new Exception("Failed to create feedback record");
            }
            
            // Update service request status to closed
            $update_query = "UPDATE service_requests SET 
                              status = 'closed',
                              closed_at = CURRENT_TIMESTAMP,
                              updated_at = CURRENT_TIMESTAMP
                              WHERE id = :id";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(":id", $id);
            
            if (!$update_stmt->execute()) {
                throw new Exception("Failed to update request status");
            }
            
            $db->commit();
            jsonResponse(true, "Yêu cầu đã được đóng thành công");
            
        } catch (Exception $e) {
            $db->rollback();
            error_log("Close transaction failed: " . $e->getMessage());
            jsonResponse(false, "Failed to close request: " . $e->getMessage());
        }
        exit();
    }
    
    // Handle accept request action
    if ($action == 'accept' && ($user_role == 'staff' || $user_role == 'admin')) {
        if ($request['assigned_to'] != null && $request['assigned_to'] != $user_id) {
            jsonResponse(false, "Request is already assigned to another staff member");
        }
        
        $update_fields = [
            "assigned_to = :assigned_to", 
            "status = 'in_progress'",
            "updated_at = CURRENT_TIMESTAMP"
        ];
        
        if ($accepted_at_exists) {
            $update_fields[] = "accepted_at = CURRENT_TIMESTAMP";
        }
        
        $query = "UPDATE service_requests SET " . implode(", ", $update_fields) . " WHERE id = :id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(":id", $id);
        $stmt->bindParam(":assigned_to", $user_id);
        
        if ($stmt->execute()) {
            jsonResponse(true, "Request accepted successfully");
        } else {
            jsonResponse(false, "Failed to accept request");
        }
        exit();
    }
    
    $update_fields = [];
    $params = [":id" => $id];
    
    if (!empty($status)) {
        $update_fields[] = "status = :status";
        $params[":status"] = $status;
        
        if ($status == 'resolved') {
            $update_fields[] = "resolved_at = CURRENT_TIMESTAMP";
        }
    }
    
    if ($assigned_to !== null) {
        if ($user_role != 'admin' && $user_role != 'staff') {
            jsonResponse(false, "Only staff can assign requests");
        }
        $update_fields[] = "assigned_to = :assigned_to";
        $params[":assigned_to"] = $assigned_to;
    }
    
    if (empty($update_fields)) {
        jsonResponse(false, "No fields to update");
    }
    
    $query = "UPDATE service_requests SET " . implode(", ", $update_fields) . " WHERE id = :id";
    
    $stmt = $db->prepare($query);
    
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    
    if ($stmt->execute()) {
        jsonResponse(true, "Service request updated");
    } else {
        jsonResponse(false, "Failed to update service request");
    }
}

elseif ($method == 'DELETE') {
    if ($user_role != 'admin') {
        jsonResponse(false, "Only administrators can delete requests");
    }
    
    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
    
    if ($id <= 0) {
        jsonResponse(false, "Invalid request ID");
    }
    
    $query = "DELETE FROM service_requests WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":id", $id);
    
    if ($stmt->execute()) {
        jsonResponse(true, "Service request deleted");
    } else {
        jsonResponse(false, "Failed to delete service request");
    }
}

else {
    jsonResponse(false, "Method not allowed");
}
?>
