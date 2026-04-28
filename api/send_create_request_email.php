<?php
// API endpoint to send email when new request is created
// This endpoint is called after create_request to send emails asynchronously

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/EmailHelper.php';
require_once __DIR__ . '/../lib/ServiceRequestNotificationHelper.php';

// Get input
$input = json_decode(file_get_contents('php://input'), true);

$request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;

if ($request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Request ID is required']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        error_log("EMAIL: Failed to get database connection");
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Get request details
    $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, c.name as category_name
                      FROM service_requests sr
                      LEFT JOIN users u ON sr.user_id = u.id
                      LEFT JOIN categories c ON sr.category_id = c.id
                      WHERE sr.id = :request_id";
    
    $request_stmt = $db->prepare($request_query);
    $request_stmt->bindParam(":request_id", $request_id);
    $request_stmt->execute();
    $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request_data) {
        error_log("EMAIL: Request #$request_id not found");
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }
    
    $emailHelper = new EmailHelper();
    $notificationHelper = new ServiceRequestNotificationHelper($db);
    
    // Send confirmation email to user
    try {
        $email_data = array(
            'id' => $request_id,
            'title' => $request_data['title'],
            'requester_name' => $request_data['requester_name'],
            'category' => $request_data['category_name'],
            'priority' => $request_data['priority'],
            'description' => $request_data['description']
        );
        
        $email_result = $emailHelper->sendNewRequestNotification($email_data);
        error_log("EMAIL: Confirmation email for request #$request_id: " . ($email_result ? "SUCCESS" : "FAILED"));
    } catch (Exception $e) {
        error_log("EMAIL: Confirmation email failed for request #$request_id: " . $e->getMessage());
    }
    
    // Notify admin
    try {
        $notificationHelper->notifyAdminNewRequest(
            $request_id,
            $request_data['title'],
            $request_data['requester_name'],
            $request_data['category_name']
        );
    } catch (Exception $e) {
        error_log("NOTIFICATION: Admin notification failed for request #$request_id: " . $e->getMessage());
    }
    
    // Notify staff
    try {
        $notificationHelper->notifyStaffNewRequest(
            $request_id,
            $request_data['title'],
            $request_data['requester_name'],
            $request_data['category_name']
        );
    } catch (Exception $e) {
        error_log("NOTIFICATION: Staff notification failed for request #$request_id: " . $e->getMessage());
    }
    
    // Send email to staff
    try {
        $staff_stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE role = 'staff' AND status = 'active'");
        $staff_stmt->execute();
        $staff_users = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($staff_users as $staff) {
            $staff_subject = "Yêu cầu mới cần xử lý #{$request_id}";
            $staff_body = "
                <h2>Yêu cầu mới</h2>
                <p>Người dùng {$request_data['requester_name']} đã tạo yêu cầu mới #{$request_id} - {$request_data['title']}</p>
                <p>Danh mục: {$request_data['category_name']}</p>
                <p>Thời gian tạo: " . date('d/m/Y H:i') . "</p>
                <p>Vui lòng truy cập hệ thống để xử lý yêu cầu.</p>
            ";
            
            queueEmailAsync(
                $staff['email'],
                $staff['full_name'],
                $staff_subject,
                $staff_body,
                'high'
            );
        }
    } catch (Exception $e) {
        error_log("EMAIL: Staff email failed for request #$request_id: " . $e->getMessage());
    }
    
    // Send email to admin
    try {
        $admin_stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE role = 'admin' AND status = 'active'");
        $admin_stmt->execute();
        $admin_users = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($admin_users as $admin) {
            $admin_subject = "Yêu cầu mới cần xử lý #{$request_id}";
            $admin_body = "
                <h2>Yêu cầu mới</h2>
                <p>Người dùng {$request_data['requester_name']} đã tạo yêu cầu mới #{$request_id} - {$request_data['title']}</p>
                <p>Danh mục: {$request_data['category_name']}</p>
                <p>Thời gian tạo: " . date('d/m/Y H:i') . "</p>
                <p>Vui lòng truy cập hệ thống để xử lý yêu cầu.</p>
            ";
            
            queueEmailAsync(
                $admin['email'],
                $admin['full_name'],
                $admin_subject,
                $admin_body,
                'high'
            );
        }
    } catch (Exception $e) {
        error_log("EMAIL: Admin email failed for request #$request_id: " . $e->getMessage());
    }
    
    echo json_encode(['success' => true, 'message' => 'Emails sent']);
    
} catch (Exception $e) {
    error_log("EMAIL: Critical error for request #$request_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
