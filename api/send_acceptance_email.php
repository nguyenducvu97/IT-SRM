<?php
// API endpoint to send acceptance email in background
// This endpoint is called after accept_request to send emails asynchronously

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/EmailHelper.php';

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
    
    // Get request details for email
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
    
    if (!$request_data) {
        error_log("EMAIL: Request #$request_id not found");
        echo json_encode(['success' => false, 'message' => 'Request not found']);
        exit;
    }
    
    $emailHelper = new EmailHelper();
    
    // Send email to requester
    try {
        error_log("EMAIL: Sending email to requester for request #$request_id");
        $subject = "Yêu cầu #{$request_id} - Trạng thái thay thành 'in_progress'";
        $customContent = '<h2 style="color: #333; margin-bottom: 20px;">Yêu cầu được nhân viên IT nhận</h2>
        <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">
            <div style="margin-bottom: 12px;"><span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Mã yêu cầu:</span><span style="color: #212529;"><strong>#' . $request_id . '</strong></span></div>
            <div style="margin-bottom: 12px;"><span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Tiêu đề:</span><span style="color: #212529;">' . htmlspecialchars($request_data['title']) . '</span></div>
            <div style="margin-bottom: 12px;"><span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Người tạo:</span><span style="color: #212529;">' . htmlspecialchars($request_data['requester_name']) . '</span></div>
            <div style="margin-bottom: 12px;"><span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Nhân viên IT:</span><span style="color: #212529;">' . htmlspecialchars($request_data['assigned_name']) . '</span></div>
            <div style="margin-bottom: 12px;"><span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Trạng thái:</span><span style="color: #212529;"><span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #e8f5e8; color: #28a745;">in_progress</span></span></div>
        </div>
        <p style="color: #666; line-height: 1.6;">Yêu cầu của bạn được nhân viên IT nhận và đang trong quá trình xử lý.</p>';
        
        $emailResult = $emailHelper->sendStandardEmail(
            $request_data['requester_email'],
            $request_data['requester_name'],
            $subject,
            $customContent,
            $request_id
        );
        error_log("EMAIL: Email to requester result for request #$request_id: " . ($emailResult ? "SUCCESS" : "FAILED"));
    } catch (Exception $e) {
        error_log("EMAIL: Email to requester failed for request #$request_id: " . $e->getMessage());
    }
    
    // Send email to all admins
    try {
        $admin_query = "SELECT email, full_name FROM users WHERE role = 'admin' AND status = 'active'";
        $admin_stmt = $db->prepare($admin_query);
        $admin_stmt->execute();
        $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($admins)) {
            $admin_subject = "Staff Accepted Request #{$request_id}";
            $adminCustomContent = '<h2 style="color: #333; margin-bottom: 20px;">Nhân viên IT nhận yêu cầu</h2>
            <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">
                <div style="margin-bottom: 12px;"><span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Mã yêu cầu:</span><span style="color: #212529;"><strong>#' . $request_id . '</strong></span></div>
                <div style="margin-bottom: 12px;"><span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Tiêu đề:</span><span style="color: #212529;">' . htmlspecialchars($request_data['title']) . '</span></div>
                <div style="margin-bottom: 12px;"><span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Người tạo:</span><span style="color: #212529;">' . htmlspecialchars($request_data['requester_name']) . '</span></div>
                <div style="margin-bottom: 12px;"><span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Nhân viên IT:</span><span style="color: #212529;">' . htmlspecialchars($request_data['assigned_name']) . '</span></div>
                <div style="margin-bottom: 12px;"><span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Trạng thái:</span><span style="color: #212529;"><span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #e8f5e8; color: #28a745;">in_progress</span></span></div>
            </div>
            <p style="color: #666; line-height: 1.6;">Nhân viên IT được phân công và bắt đầu xử lý yêu cầu này.</p>';
            
            foreach ($admins as $admin) {
                $admin_email_result = $emailHelper->sendStandardEmail(
                    $admin['email'],
                    $admin['full_name'],
                    $admin_subject,
                    $adminCustomContent,
                    $request_id
                );
                error_log("EMAIL: Email to admin {$admin['email']} result: " . ($admin_email_result ? "SUCCESS" : "FAILED"));
            }
        }
    } catch (Exception $e) {
        error_log("EMAIL: Failed to send admin emails for request #$request_id: " . $e->getMessage());
    }
    
    echo json_encode(['success' => true, 'message' => 'Emails sent']);
    
} catch (Exception $e) {
    error_log("EMAIL: Critical error for request #$request_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
