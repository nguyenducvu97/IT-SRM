<?php
require_once 'vendor/phpmailer/phpmailer.php';

class GmailEmailHelper {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer();
        $this->mail->isSMTP();
        $this->mail->Host = 'smtp.gmail.com';
        $this->mail->SMTPAuth = true;
        $this->mail->Username = 'your-gmail@gmail.com'; // Cần cấu hình
        $this->mail->Password = 'your-app-password';   // Cần cấu hình
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port = 587;
        $this->mail->setFrom('your-gmail@gmail.com', 'IT Service Request System');
        $this->mail->CharSet = 'UTF-8';
    }
    
    public function sendEmail($to, $toName, $subject, $body) {
        try {
            $this->mail->clearAddresses();
            $this->mail->addAddress($to, $toName);
            $this->mail->Subject = $subject;
            $this->mail->Body = $body;
            $this->mail->isHTML(true);
            
            return $this->mail->send();
        } catch (Exception $e) {
            error_log("Gmail Email failed: " . $e->getMessage());
            return false;
        }
    }
    
    public function sendStatusUpdateNotification($request_data, $assigned_name = null) {
        $status_text = '';
        switch($request_data['status']) {
            case 'in_progress':
                $status_text = 'Đang xử lý';
                break;
            case 'resolved':
                $status_text = 'Đã giải quyết';
                break;
            case 'closed':
                $status_text = 'Đã đóng';
                break;
            case 'open':
                $status_text = 'Mở';
                break;
            default:
                $status_text = $request_data['status'];
        }
        
        $subject = "📝 Cập nhật trạng thái yêu cầu #" . $request_data["id"];
        
        $body = "<h2>📋 Cập nhật trạng thái yêu cầu</h2>
                <p><strong>Mã yêu cầu:</strong> #" . $request_data["id"] . "</p>
                <p><strong>Tiêu đề:</strong> " . htmlspecialchars($request_data["title"]) . "</p>
                <p><strong>Trạng thái mới:</strong> <span style='color: #007bff; font-weight: bold;'>{$status_text}</span></p>";
        
        if ($assigned_name && $request_data['status'] == 'in_progress') {
            $body .= "<p><strong>Người xử lý:</strong> " . htmlspecialchars($assigned_name) . "</p>";
            $body .= "<p style='color: #28a745;'><strong>✅ Yêu cầu của bạn đã được tiếp nhận và đang được xử lý!</strong></p>";
        }
        
        $body .= "<p><strong>Mô tả:</strong> " . nl2br(htmlspecialchars($request_data["description"])) . "</p>
                 <hr>
                 <p>Vui lòng đăng nhập hệ thống để xem chi tiết và theo dõi tiến độ.</p>
                 <p><em>IT Service Request System</em></p>";
        
        return $this->sendEmail(
            $request_data["requester_email"],
            $request_data["requester_name"],
            $subject,
            $body
        );
    }
}
?>
