<?php
// PHPMailer-based Email Helper for better SMTP support
require_once __DIR__ . '/../vendor/phpmailer/phpmailer.php';

class PHPMailerEmailHelper {
    private $mail;
    
    public function __construct() {
        // Using PHP mail() instead of SMTP for internal POP3 system
        $this->from_email = 'ndvu@sgitech.com.vn';
        $this->from_name = 'IT Service Request System';
    }
    
    public function sendEmail($to, $toName, $subject, $body) {
        try {
            // Proper UTF-8 encoding for Vietnamese characters
            $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            
            $headers = array(
                'MIME-Version: 1.0',
                'Content-Type: text/html; charset=UTF-8',
                'Content-Transfer-Encoding: base64',
                'From: ' . $this->from_name . ' <' . $this->from_email . '>',
                'Reply-To: ' . $this->from_email,
                'X-Mailer: PHP/' . phpversion()
            );
            
            $headers_string = implode("\r\n", $headers);
            $encoded_body = chunk_split(base64_encode($body));
            
            $result = mail($to, $subject, $encoded_body, $headers_string);
            
            $this->logEmail($to, $subject, $body, $result ? 'SENT_INTERNAL' : 'FAILED_INTERNAL');
            
            return $result;
            
        } catch (Exception $e) {
            $this->logEmail($to, $subject, $body, 'FAILED_INTERNAL: ' . $e->getMessage());
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
        
        $body = "<h2>📋 Yêu cầu dịch vụ mới</h2>
                <p><strong>Mã yêu cầu:</strong> #" . $request_data['id'] . "</p>
                <p><strong>Tiêu đề:</strong> " . htmlspecialchars($request_data['title']) . "</p>
                <p><strong>Người tạo:</strong> " . htmlspecialchars($request_data['requester_name']) . "</p>
                <p><strong>Danh mục:</strong> " . htmlspecialchars($request_data['category']) . "</p>
                <p><strong>Ưu tiên:</strong> " . htmlspecialchars($request_data['priority']) . "</p>
                <p><strong>Mô tả:</strong> " . nl2br(htmlspecialchars($request_data['description'])) . "</p>
                <hr>
                <p>Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý: </p>
                <a href='http://localhost/it-service-request/'>http://localhost/it-service-request/</a>
                <p><em>IT Service Request System</em></p>";
        
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
                 <p>Vui lòng đăng nhập hệ thống để xem chi tiết và theo dõi tiến độ: <a href='http://localhost/it-service-request/'>http://localhost/it-service-request/</a></p>
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
