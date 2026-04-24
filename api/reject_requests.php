<?php

// IT Service Request Reject Requests API

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: http://localhost');

header('Access-Control-Allow-Credentials: true');

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');



// Disable error display to prevent JSON corruption

error_reporting(0);

ini_set('display_errors', 0);

error_log("=== REJECT_REQUESTS.PHP DEBUG START ===");



// Handle preflight OPTIONS request

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

    http_response_code(200);

    exit();

}



try {

    require_once '../config/session.php';

    require_once '../config/database.php';

    require_once '../lib/EmailHelper.php';

    require_once '../lib/ServiceRequestNotificationHelper.php';

    error_log("✅ Config files loaded successfully");

} catch (Exception $e) {

    error_log("❌ Error loading config: " . $e->getMessage());

    header('Content-Type: application/json');

    http_response_code(500);

    echo json_encode(['success' => false, 'message' => 'Server configuration error']);

    exit();

}



// Start session

try {

    startSession();

    error_log("✅ Session started successfully");

} catch (Exception $e) {

    error_log("❌ Error starting session: " . $e->getMessage());

    header('Content-Type: application/json');

    http_response_code(500);

    echo json_encode(['success' => false, 'message' => 'Session error']);

    exit();

}



// Check if user is authenticated

if (!isset($_SESSION['user_id'])) {

    error_log("❌ User not authenticated - session data: " . json_encode($_SESSION));

    http_response_code(401);

    echo json_encode(['success' => false, 'message' => 'Unauthorized']);

    exit;

}



// Get user information

$user_id = $_SESSION['user_id'];

$user_role = $_SESSION['role'] ?? 'user';

error_log("✅ User authenticated - ID: $user_id, Role: $user_role");



// Get database connection

try {

    $db = getDatabaseConnection();

    error_log("✅ Database connection established");

} catch (Exception $e) {

    error_log("❌ Database connection error: " . $e->getMessage());

    header('Content-Type: application/json');

    http_response_code(500);

    echo json_encode(['success' => false, 'message' => 'Database connection error']);

    exit();

}



// Helper function for JSON responses

function rejectJsonResponse($success, $message, $data = null) {

    $response = ['success' => $success, 'message' => $message];

    if ($data !== null) {

        $response['data'] = $data;

    }

    echo json_encode($response);

    exit;

}



// Get HTTP method

$method = $_SERVER['REQUEST_METHOD'];

error_log("📡 Request method: $method");



