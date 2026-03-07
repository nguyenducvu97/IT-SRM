<?php
// Real-time Email Helper - Gửi email ngay lập tức
class EmailHelper {
    public $mail;
    private $config;
    
    public function __construct() {
        // Simple constructor - no complex SMTP
        $this->config = array(
            'host' => 'gw.sgitech.com.vn',
            'port' => 25,
            'username' => 'ndvu@sgitech.com.vn',
            'password' => 'ndvu', // Update với password thật
            'from_email' => 'ndvu@sgitech.com.vn',
            'from_name' => 'IT Service Request System'
        );
    }
    
    public function sendEmail($to, $toName, $subject, $body) {
        try {
            // Method 1: Try direct SMTP first
            if ($this->sendDirectEmail($to, $toName, $subject, $body)) {
                $this->logEmail($to, $subject, $body, 'SENT_DIRECT');
                return true;
            }
            
            // Method 2: Fallback to PHP mail
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
    
    private function sendDirectEmail($to, $toName, $subject, $body) {
        try {
            $socket = @fsockopen($this->config['host'], $this->config['port'], $errno, $errstr, 5);
            
            if (!$socket) {
                return false;
            }
            
            // SMTP conversation
            fgets($socket, 515); // Greeting
            fputs($socket, "EHLO localhost\r\n");
            fgets($socket, 515);
            
            // Try AUTH LOGIN
            fputs($socket, "AUTH LOGIN\r\n");
            $response = fgets($socket, 515);
            
            if (substr($response, 0, 3) == '334') {
                fputs($socket, base64_encode($this->config['username']) . "\r\n");
                $response = fgets($socket, 515);
                
                if (substr($response, 0, 3) == '334') {
                    fputs($socket, base64_encode($this->config['password']) . "\r\n");
                    $response = fgets($socket, 515);
                    
                    if (substr($response, 0, 3) == '235') {
                        // Send email
                        fputs($socket, "MAIL FROM:<{$this->config['username']}>\r\n");
                        fgets($socket, 515);
                        
                        fputs($socket, "RCPT TO:<$to>\r\n");
                        $response = fgets($socket, 515);
                        
                        if (substr($response, 0, 3) == '250') {
                            fputs($socket, "DATA\r\n");
                            fgets($socket, 515);
                            
                            $email_content = "Subject: $subject\r\n";
                            $email_content .= "From: {$this->config['from_name']} <{$this->config['from_email']}>\r\n";
                            $email_content .= "To: $toName <$to>\r\n";
                            $email_content .= "MIME-Version: 1.0\r\n";
                            $email_content .= "Content-Type: text/html; charset=UTF-8\r\n";
                            $email_content .= "Reply-To: {$this->config['from_email']}\r\n\r\n";
                            $email_content .= $body . "\r\n.";
                            
                            fputs($socket, $email_content . "\r\n");
                            $response = fgets($socket, 515);
                            
                            fputs($socket, "QUIT\r\n");
                            fclose($socket);
                            
                            return substr($response, 0, 3) == '250';
                        }
                    }
                }
            }
            
            fclose($socket);
            return false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function sendPhpMail($to, $toName, $subject, $body) {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>',
            'Reply-To: ' . $this->config['from_email'],
            'X-Mailer: PHP/' . phpversion()
        );
        
        $header_string = implode("\r\n", $headers);
        
        return @mail($to, $subject, $body, $header_string);
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
    
    // Methods for template-based emails
    public function sendNewRequestNotification($request_data) {
        $template = $this->getTemplate('new_request');
        $subject = str_replace('{request_id}', $request_data['id'], $template['subject']);
        $body = $this->replaceTemplateVariables($template['body'], $request_data);
        
        return $this->sendEmail(
            'ndvu@sgitech.com.vn',
            'IT Support',
            $subject,
            $body
        );
    }
    
    public function sendStatusUpdateNotification($request_data, $updated_by) {
        $template = $this->getTemplate('status_update');
        $subject = str_replace('{request_id}', $request_data['id'], $template['subject']);
        $vars = array_merge($request_data, array('updated_by' => $updated_by));
        $body = $this->replaceTemplateVariables($template['body'], $vars);
        
        return $this->sendEmail(
            $request_data['email'],
            $request_data['requester_name'],
            $subject,
            $body
        );
    }
    
    public function sendNewCommentNotification($comment_data, $request_data) {
        $template = $this->getTemplate('new_comment');
        $subject = str_replace('{request_id}', $request_data['id'], $template['subject']);
        $vars = array_merge($comment_data, $request_data);
        $body = $this->replaceTemplateVariables($template['body'], $vars);
        
        // Send to requester
        $this->sendEmail(
            $request_data['email'],
            $request_data['requester_name'],
            $subject,
            $body
        );
        
        // Send to assigned staff if exists
        if (!empty($request_data['assigned_to'])) {
            $this->sendEmail(
                'ndvu@sgitech.com.vn',
                'IT Support',
                $subject,
                $body
            );
        }
        
        return true;
    }
    
    public function sendRequestResolvedNotification($request_data) {
        $template = $this->getTemplate('request_resolved');
        $subject = str_replace('{request_id}', $request_data['id'], $template['subject']);
        $body = $this->replaceTemplateVariables($template['body'], $request_data);
        
        return $this->sendEmail(
            $request_data['email'],
            $request_data['requester_name'],
            $subject,
            $body
        );
    }
    
    public function sendRequestAssignedNotification($request_data) {
        $template = $this->getTemplate('request_assigned');
        $subject = str_replace('{request_id}', $request_data['id'], $template['subject']);
        $body = $this->replaceTemplateVariables($template['body'], $request_data);
        
        return $this->sendEmail(
            'ndvu@sgitech.com.vn',
            'IT Support',
            $subject,
            $body
        );
    }
    
    private function getTemplate($type) {
        $templates = array(
            'new_request' => array(
                'subject' => '🔔 Yêu cầu dịch vụ mới #{request_id}',
                'body' => '<h2>📋 Yêu cầu dịch vụ mới</h2>
                         <p><strong>Mã yêu cầu:</strong> #{request_id}</p>
                         <p><strong>Tiêu đề:</strong> {title}</p>
                         <p><strong>Người tạo:</strong> {requester_name}</p>
                         <p><strong>Danh mục:</strong> {category}</p>
                         <p><strong>Ưu tiên:</strong> {priority}</p>
                         <p><strong>Mô tả:</strong> {description}</p>
                         <hr>
                         <p>Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý.</p>
                         <p><em>IT Service Request System</em></p>'
            ),
            'status_update' => array(
                'subject' => '📝 Cập nhật trạng thái yêu cầu #{request_id}',
                'body' => '<h2>🔄 Trạng thái yêu cầu đã cập nhật</h2>
                         <p><strong>Mã yêu cầu:</strong> #{request_id}</p>
                         <p><strong>Tiêu đề:</strong> {title}</p>
                         <p><strong>Trạng thái mới:</strong> {status}</p>
                         <p><strong>Người cập nhật:</strong> {updated_by}</p>
                         <hr>
                         <p>Vui lòng đăng nhập hệ thống để xem chi tiết.</p>
                         <p><em>IT Service Request System</em></p>'
            ),
            'new_comment' => array(
                'subject' => '💬 Bình luận mới cho yêu cầu #{request_id}',
                'body' => '<h2>💬 Có bình luận mới</h2>
                         <p><strong>Mã yêu cầu:</strong> #{request_id}</p>
                         <p><strong>Tiêu đề:</strong> {title}</p>
                         <p><strong>Người bình luận:</strong> {commenter_name}</p>
                         <p><strong>Nội dung:</strong> {comment}</p>
                         <hr>
                         <p>Vui lòng đăng nhập hệ thống để xem chi tiết và trả lời.</p>
                         <p><em>IT Service Request System</em></p>'
            ),
            'request_resolved' => array(
                'subject' => '✅ Yêu cầu #{request_id} đã được giải quyết',
                'body' => '<h2>✅ Yêu cầu đã giải quyết</h2>
                         <p><strong>Mã yêu cầu:</strong> #{request_id}</p>
                         <p><strong>Tiêu đề:</strong> {title}</p>
                         <p><strong>Giải pháp:</strong> {solution}</p>
                         <p><strong>Người giải quyết:</strong> {resolved_by}</p>
                         <hr>
                         <p>Vui lòng đăng nhập hệ thống để xem chi tiết và đánh giá.</p>
                         <p><em>IT Service Request System</em></p>'
            ),
            'request_assigned' => array(
                'subject' => '👤 Yêu cầu #{request_id} đã được giao',
                'body' => '<h2>👤 Yêu cầu mới được giao</h2>
                         <p><strong>Mã yêu cầu:</strong> #{request_id}</p>
                         <p><strong>Tiêu đề:</strong> {title}</p>
                         <p><strong>Người tạo:</strong> {requester_name}</p>
                         <p><strong>Danh mục:</strong> {category}</p>
                         <p><strong>Ưu tiên:</strong> {priority}</p>
                         <hr>
                         <p>Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý.</p>
                         <p><em>IT Service Request System</em></p>'
            )
        );
        
        return isset($templates[$type]) ? $templates[$type] : array('subject' => 'Email', 'body' => 'Email content');
    }
    
    private function replaceTemplateVariables($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }
}
?>
