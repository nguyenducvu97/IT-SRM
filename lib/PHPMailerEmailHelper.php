<?php
// PHPMailer-based Email Helper for better SMTP support
require_once __DIR__ . '/../vendor/phpmailer/phpmailer.php';

class PHPMailerEmailHelper {
    private $mail;
    private $from_email;
    private $from_name;
    
    public function __construct() {
        // Using PHPMailer instead of PHP mail() for better email handling
        $this->mail = new PHPMailer();
        $this->from_email = 'ndvu@sgitech.com.vn';
        $this->from_name = 'IT Service Request System';
        
        // Configure PHPMailer
        $this->mail->CharSet = 'UTF-8';
        $this->mail->Encoding = 'base64';
        $this->mail->setFrom($this->from_email, $this->from_name);
        $this->mail->isHTML(true);
    }
    
    public function sendEmail($to, $toName, $subject, $body) {
        try {
            // Clear previous recipients
            $this->mail->clearAddresses();
            
            // Add recipient
            $this->mail->addAddress($to, $toName);
            
            // Set subject
            $this->mail->Subject = $subject;
            
            // Set body
            $this->mail->Body = $body;
            
            // Send using default mail method (mailSend)
            $result = $this->mail->send();
            
            $this->logEmail($to, $subject, $body, $result ? 'SENT_PHPMAILER' : 'FAILED_PHPMAILER');
            
            return $result;
            
        } catch (Exception $e) {
            $this->logEmail($to, $subject, $body, 'FAILED_PHPMAILER: ' . $e->getMessage());
            return false;
        }
    }
    
    public function sendNewRequestNotification($request_data) {
        require_once __DIR__ . '/../config/database.php';
        
        // Get all admin and staff emails
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT email, full_name FROM users WHERE role IN ('admin', 'staff')");
        $stmt->execute();
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($recipients)) {
            $this->logEmail('', 'No recipients found', '', 'FAILED: No admin/staff users found');
            return false;
        }
        
        $subject = "🔔 Yêu cầu dịch vụ mới #" . $request_data['id'];
        
