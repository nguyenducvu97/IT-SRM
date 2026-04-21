<?php
require_once __DIR__ . '/../autoloader.php';

class EmailHelper {
    public $mail;
    public $config;
    
    public function __construct() {
        $this->config = array(
            'protocol' => 'pop3',
            'host' => 'gw.sgitech.com.vn',
            'port' => 25,
            'username' => 'ndvu@sgitech.com.vn',
            'password' => 'ndvu',
            'from_email' => 'ndvu@sgitech.com.vn',
            'from_name' => 'IT Service Request System',
            'encryption' => 'none',
            'pop3_server' => 'gw.sgitech.com.vn',
            'smtp_server' => 'gw.sgitech.com.vn'
        );
    }
    
    public function sendEmail($to, $toName, $subject, $body) {
        try {
            if ($this->sendCompanySMTP($to, $toName, $subject, $body)) {
                $this->logEmail($to, $subject, $body, 'SENT_COMPANY_SMTP');
                return true;
            }
            
            if ($this->sendPhpMail($to, $toName, $subject, $body)) {
                $this->logEmail($to, $subject, $body, 'SENT_PHPMAIL');
                return true;
            }
            
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
            $this->mail = new PHPMailer\PHPMailer\PHPMailer(true);
            
            $this->mail->isSMTP();
            $this->mail->Host = $this->config['smtp_server'];
            $this->mail->Port = $this->config['port'];
            $this->mail->SMTPAuth = true;
            $this->mail->Username = $this->config['username'];
            $this->mail->Password = $this->config['password'];
            $this->mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = 25;
            
            $this->mail->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mail->addAddress($to, $toName);
            $this->mail->Subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
            $this->mail->Body = $body;
            $this->mail->isHTML(true);
            
            try {
                $this->mail->send();
                return true;
            } catch (Exception $e) {
                $this->mail->SMTPSecure = '';
                $this->mail->Port = 25;
                $this->mail->send();
                return true;
            }
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function sendPhpMail($to, $toName, $subject, $body) {
        $headers = [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
            'Reply-To: ' . $this->config['from_email']
        ];
        
        return mail($to, $subject, $body, implode("\r\n", $headers));
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
    
    public function sendNewRequestNotification($request_data) {
        require_once __DIR__ . '/../config/database.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT email, full_name FROM users WHERE role IN ('admin', 'staff') AND status = 'active'");
        $stmt->execute();
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($recipients)) {
            $this->logEmail('', 'No recipients found', '', 'FAILED: No admin/staff users found');
            return false;
        }
        
        $subject = "Yêu câu dich vu mõi #" . $request_data['id'];
        
        $body = '<div class="mail-container" align="left" valign="top" style="padding-top:5px;vertical-align:top;" id="displayFrameTD">
		<iframe name="displayFrame" id="displayFrame" src="./?mode=display&amp;box=&amp;&amp;iid=194969&amp;yn_preview=" width="100%" height="751" frameborder="0" marginheight="0" onload="autoResize(this);">
			<html><head>
<meta content="text/html; charset=UTF-8" http-equiv="content-type">
<style>
.email-container {
    max-width: 600px;
    margin: 20px auto;
    background-color: white;
    border: 1px solid #ddd;
    border-radius: 8px;
    overflow: hidden;
    font-family: Arial, sans-serif;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
}

.email-header {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 40px 30px;
    text-align: center;
}

.email-header h1 {
    margin: 0;
    font-size: 28px;
    font-weight: bold;
    text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.email-header p {
    margin: 8px 0 0 0;
    opacity: 0.9;
    font-size: 14px;
}

.email-body {
    padding: 40px 30px;
    background-color: #fafbfc;
}

.email-title {
    color: #2c3e50;
    font-size: 24px;
    font-weight: 600;
    margin-bottom: 25px;
    text-align: center;
    position: relative;
}

.email-title::after {
    content: "";
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 60px;
    height: 3px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 2px;
}

.request-details {
    background: white;
    border: 1px solid #e8eaed;
    border-radius: 10px;
    padding: 25px;
    margin: 20px 0;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
}

.request-detail-row {
    display: flex;
    margin-bottom: 15px;
    align-items: flex-start;
}

.request-detail-row:last-child {
    margin-bottom: 0;
}

.request-label {
    font-weight: 600;
    color: #495057;
    min-width: 120px;
    flex-shrink: 0;
    font-size: 14px;
}

.request-value {
    color: #212529;
    flex: 1;
    font-size: 14px;
    word-break: break-word;
}

.priority-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.priority-high {
    background: linear-gradient(135deg, #ff6b6b, #ff5252);
    color: white;
    box-shadow: 0 2px 8px rgba(255, 107, 107, 0.3);
}

.priority-medium {
    background: linear-gradient(135deg, #ffc107, #ff9800);
    color: white;
    box-shadow: 0 2px 8px rgba(255, 193, 7, 0.3);
}

.priority-low {
    background: linear-gradient(135deg, #4caf50, #388e3c);
    color: white;
    box-shadow: 0 2px 8px rgba(76, 175, 80, 0.3);
}

.cta-button {
    display: inline-block;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 15px 30px;
    text-decoration: none;
    border-radius: 25px;
    font-weight: 600;
    font-size: 16px;
    text-align: center;
    transition: all 0.3s ease;
    box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
    letter-spacing: 0.5px;
}

.cta-button:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.email-footer {
    background: #f8f9fa;
    padding: 30px;
    text-align: center;
    border-top: 1px solid #e8eaed;
}

.footer-text {
    margin: 5px 0;
    color: #6c757d;
    font-size: 13px;
    line-height: 1.5;
}

.footer-text strong {
    color: #495057;
}
</style>
</head>
<body marginheight="0">
<div class="email-container">
    <div class="email-header">
        <h1>IT Service Request</h1>
        <p>Hê thong yêu câu dich vu CNTT</p>
    </div>
    
    <div class="email-body">
        <h2 class="email-title">Yêu câu dich vu mõi</h2>
        
        <div class="request-details">
            <div class="request-detail-row">
                <span class="request-label">Mã yêu câu:</span>
                <span class="request-value"><strong>#' . $request_data['id'] . '</strong></span>
            </div>
            <div class="request-detail-row">
                <span class="request-label">Tiêu dê:</span>
                <span class="request-value">' . htmlspecialchars($request_data['title']) . '</span>
            </div>
            <div class="request-detail-row">
                <span class="request-label">Nguôi tao:</span>
                <span class="request-value">' . htmlspecialchars($request_data['requester_name']) . '</span>
            </div>
            <div class="request-detail-row">
                <span class="request-label">Danh mûc:</span>
                <span class="request-value">' . htmlspecialchars($request_data['category']) . '</span>
            </div>
            <div class="request-detail-row">
                <span class="request-label">Uu tiên:</span>
                <span class="request-value"><span class="priority-badge priority-' . strtolower($request_data['priority']) . '">' . htmlspecialchars($request_data['priority']) . '</span></span>
            </div>
            <div class="request-detail-row">
                <span class="request-label">Mô tã:</span>
                <span class="request-value">' . nl2br(htmlspecialchars($request_data['description'])) . '</span>
            </div>
        </div>
        
        <div style="text-align: center; margin: 30px 0;">
            <a href="http://localhost/it-service-request/" class="cta-button" target="_blank">Xem chi tiêt yêu câu -></a>
        </div>
        
        <div class="email-footer">
            <p class="footer-text"><strong>IT Service Request System</strong></p>
            <p class="footer-text">Dây là email tu dông. Vui lòng không trá loi email này.</p>
            <p class="footer-text">Nêu cân hõ trõ, vui lòng liên hê IT Department.</p>
        </div>
    </div>
</div>

</body></html>
		</iframe>
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
