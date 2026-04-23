<?php
// POP3 Email Helper for Company Email System
// Hỗ trợ POP3 cho công ty

class EmailHelper {
    public $mail;
    public $config;
    
    public function __construct() {
        // POP3 Configuration for company email
        $this->config = array(
            'protocol' => 'pop3',
            'host' => 'gw.sgitech.com.vn', // Company POP3 server
            'port' => 110, // Standard POP3 port
            'username' => 'ndvu@sgitech.com.vn', // Company email
            'password' => 'ndvu', // Company email password
            'from_email' => 'ndvu@sgitech.com.vn',
            'from_name' => 'IT Service Request System',
            'encryption' => 'none', // POP3 typically no encryption
            'pop3_server' => 'gw.sgitech.com.vn',
            'smtp_server' => 'gw.sgitech.com.vn' // Company SMTP for sending
        );
    }
    
    public function sendEmail($to, $toName, $subject, $body) {
        try {
            // Method 1: Try company SMTP first
            if ($this->sendCompanySMTP($to, $toName, $subject, $body)) {
                $this->logEmail($to, $subject, $body, 'SENT_COMPANY_SMTP');
                return true;
            }
            
            // Method 2: Try PHP mail as fallback
            if ($this->sendPhpMail($to, $toName, $subject, $body)) {
                $this->logEmail($to, $subject, $body, 'SENT_PHPMAIL');
                return true;
            }
            
            // Method 3: Log for manual sending
            $this->logEmail($to, $subject, $body, 'FAILED');
            return false;
            
        } catch (Exception $e) {
            error_log("EmailHelper Error: " . $e->getMessage());
            $this->logEmail($to, $subject, $body, 'ERROR');
            return false;
        }
    }
    
    private function sendCompanySMTP($to, $toName, $subject, $body) {
        try {
            // Use PHPMailer with company SMTP
            $this->mail = new PHPMailer(true);
            
            // Server settings for company SMTP
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtp_server'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; // Try TLS first
            $this->mail->Port = 25; // Company SMTP port
            
            // Try with TLS first
            try {
                $this->mail->send();
                return true;
            } catch (Exception $e) {
                // Fallback to no encryption
                $this->mail->SMTPSecure = '';
                $this->mail->Port = 25;
                $this->mail->send();
                return true;
            }
            
        } catch (Exception $e) {
            error_log("Company SMTP Error: " . $e->getMessage());
            return false;
        }
    }
    
    private function sendPhpMail($to, $toName, $subject, $body) {
        // Encode subject for UTF-8
        $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: base64',
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
            'Reply-To: ' . $this->config['from_email'],
            'X-Mailer: PHP/' . phpversion()
        );
        
        $header_string = implode("\r\n", $headers);
        $encoded_body = chunk_split(base64_encode($body));
        
        return @mail($to, $encoded_subject, $encoded_body, $header_string);
    }
    
    private function logEmail($to, $subject, $body, $status) {
        // Create email file for backup
        if ($status === 'FAILED' || $status === 'ERROR') {
            $email_content = "To: $to\n";
            $email_content .= "Subject: $subject\n";
            $email_content .= "From: {$this->config['from_name']} <{$this->config['from_email']}>\n";
            $email_content .= "MIME-Version: 1.0\n";
            $email_content .= "Content-Type: text/html; charset=UTF-8\n\n";
            $email_content .= $body;
            
            $email_file = __DIR__ . '/../logs/email_' . date('Y-m-d_H-i-s') . '.eml';
            file_put_contents($email_file, $email_content);
        }
        
        // Log activity
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
    
    public function sendNewRequestNotification($request_data) {
        require_once __DIR__ . '/../config/database.php';
        
        // Get all admin and staff emails
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT email, full_name FROM users WHERE role IN ('admin', 'staff') AND status = 'active'");
        $stmt->execute();
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($recipients)) {
            $this->logEmail('', 'No recipients found', '', 'FAILED: No admin/staff users found');
            return false;
        }
        
        // Create email template
        $subject = "🔔 Yêu cầu dịch vụ mới #" . $request_data['id'];
        
        $body = '<div style="max-width: 600px; margin: 20px auto; background-color: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; font-family: Arial, sans-serif;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;">
                        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">🔔 IT Service Request</h1>
                        <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Hệ thống yêu cầu dịch vụ CNTT</p>
                    </div>
                    
                    <div style="padding: 30px 20px;">
                        <h2 style="color: #333; margin-bottom: 20px;">Yêu cầu dịch vụ mới</h2>
                        
                        <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Mã yêu cầu:</span>
                                <span style="color: #212529;"><strong>#' . $request_data['id'] . '</strong></span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Tiêu đề:</span>
                                <span style="color: #212529;">' . htmlspecialchars($request_data['title']) . '</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Người tạo:</span>
                                <span style="color: #212529;">' . htmlspecialchars($request_data['requester_name']) . '</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Danh mục:</span>
                                <span style="color: #212529;">' . htmlspecialchars($request_data['category']) . '</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Ưu tiên:</span>
                                <span style="color: #212529;"><span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #ffebee; color: #c62828;">' . htmlspecialchars($request_data['priority']) . '</span></span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Mô tả:</span>
                                <span style="color: #212529;">' . nl2br(htmlspecialchars($request_data['description'])) . '</span>
                            </div>
                        </div>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="http://localhost/it-service-request/" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 20px; font-weight: bold;">Xem chi tiết yêu cầu →</a>
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd;">
                        <p style="margin: 5px 0; color: #6c757d; font-size: 12px;"><strong>IT Service Request System</strong></p>
                        <p style="margin: 5px 0; color: #6c757d; font-size: 12px;">Đây là email tự động. Vui lòng không trả lời email này.</p>
                        <p style="margin: 5px 0; color: #6c757d; font-size: 12px;">Nếu cần hỗ trợ, vui lòng liên hệ IT Department.</p>
                    </div>
                </div>';
        
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
}
?>