        $body = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>IT Service Request</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
        .header p { margin: 5px 0 0 0; opacity: 0.9; font-size: 14px; }
        .content { padding: 30px 20px; }
        .request-info { background: #f8f9fa; border-radius: 6px; padding: 20px; margin: 20px 0; border-left: 4px solid #667eea; }
        .info-row { display: flex; margin-bottom: 12px; align-items: flex-start; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { font-weight: 600; color: #495057; min-width: 120px; margin-right: 15px; }
        .info-value { color: #212529; flex: 1; line-height: 1.5; }
        .priority { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; }
        .priority.high { background: #ffebee; color: #c62828; }
        .priority.medium { background: #fff3e0; color: #ef6c00; }
        .priority.low { background: #e8f5e8; color: #2e7d32; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; margin: 20px 0; transition: transform 0.2s; }
        .cta-button:hover { transform: translateY(-2px); }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6; }
        .footer p { margin: 5px 0; color: #6c757d; font-size: 13px; }
        .description { background: white; border: 1px solid #e9ecef; border-radius: 6px; padding: 15px; margin: 15px 0; line-height: 1.6; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>� IT Service Request</h1>
            <p>Hệ thống yêu cầu dịch vụ CNTT</p>
        </div>
        
        <div class='content'>
            <h2 style='color: #333; margin-bottom: 20px;'>Yêu cầu dịch vụ mới</h2>
            
            <div class='request-info'>
                <div class='info-row'>
                    <div class='info-label'>Mã yêu cầu:</div>
                    <div class='info-value'><strong>#" . $request_data['id'] . "</strong></div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Tiêu đề:</div>
                    <div class='info-value'>" . htmlspecialchars($request_data['title']) . "</div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Người tạo:</div>
                    <div class='info-value'>" . htmlspecialchars($request_data['requester_name']) . "</div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Danh mục:</div>
                    <div class='info-value'>" . htmlspecialchars($request_data['category']) . "</div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Ưu tiên:</div>
                    <div class='info-value'><span class='priority " . $request_data['priority'] . "'>" . htmlspecialchars($request_data['priority']) . "</span></div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Mô tả:</div>
                    <div class='info-value'>
                        <div class='description'>" . nl2br(htmlspecialchars($request_data['description'])) . "</div>
                    </div>
                </div>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='http://localhost/it-service-request/request-detail.html?id=" . $request_data["id"] . "' class='cta-button'>
                    Xem chi tiết yêu cầu →
                </a>
            </div>
        </div>
        
        <div class='footer'>
            <p><strong>IT Service Request System</strong></p>
            <p>Đây là email tự động. Vui lòng không trả lời email này.</p>
            <p>Nếu cần hỗ trợ, vui lòng liên hệ IT Department.</p>
        </div>
    </div>
</body>
</html>";
        
        $success_count = 0;
        $total_count = count($recipients);
        
        foreach ($recipients as $recipient) {
            if ($this->sendEmail($recipient['email'], $recipient['full_name'], $subject, $body)) {
                $success_count++;
            }
        }
        
        $this->logEmail('multiple', $subject, $body, "SENT to {$success_count}/{$total_count} admin/staff recipients");
        return $success_count > 0;
    }
    
    public function sendStatusUpdateNotification($request_data, $assigned_name = null) {
        $status_text = '';
        $status_color = '';
        switch($request_data['status']) {
            case 'in_progress':
                $status_text = 'Đang xử lý';
                $status_color = '#ffc107';
                break;
            case 'resolved':
                $status_text = 'Đã giải quyết';
                $status_color = '#28a745';
                break;
            case 'closed':
                $status_text = 'Đã đóng';
                $status_color = '#6c757d';
                break;
            case 'open':
                $status_text = 'Mở';
                $status_color = '#007bff';
                break;
            default:
                $status_text = $request_data['status'];
                $status_color = '#6c757d';
        }
        
        $subject = "📝 Cập nhật trạng thái yêu cầu #" . $request_data["id"];
        
        $body = "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>IT Service Request</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f5f5f5; }
        .container { max-width: 600px; margin: 20px auto; background-color: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center; }
        .header h1 { margin: 0; font-size: 28px; font-weight: 600; }
        .header p { margin: 5px 0 0 0; opacity: 0.9; font-size: 14px; }
        .content { padding: 30px 20px; }
        .status-info { background: #f8f9fa; border-radius: 6px; padding: 20px; margin: 20px 0; border-left: 4px solid {$status_color}; }
        .info-row { display: flex; margin-bottom: 12px; align-items: flex-start; }
        .info-row:last-child { margin-bottom: 0; }
        .info-label { font-weight: 600; color: #495057; min-width: 120px; margin-right: 15px; }
        .info-value { color: #212529; flex: 1; line-height: 1.5; }
        .status-badge { padding: 4px 12px; border-radius: 20px; font-size: 12px; font-weight: 600; text-transform: uppercase; background: {$status_color}; color: white; }
        .cta-button { display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 30px; text-decoration: none; border-radius: 25px; font-weight: 600; margin: 20px 0; transition: transform 0.2s; }
        .cta-button:hover { transform: translateY(-2px); }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #dee2e6; }
        .footer p { margin: 5px 0; color: #6c757d; font-size: 13px; }
        .description { background: white; border: 1px solid #e9ecef; border-radius: 6px; padding: 15px; margin: 15px 0; line-height: 1.6; }
    </style>
</head>
<body>
    <div class='container'>
        <div class='header'>
            <h1>� IT Service Request</h1>
            <p>Cập nhật trạng thái yêu cầu</p>
        </div>
        
        <div class='content'>
            <h2 style='color: #333; margin-bottom: 20px;'>Trạng thái yêu cầu đã cập nhật</h2>
            
            <div class='status-info'>
                <div class='info-row'>
                    <div class='info-label'>Mã yêu cầu:</div>
                    <div class='info-value'><strong>#" . $request_data["id"] . "</strong></div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Tiêu đề:</div>
                    <div class='info-value'>" . htmlspecialchars($request_data["title"]) . "</div>
                </div>
                <div class='info-row'>
                    <div class='info-label'>Trạng thái mới:</div>
                    <div class='info-value'><span class='status-badge'>{$status_text}</span></div>
                </div>";
        
        if ($assigned_name && $request_data['status'] == 'in_progress') {
            $body .= "<div class='info-row'>
                    <div class='info-label'>Người xử lý:</div>
                    <div class='info-value'>" . htmlspecialchars($assigned_name) . "</div>
                </div>
                <div style='background: #d4edda; padding: 15px; border-radius: 6px; margin: 15px 0; border-left: 4px solid #28a745;'>
                    <p style='margin: 0; color: #155724; font-weight: 600;'>✅ Yêu cầu của bạn đã được tiếp nhận và đang được xử lý!</p>
                </div>";
        }
        
        $body .= "<div class='info-row'>
                    <div class='info-label'>Mô tả:</div>
                    <div class='info-value'>
                        <div class='description'>" . nl2br(htmlspecialchars($request_data["description"])) . "</div>
                    </div>
                </div>
            </div>
            
            <div style='text-align: center; margin: 30px 0;'>
                <a href='http://localhost/it-service-request/request-detail.html?id=" . $request_data["id"] . "' class='cta-button'>
                    Xem chi tiết yêu cầu →
                </a>
            </div>
        </div>
        
        <div class='footer'>
            <p><strong>IT Service Request System</strong></p>
            <p>Đây là email tự động. Vui lòng không trả lời email này.</p>
            <p>Nếu cần hỗ trợ, vui lòng liên hệ IT Department.</p>
        </div>
    </div>
</body>
</html>";
        
        return $this->sendEmail(
            $request_data["requester_email"],
            $request_data["requester_name"],
            $subject,
            $body
        );
    }
    
    public function sendResolutionNotification($request_data, $error_description, $solution_method) {
        $subject = "✅ Yêu cầu đã được giải quyết #" . $request_data["id"];
        
        $body = "<h2>🎉 Yêu cầu đã được giải quyết</h2>
                <p><strong>Mã yêu cầu:</strong> #" . $request_data["id"] . "</p>
                <p><strong>Tiêu đề:</strong> " . htmlspecialchars($request_data["title"]) . "</p>
                <p><strong>Người giải quyết:</strong> " . htmlspecialchars($request_data["staff_name"]) . "</p>
                <p><strong>Danh mục:</strong> " . htmlspecialchars($request_data["category_name"]) . "</p>
                
                <h3>📝 Mô tả lỗi:</h3>
                <p style='background-color: #f8f9fa; padding: 10px; border-left: 4px solid #dc3545;'>
                " . nl2br(htmlspecialchars($error_description)) . "
                </p>
                
                <h3>🔧 Cách khắc phục:</h3>
                <p style='background-color: #f8f9fa; padding: 10px; border-left: 4px solid #28a745;'>
                " . nl2br(htmlspecialchars($solution_method)) . "
                </p>
                
                <hr>
                <p style='color: #28a745; font-weight: bold;'>✅ Yêu cầu của bạn đã được giải quyết thành công!</p>
                <p>Vui lòng đăng nhập hệ thống để xem chi tiết: <a href='http://localhost/it-service-request/'>http://localhost/it-service-request/</a></p>
                <p><em>IT Service Request System</em></p>";
        
        return $this->sendEmail(
            $request_data["requester_email"],
            $request_data["requester_name"],
            $subject,
            $body
        );
    }
    
    private function logEmail($to, $subject, $body, $status) {
        $log_entry = sprintf(
            "[%s] %s | To: %s | Subject: %s\n",
            date('Y-m-d H:i:s'),
            $status,
            $to,
            $subject
        );
        
        $log_file = __DIR__ . '/../logs/email_activity.log';
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}
?>
