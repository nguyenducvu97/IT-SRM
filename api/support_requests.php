<?php
// IT Service Request Support Requests API
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Disable error display to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/session.php';
require_once '../config/database.php';
require_once '../config/email.php';
require_once '../lib/PHPMailerEmailHelper.php';
require_once '../lib/ServiceRequestNotificationHelper.php';

// Start session
startSession();

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
    error_log("Database connection established successfully");
    
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            error_log("Handling GET request with action: " . $action);
            handleGet($pdo, $action, $current_user, $user_role);
            break;
        case 'POST':
            error_log("Handling POST request with action: " . $action);
            handlePost($pdo, $action, $current_user, $user_role);
            break;
        case 'PUT':
            error_log("Handling PUT request with action: " . $action);
            handlePut($pdo, $action, $current_user, $user_role);
            break;
        case 'DELETE':
            error_log("Handling DELETE request with action: " . $action);
            handleDelete($pdo, $action, $current_user, $user_role);
            break;
        default:
            error_log("Method not allowed: " . $_SERVER['REQUEST_METHOD']);
            http_response_code(405);
            echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    }
} catch (Exception $e) {
    error_log("Server error in support_requests.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
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
            
            // Get attachments for this support request
            try {
                $attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 
                                     FROM support_request_attachments 
                                     WHERE support_request_id = :id 
                                     ORDER BY uploaded_at ASC";
                $attachments_stmt = $pdo->prepare($attachments_query);
                $attachments_stmt->bindParam(":id", $support_id);
                $attachments_stmt->execute();
                
                $attachments = $attachments_stmt->fetchAll(PDO::FETCH_ASSOC);
                $support_request['attachments'] = $attachments;
            } catch (Exception $e) {
                $support_request['attachments'] = [];
            }
            
            // Filter sensitive information based on user role
            if ($user_role === 'user') {
                // Remove admin decision information for regular users
                unset($support_request['admin_reason']);
                unset($support_request['processed_by']);
                unset($support_request['processed_at']);
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
                // Filter sensitive information based on user role
                if ($user_role === 'staff') {
                    // Staff can see admin decisions
                    // Keep all data as is
                } elseif ($user_role === 'admin') {
                    // Admin can see all data
                    // Keep all data as is
                }
                
                echo json_encode(['success' => true, 'data' => $support_request]);
            } else {
                echo json_encode(['success' => true, 'data' => null]);
            }
            break;
            
        case 'list':
            error_log("Handling list action for user role: " . $user_role);
            // Admin can view all support requests, staff can view their own
            $status = $_GET['status'] ?? null;
            $page = max(1, intval($_GET['page'] ?? 1));
            $limit = max(1, intval($_GET['limit'] ?? 20));
            $offset = ($page - 1) * $limit;
            
            error_log("List parameters - status: " . ($status ?? 'all') . ", page: $page, limit: $limit");
            
            if ($user_role === 'admin') {
                // Admin can see all, or filter by status
                if ($status && $status !== 'all') {
                    $where_clause = "WHERE sr.status = ?";
                    $params = [$status];
                } else {
                    $where_clause = "";
                    $params = [];
                }
            } elseif ($user_role === 'staff') {
                // Staff can see all support requests for processing
                if ($status && $status !== 'all') {
                    $where_clause = "WHERE sr.status = ?";
                    $params = [$status];
                } else {
                    $where_clause = "";
                    $params = [];
                }
            } else {
                error_log("Access denied for user role: " . $user_role);
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied']);
                return;
            }
            
            error_log("Where clause: '$where_clause', params count: " . count($params));
            
            // Count total records
            $count_query = "
                SELECT COUNT(*) as total
                FROM support_requests sr
                $where_clause
            ";
            $count_stmt = $pdo->prepare($count_query);
            
            // Execute with parameters if any
            if (!empty($params)) {
                $count_stmt->execute($params);
            } else {
                $count_stmt->execute();
            }
            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            error_log("Total records: $total");
            
            // Get support requests with pagination
            $query = "
                SELECT sr.*, 
                       u.username as requester_name,
                       srq.title as request_title
                FROM support_requests sr
                JOIN users u ON sr.requester_id = u.id
                JOIN service_requests srq ON sr.service_request_id = srq.id
                $where_clause
                ORDER BY sr.created_at DESC
                LIMIT " . (int)$limit . " OFFSET " . (int)$offset;
            
            error_log("Executing query: " . $query);
            
            $stmt = $pdo->prepare($query);
            
            // Execute with parameters if any
            if (!empty($params)) {
                $stmt->execute($params);
            } else {
                $stmt->execute();
            }
            $support_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Filter sensitive information based on user role
            if ($user_role === 'user') {
                foreach ($support_requests as &$request) {
                    unset($request['admin_reason']);
                    unset($request['processed_by']);
                    unset($request['processed_at']);
                }
            }
            
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
    
    // Check if this is FormData (file upload) or JSON
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    if (strpos($content_type, 'multipart/form-data') !== false) {
        // Handle FormData (file upload)
        $service_request_id = isset($_POST['service_request_id']) ? (int)$_POST['service_request_id'] : 0;
        $support_type = isset($_POST['support_type']) ? $_POST['support_type'] : '';
        $support_details = isset($_POST['support_details']) ? $_POST['support_details'] : '';
        $support_reason = isset($_POST['support_reason']) ? $_POST['support_reason'] : '';
        
        error_log("FormData upload - service_request_id: $service_request_id, support_type: $support_type");
    } else {
        // Handle JSON
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
        
        error_log("JSON upload - service_request_id: $service_request_id, support_type: $support_type");
    }
    
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
        WHERE id = ? AND assigned_to = ?
    ");
    $stmt->execute([$service_request_id, $current_user]);
    $service_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$service_request) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Service request not found or not assigned to you']);
        return;
    }
    
    // Check if service request is already completed, resolved, or closed
    $completed_statuses = ['resolved', 'closed', 'cancelled'];
    if (in_array($service_request['status'], $completed_statuses)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Cannot submit support request for completed service request']);
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
        
        // Send notification to admin about new support request
        try {
            $notificationHelper = new ServiceRequestNotificationHelper();
            
            // Get request details for notification
            $requestDetails = $notificationHelper->getRequestDetails($service_request_id);
            
            // Notify admin about support request (escalation)
            $notificationHelper->notifyAdminSupportRequest(
                $service_request_id, 
                $support_details . ($support_reason ? " - Lý do: " . $support_reason : ""), 
                $_SESSION['full_name'] ?? 'Staff', 
                $requestDetails['title']
            );
            
        } catch (Exception $e) {
            error_log("Failed to send support request notification: " . $e->getMessage());
            // Continue even if notification fails
        }
        
        // Handle file uploads if any
        $uploaded_files = [];
        try {
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                error_log("Processing file uploads for support request $support_id");
                
                $uploads_dir = __DIR__ . '/../uploads/support_requests/';
                if (!file_exists($uploads_dir)) {
                    mkdir($uploads_dir, 0755, true);
                }
                
                $file_attachments = $_FILES['attachments'];
                $file_count = count($file_attachments['name']);
                
                for ($i = 0; $i < $file_count; $i++) {
                    if ($file_attachments['error'][$i] === UPLOAD_ERR_OK) {
                        $original_name = $file_attachments['name'][$i];
                        $file_size = $file_attachments['size'][$i];
                        $file_tmp = $file_attachments['tmp_name'][$i];
                        $file_type = $file_attachments['type'][$i];
                        
                        // Generate unique filename
                        $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                        $unique_filename = uniqid('support_', true) . '.' . $file_extension;
                        $file_path = $uploads_dir . $unique_filename;
                        
                        // Validate file (size, type)
                        $max_size = 10 * 1024 * 1024; // 10MB
                        $allowed_types = [
                            'image/jpeg', 'image/png', 'image/gif',
                            'application/pdf', 'application/msword',
                            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                            'application/vnd.ms-excel',
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                            'application/vnd.ms-powerpoint',
                            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
                            'text/plain', 'application/zip'
                        ];
                        
                        if ($file_size > $max_size) {
                            error_log("File too large: $original_name ($file_size bytes)");
                            continue;
                        }
                        
                        if (!in_array($file_type, $allowed_types)) {
                            error_log("File type not allowed: $original_name ($file_type)");
                            continue;
                        }
                        
                        // Move file
                        if (move_uploaded_file($file_tmp, $file_path)) {
                            // Save to database
                            $attachment_stmt = $pdo->prepare("
                                INSERT INTO support_request_attachments 
                                (support_request_id, original_name, filename, file_size, mime_type, uploaded_at)
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            
                            if ($attachment_stmt->execute([$support_id, $original_name, $unique_filename, $file_size, $file_type])) {
                                $uploaded_files[] = [
                                    'original_name' => $original_name,
                                    'filename' => $unique_filename,
                                    'file_size' => $file_size,
                                    'mime_type' => $file_type
                                ];
                                error_log("Successfully uploaded: $original_name");
                            } else {
                                error_log("Failed to save attachment to database: $original_name");
                                // Clean up uploaded file
                                unlink($file_path);
                            }
                        } else {
                            error_log("Failed to move uploaded file: $original_name");
                        }
                    } else {
                        error_log("Upload error for file $i: " . $file_attachments['error'][$i]);
                    }
                }
                
                error_log("Uploaded " . count($uploaded_files) . " files for support request $support_id");
            }
        } catch (Exception $e) {
            error_log("File upload error: " . $e->getMessage());
            // Continue without files - don't fail the whole request
        }
        
        // Keep service request status as 'in_progress' (staff continues working)
        $update_stmt = $pdo->prepare("
            UPDATE service_requests 
            SET status = 'in_progress' 
            WHERE id = ?
        ");
        $update_result = $update_stmt->execute([$service_request_id]);
        
        // Enable email sending with fixed template (non-blocking)
        try {
            // Get staff name and request details
            $staff_stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ?");
            $staff_stmt->execute([$current_user]);
            $staff_data = $staff_stmt->fetch(PDO::FETCH_ASSOC);
            
            $request_stmt = $pdo->prepare("SELECT title FROM service_requests WHERE id = ?");
            $request_stmt->execute([$service_request_id]);
            $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
            
            $staff_name = $staff_data['full_name'] ?? 'Staff';
            $request_title = $request_data['title'] ?? 'Unknown';
            
            $title = "Yêu cầu hỗ trợ mới cho yêu cầu #" . $service_request_id;
            $message = $staff_name . " yêu cầu hỗ trợ cho: " . $request_title;
            
            // Notify all admin users
            $admin_stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
            $admin_stmt->execute();
            $admins = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);
            
            if (!empty($admins)) {
                foreach ($admins as $admin_id) {
                    createNotification($pdo, $admin_id, $title, $message, 'warning', $service_request_id, 'request');
                    
                    // Send email notification to admin (non-blocking)
                    try {
                        $emailHelper = new PHPMailerEmailHelper();
                        
                        // Get admin email
                        $admin_stmt = $pdo->prepare("SELECT email, full_name FROM users WHERE id = ?");
                        $admin_stmt->execute([$admin_id]);
                        $admin_data = $admin_stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($admin_data) {
                            $subject = $title;
                            $email_body = "Yêu cầu hỗ trợ mới\n\n";
                            $email_body .= "Tiêu đề: " . $title . "\n";
                            $email_body .= "Nội dung: " . $message . "\n\n";
                            $email_body .= "Xem chi tiết: http://localhost/it-service-request/request-detail.html?id=" . $service_request_id . "\n\n";
                            $email_body .= "Trân trọng,\n";
                            $email_body .= "IT Service Request System";
                            
                            $emailHelper->sendEmail($admin_data['email'], $admin_data['full_name'], $subject, $email_body);
                        }
                    } catch (Exception $e) {
                        error_log("Failed to send support request email to admin {$admin_id}: " . $e->getMessage());
                        // Continue even if email fails
                    }
                }
            }
        } catch (Exception $e) {
            error_log("Failed to create support request notifications: " . $e->getMessage());
            // Continue even if notification creation fails - don't fail the whole request
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Support request created successfully',
            'data' => [
                'id' => $support_id,
                'attachments_uploaded' => count($uploaded_files)
            ]
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
    
    // Only admin can process support request decisions
    if ($user_role !== 'admin') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Chỉ admin mới có quyền xử lý yêu cầu hỗ trợ']);
        return;
    }
    
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
        // Send role-based notifications for support request decision
        try {
            $notificationHelper = new ServiceRequestNotificationHelper();
            
            // Get support request details
            $support_stmt = $pdo->prepare("
                SELECT sr.*, u.username as requester_name 
                FROM support_requests sr
                LEFT JOIN users u ON sr.requester_id = u.id
                WHERE sr.id = ?
            ");
            $support_stmt->execute([$support_id]);
            $support_data = $support_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($support_data) {
                $service_request_id = $support_data['service_request_id'];
                
                // Get service request details for proper title
                $requestDetails = $notificationHelper->getRequestDetails($service_request_id);
                $requestTitle = $requestDetails['title'];
                
                // Notify staff about admin decision
                if ($decision === 'approved') {
                    $notificationHelper->notifyStaffAdminApproved(
                        $service_request_id, 
                        $requestTitle, 
                        $_SESSION['full_name'] ?? 'Admin'
                    );
                } else {
                    $notificationHelper->notifyStaffAdminRejected(
                        $service_request_id, 
                        $requestTitle, 
                        $_SESSION['full_name'] ?? 'Admin', 
                        $reason
                    );
                }
                
                // Notify original requester
                if ($support_data['requester_id'] != $current_user) {
                    if ($decision === 'approved') {
                        // User gets notification that escalation was approved
                        $notificationHelper->notifyUserRequestPendingApproval(
                            $service_request_id, 
                            $support_data['requester_id']
                        );
                    } else {
                        // User gets notification that escalation was rejected
                        $notificationHelper->notifyUserRequestRejected(
                            $service_request_id, 
                            $support_data['requester_id'], 
                            "Yêu cầu hỗ trợ đã bị từ chối: " . $reason
                        );
                    }
                }
            }
            
        } catch (Exception $e) {
            error_log("Failed to send support request notifications: " . $e->getMessage());
            // Continue even if notification fails
        }
        
        // Update service request based on decision
        if ($decision === 'approved') {
            // Keep with staff but set to in_progress with approval info
            $update_stmt = $pdo->prepare("
                UPDATE service_requests 
                SET status = 'in_progress'
                WHERE id = ?
            ");
            $update_result = $update_stmt->execute([$service_request_id]);
            
            error_log("DEBUG: Updating service request $service_request_id to in_progress (approved support)");
            error_log("DEBUG: Update result: " . ($update_result ? 'SUCCESS' : 'FAILED'));
            
            echo json_encode([
                'success' => true,
                'message' => 'Support request approved, request continues with staff',
                'data' => [
                    'decision' => $decision,
                    'reason' => $reason,
                    'service_request_id' => $service_request_id,
                    'service_request_status' => 'in_progress'
                ]
            ]);
            
        } elseif ($decision === 'rejected') {
            // Set service request to in_progress with rejection info (similar to approved logic)
            $update_stmt = $pdo->prepare("
                UPDATE service_requests 
                SET status = 'in_progress'
                WHERE id = ?
            ");
            $update_result = $update_stmt->execute([$service_request_id]);
            
            error_log("DEBUG: Updating service request $service_request_id to in_progress (rejected support)");
            error_log("DEBUG: Update result: " . ($update_result ? 'SUCCESS' : 'FAILED'));
            
            echo json_encode([
                'success' => true,
                'message' => 'Support request rejected, request continues with staff',
                'data' => [
                    'decision' => $decision,
                    'service_request_id' => $service_request_id,
                    'service_request_status' => 'in_progress'
                ]
            ]);
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
