<?php
// API endpoint to send email when support request is created
// This endpoint is called after support request creation to send emails asynchronously

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../lib/EmailHelper.php';
require_once __DIR__ . '/../lib/ServiceRequestNotificationHelper.php';

// Get input
$input = json_decode(file_get_contents('php://input'), true);

$service_request_id = isset($input['service_request_id']) ? (int)$input['service_request_id'] : 0;
$support_details = isset($input['support_details']) ? $input['support_details'] : '';
$support_reason = isset($input['support_reason']) ? $input['support_reason'] : '';
$staff_name = isset($input['staff_name']) ? $input['staff_name'] : 'Staff';

if ($service_request_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Service Request ID is required']);
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
    
    $notificationHelper = new ServiceRequestNotificationHelper($db);
    $emailHelper = new EmailHelper();
    
    // Get request details for notification
    $requestDetails = $notificationHelper->getRequestDetails($service_request_id);
    
    // Notify admin about support request (escalation)
    try {
        $notificationHelper->notifyAdminSupportRequest(
            $service_request_id,
            $support_details . ($support_reason ? " - Lý do: " . $support_reason : ""),
            $staff_name,
            $requestDetails['title']
        );
    } catch (Exception $e) {
        error_log("NOTIFICATION: Admin notification failed for support request #$service_request_id: " . $e->getMessage());
    }
    
    // Send email to admin with standard template
    try {
        $adminUsers = $notificationHelper->getUsersByRole(['admin']);
        
        foreach ($adminUsers as $admin) {
            $emailContent = '<h2 style="color: #333; margin-bottom: 20px;">Yêu cầu hỗ trợ kỹ thuật</h2>

            <div style="background: #f8f9fa; border-left: 4px solid #ffc107; padding: 20px; margin: 20px 0;">
                <div style="margin-bottom: 12px;">
                    <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Mã yêu cầu:</span>
                    <span style="color: #212529;"><strong>#' . $service_request_id . '</strong></span>
                </div>
                <div style="margin-bottom: 12px;">
                    <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Tiêu đề yêu cầu:</span>
                    <span style="color: #212529;">' . htmlspecialchars($requestDetails['title']) . '</span>
                </div>
                <div style="margin-bottom: 12px;">
                    <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Nhân viên IT:</span>
                    <span style="color: #212529;">' . htmlspecialchars($staff_name) . '</span>
                </div>
                <div style="margin-bottom: 12px;">
                    <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Chi tiết hỗ trợ:</span>
                    <span style="color: #212529;">' . htmlspecialchars($support_details) . '</span>
                </div>
                ' . ($support_reason ? '<div style="margin-bottom: 12px;">
                    <span style="font-weight: bold; color: #495057; display: inline-block; width: 150px;">Lý do:</span>
                    <span style="color: #212529;">' . htmlspecialchars($support_reason) . '</span>
                </div>' : '') . '
            </div>

            <p style="color: #666; line-height: 1.6;">Nhân viên IT gặp vấn đề kỹ thuật khó và cần Admin can thiệp. Vui lòng truy cập hệ thống để xem và xử lý yêu cầu hỗ trợ này.</p>';

            $emailHelper->sendStandardEmail(
                $admin['email'],
                $admin['full_name'],
                "Yêu cầu hỗ trợ kỹ thuật #" . $service_request_id,
                $emailContent,
                $service_request_id
            );
        }
    } catch (Exception $e) {
        error_log("EMAIL: Failed to send support request email for #$service_request_id: " . $e->getMessage());
    }
    
    echo json_encode(['success' => true, 'message' => 'Emails sent']);
    
} catch (Exception $e) {
    error_log("EMAIL: Critical error for support request #$service_request_id: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