if ($method == 'GET') {

    $action = isset($_GET['action']) ? $_GET['action'] : '';

    error_log("🎯 Action: $action");

    

    if ($action == 'list') {

        error_log("📋 Processing reject requests list");

        

        // Only admin and staff can view reject requests

        if (!in_array($user_role, ['admin', 'staff'])) {

            error_log("❌ Access denied for role: $user_role");

            rejectJsonResponse(false, 'Chỉ admin và staff mới có quyền xem yêu cầu từ chối');

        }

        

        error_log("✅ Access granted for role: $user_role");

        

        $status_filter = isset($_GET['status']) ? $_GET['status'] : '';

        $page = max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1);

        $limit = max(1, isset($_GET['limit']) ? (int)$_GET['limit'] : 9);

        $offset = ($page - 1) * $limit;

        

        error_log("🔍 Filters - Status: '$status_filter', Page: $page, Limit: $limit");

        

        $where_clause = "WHERE 1=1";

        $params = [];

        

        if (!empty($status_filter)) {

            $where_clause .= " AND rr.status = :status";

            $params[':status'] = $status_filter;

        }

        

        error_log("📊 Query clause: $where_clause");

        

        // Get total count

        $count_query = "SELECT COUNT(*) as total FROM reject_requests rr $where_clause";

        error_log("🔢 Count query: $count_query");

        

        try {

            $count_stmt = $db->prepare($count_query);

            foreach ($params as $key => $value) {

                $count_stmt->bindValue($key, $value);

            }

            $count_stmt->execute();

            $total = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];

            error_log("📊 Total count: $total");

            

            // Get reject requests with details

            $query = "SELECT rr.*, 

                      sr.title as service_request_title, sr.id as service_request_id,

                      requester.username as requester_name,

                      rejecter.username as rejecter_name,

                      processor.username as processor_name,

                      GROUP_CONCAT(
    CASE 
        WHEN attachment.original_name IS NOT NULL AND attachment.filename IS NOT NULL 
        THEN CONCAT(attachment.original_name, '|', attachment.filename, '|', COALESCE(attachment.file_size, 0), '|', COALESCE(attachment.mime_type, 'application/octet-stream'))
        ELSE NULL 
    END
    ORDER BY attachment.id 
    SEPARATOR '||'
) as attachments

                      FROM reject_requests rr 

                      LEFT JOIN service_requests sr ON rr.service_request_id = sr.id

                      LEFT JOIN users requester ON sr.user_id = requester.id

                      LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id

                      LEFT JOIN users processor ON rr.processed_by = processor.id

                      LEFT JOIN reject_request_attachments attachment ON rr.id = attachment.reject_request_id

                      $where_clause 

                      GROUP BY rr.id

                      ORDER BY rr.created_at DESC 

                      LIMIT :limit OFFSET :offset";

            

            error_log("📋 Main query: $query");

            

            $stmt = $db->prepare($query);

            

            // Bind parameters

            foreach ($params as $key => $value) {

                $stmt->bindValue($key, $value);

            }

            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);

            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);

            

            $stmt->execute();

            $reject_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

            

            error_log("📝 Found " . count($reject_requests) . " reject requests");

            

            // Process attachments

            foreach ($reject_requests as &$request) {

                $attachments = [];

                if (!empty($request['attachments'])) {

                    $attachment_strings = explode('||', $request['attachments']);

                    $seen_original_names = [];

                    foreach ($attachment_strings as $attachment_string) {

                        if (!empty($attachment_string) && trim($attachment_string) !== '') {

                            $parts = explode('|', $attachment_string);

                            // Filter out empty parts but preserve order
                            $filtered_parts = [];
                            foreach ($parts as $part) {
                                if ($part !== '' && $part !== null) {
                                    $filtered_parts[] = $part;
                                }
                            }

                            if (count($filtered_parts) >= 4 && !empty($filtered_parts[0]) && !empty($filtered_parts[1])) {

                                $original_name = trim($filtered_parts[0]);

                                // Skip if we've already seen this original name
                                if (!in_array($original_name, $seen_original_names)) {

                                    $attachments[] = [

                                        'original_name' => $original_name,

                                        'filename' => trim($filtered_parts[1]),

                                        'file_size' => intval($filtered_parts[2]),

                                        'mime_type' => trim($filtered_parts[3])

                                    ];

                                    $seen_original_names[] = $original_name;

                                }

                            }

                        }

                    }

                }

                $request['attachments'] = $attachments;

                // Remove the raw attachment string field (not the processed one)

                unset($request['attachments_raw']);

            }

            

            rejectJsonResponse(true, 'Lấy danh sách yêu cầu từ chối thành công', [

                'reject_requests' => $reject_requests,

                'pagination' => [

                    'page' => $page,

                    'limit' => $limit,

                    'total' => $total,

                    'pages' => ceil($total / $limit)

                ]

            ]);

            

        } catch (Exception $e) {

            error_log("❌ Database error: " . $e->getMessage());

            rejectJsonResponse(false, 'Lỗi truy vấn cơ sở dữ liệu: ' . $e->getMessage());

        }

        

    } elseif ($action == 'get') {

        // Only admin and staff can view reject request details

        if (!in_array($user_role, ['admin', 'staff'])) {

            rejectJsonResponse(false, 'Chỉ admin và staff mới có quyền xem chi tiết yêu cầu từ chối');

        }

        

        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {

            rejectJsonResponse(false, 'ID yêu cầu từ chối không hợp lệ');

        }

        

        $query = "SELECT rr.*, 

                  sr.title as service_request_title, sr.id as service_request_id,

                  requester.username as requester_name,

                  rejecter.username as rejecter_name,

                  processor.username as processor_name

                  FROM reject_requests rr 

                  LEFT JOIN service_requests sr ON rr.service_request_id = sr.id

                  LEFT JOIN users requester ON sr.user_id = requester.id

                  LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id

                  LEFT JOIN users processor ON rr.processed_by = processor.id

                  WHERE rr.id = :id";

        

        $stmt = $db->prepare($query);

        $stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $stmt->execute();

        

        $reject_request = $stmt->fetch(PDO::FETCH_ASSOC);

        

        if (!$reject_request) {

            rejectJsonResponse(false, 'Không tìm thấy yêu cầu từ chối');

        }

        

        // Get attachments

        $attachment_query = "SELECT original_name, filename, file_size, mime_type 

                            FROM reject_request_attachments 

                            WHERE reject_request_id = :id

                            ORDER BY id";

        $attachment_stmt = $db->prepare($attachment_query);

        $attachment_stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $attachment_stmt->execute();

        $all_attachments = $attachment_stmt->fetchAll(PDO::FETCH_ASSOC);

        

        // Filter duplicates by original name

        $attachments = [];

        $seen_original_names = [];

        foreach ($all_attachments as $attachment) {

            $original_name = $attachment['original_name'];

            if (!in_array($original_name, $seen_original_names)) {

                $attachments[] = $attachment;

                $seen_original_names[] = $original_name;

            }

        }

        

        $reject_request['attachments'] = $attachments;

        

        rejectJsonResponse(true, 'Lấy chi tiết yêu cầu từ chối thành công', $reject_request);

        

    } else {

        rejectJsonResponse(false, 'Action không hợp lệ');

    }

    

} elseif ($method == 'PUT') {

    // Get JSON input

    $input = json_decode(file_get_contents('php://input'), true);

    

    if (!$input) {

        rejectJsonResponse(false, 'Dữ liệu JSON không hợp lệ');

    }

    

    $action = isset($input['action']) ? $input['action'] : '';

    

    if ($action == 'update') {

        // Only admin can update reject requests

        if ($user_role !== 'admin') {

            rejectJsonResponse(false, 'Chỉ admin mới có quyền cập nhật yêu cầu từ chối');

        }

        

        $id = isset($input['reject_id']) ? (int)$input['reject_id'] : 0;

        $decision = isset($input['decision']) ? trim($input['decision']) : '';

        $admin_reason = isset($input['admin_reason']) ? trim($input['admin_reason']) : '';

        

        if ($id <= 0) {

            rejectJsonResponse(false, 'ID yêu cầu từ chối không hợp lệ');

        }

        

        if (!in_array($decision, ['approved', 'rejected'])) {

            rejectJsonResponse(false, 'Quyết định không hợp lệ (phải là approved hoặc rejected)');

        }

        

        if (empty($admin_reason)) {

            rejectJsonResponse(false, 'Lý do xử lý của admin không được để trống');

        }

        

        // Check if reject request exists

        $check_query = "SELECT id, status, service_request_id FROM reject_requests WHERE id = :id";

        $check_stmt = $db->prepare($check_query);

        $check_stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $check_stmt->execute();

        

        if ($check_stmt->rowCount() === 0) {

            rejectJsonResponse(false, 'Không tìm thấy yêu cầu từ chối');

        }

        

        $reject_request = $check_stmt->fetch(PDO::FETCH_ASSOC);

        

        // Update reject request with admin decision

        $update_query = "UPDATE reject_requests 

                        SET status = :status,

                            admin_reason = :admin_reason,

                            processed_by = :processed_by,

                            processed_at = NOW(),

                            updated_at = NOW()

                        WHERE id = :id";

        

        $update_stmt = $db->prepare($update_query);

        $update_stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $update_stmt->bindValue(':status', $decision);

        $update_stmt->bindValue(':admin_reason', $admin_reason);

        $update_stmt->bindValue(':processed_by', $user_id, PDO::PARAM_INT);

        

        if ($update_stmt->execute()) {

            // Send role-based notifications

            try {

                $notificationHelper = new ServiceRequestNotificationHelper($db);

                

                // Get request details for notifications

                $requestDetails = $notificationHelper->getRequestDetails($reject_request['service_request_id']);

                

                if ($decision === 'approved') {

                    // Update service request to reflect that rejection was approved

                    $service_update_query = "UPDATE service_requests

                                           SET status = 'rejected',

                                               updated_at = NOW()

                                           WHERE id = :service_request_id";

                    $service_update_stmt = $db->prepare($service_update_query);

                    $service_update_stmt->bindValue(':service_request_id', $reject_request['service_request_id'], PDO::PARAM_INT);

                    $service_update_stmt->execute();



                    // Notify user that their request was rejected

                    $notificationHelper->notifyUserRequestRejected(

                        $reject_request['service_request_id'],

                        $requestDetails['user_id'],

                        $admin_reason . " (Yêu cầu từ chối đã được Admin duyệt)"

                    );

                    // Send email to user with standard template
                    $emailHelper = new EmailHelper();
                    $user_stmt = $db->prepare("SELECT email, full_name FROM users WHERE id = ?");
                    $user_stmt->execute([$requestDetails['user_id']]);
                    $userData = $user_stmt->fetch(PDO::FETCH_ASSOC);

                    if ($userData) {
                        $emailContent = '<h2 style="color: #333; margin-bottom: 20px;">Yêu cầu đã bị từ chối</h2>

                        <div style="background: #f8f9fa; border-left: 4px solid #dc3545; padding: 20px; margin: 20px 0;">
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Mã yêu cầu:</span>
                                <span style="color: #212529;"><strong>#' . $reject_request['service_request_id'] . '</strong></span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Tiêu đề yêu cầu:</span>
                                <span style="color: #212529;">' . htmlspecialchars($requestDetails['title']) . '</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Lý do từ chối:</span>
                                <span style="color: #212529;">' . htmlspecialchars($admin_reason) . '</span>
                            </div>
                        </div>

                        <p style="color: #666; line-height: 1.6;">Yêu cầu từ chối đã được Admin duyệt. Yêu cầu gốc đã được chuyển sang trạng thái đã từ chối.</p>';

                        $emailHelper->sendStandardEmail(
                            $userData['email'],
                            $userData['full_name'],
                            "Yêu cầu đã bị từ chối #" . $reject_request['service_request_id'],
                            $emailContent,
                            $reject_request['service_request_id']
                        );
                    }



                    // Notify staff about admin approval

                    $notificationHelper->notifyStaffAdminRejected(

                        $reject_request['service_request_id'],

                        $requestDetails['title'],

                        $_SESSION['full_name'] ?? 'Admin',

                        $admin_reason

                    );



                    rejectJsonResponse(true, 'Yêu cầu từ chối đã được duyệt. Yêu cầu gốc đã được chuyển sang trạng thái đã từ chối.');

                } else {

                    // Rejection was denied, service request continues normally

                    // Notify staff that rejection was denied

                    $notificationHelper->notifyStaffAdminRejected(

                        $reject_request['service_request_id'],

                        $requestDetails['title'],

                        $_SESSION['full_name'] ?? 'Admin',

                        $admin_reason

                    );

                    // Send email to staff with standard template
                    $emailHelper = new EmailHelper();
                    $staffUsers = $notificationHelper->getUsersByRole(['staff']);

                    foreach ($staffUsers as $staff) {
                        $emailContent = '<h2 style="color: #333; margin-bottom: 20px;">Yêu cầu từ chối đã bị từ chối</h2>

                        <div style="background: #f8f9fa; border-left: 4px solid #28a745; padding: 20px; margin: 20px 0;">
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Mã yêu cầu:</span>
                                <span style="color: #212529;"><strong>#' . $reject_request['service_request_id'] . '</strong></span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Tiêu đề yêu cầu:</span>
                                <span style="color: #212529;">' . htmlspecialchars($requestDetails['title']) . '</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Lý do:</span>
                                <span style="color: #212529;">' . htmlspecialchars($admin_reason) . '</span>
                            </div>
                        </div>

                        <p style="color: #666; line-height: 1.6;">Yêu cầu từ chối đã bị từ chối. Yêu cầu gốc sẽ tiếp tục xử lý bình thường.</p>';

                        $emailHelper->sendStandardEmail(
                            $staff['email'],
                            $staff['full_name'],
                            "Yêu cầu từ chối đã bị từ chối #" . $reject_request['service_request_id'],
                            $emailContent,
                            $reject_request['service_request_id']
                        );
                    }



                    rejectJsonResponse(true, 'Yêu cầu từ chối đã bị từ chối. Yêu cầu gốc sẽ tiếp tục xử lý bình thường.');

                }

                

            } catch (Exception $e) {

                error_log("Failed to send reject request notifications: " . $e->getMessage());

                // Continue even if notification fails

            }

        } else {

            rejectJsonResponse(false, 'Cập nhật yêu cầu từ chối thất bại');

        }

        

    } else {

        rejectJsonResponse(false, 'Action không hợp lệ');

    }

    

} elseif ($method == 'DELETE') {

    // Only admin can delete reject requests

    if ($user_role !== 'admin') {

        rejectJsonResponse(false, 'Chỉ admin mới có quyền xóa yêu cầu từ chối');

    }

    

    $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

    

    if ($id <= 0) {

        rejectJsonResponse(false, 'ID yêu cầu từ chối không hợp lệ');

    }

    

    try {

        $db->beginTransaction();

        

        // Delete attachments first

        $delete_attachments_query = "DELETE FROM reject_request_attachments WHERE reject_request_id = :id";

        $delete_attachments_stmt = $db->prepare($delete_attachments_query);

        $delete_attachments_stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $delete_attachments_stmt->execute();

        

        // Delete reject request

        $delete_query = "DELETE FROM reject_requests WHERE id = :id";

        $delete_stmt = $db->prepare($delete_query);

        $delete_stmt->bindValue(':id', $id, PDO::PARAM_INT);

        $delete_stmt->execute();

        

        if ($delete_stmt->rowCount() === 0) {

            $db->rollBack();

            rejectJsonResponse(false, 'Không tìm thấy yêu cầu từ chối');

        }

        

        $db->commit();

        rejectJsonResponse(true, 'Xóa yêu cầu từ chối thành công');

        

    } catch (Exception $e) {

        $db->rollBack();

        error_log("Error deleting reject request: " . $e->getMessage());

        rejectJsonResponse(false, 'Lỗi khi xóa yêu cầu từ chối');

    }

    

} else {

    rejectJsonResponse(false, 'Method không được hỗ trợ');

}

?>

