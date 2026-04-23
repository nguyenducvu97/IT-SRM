<?php
class EmailHelper {
    private $config = [];
    
    public function __construct() {
        $this->config = [
            'smtp_server' => 'gw.sgitech.com.vn',
            'port' => 25,
            'username' => 'ndvu@sgitech.com.vn',
            'password' => 'ndvu@123',
            'from_email' => 'ndvu@sgitech.com.vn',
            'from_name' => 'IT Service Request System'
        ];
    }
    
    public function getConfig() {
        return $this->config;
    }
    
    public function sendNewRequestNotification($request_data) {
        require_once __DIR__ . '/../config/database.php';
        
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("SELECT email, full_name FROM users WHERE role = 'admin' AND status = 'active'");
        $stmt->execute();
        $recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($recipients)) {
            return false;
        }
        
        $subject = "Yêu yêu dich vu moi #" . $request_data['id'];
        
        $body = '<body marginheight="0">
<div id="am_mail_content">
<div style="max-width: 600px; margin: 20px auto; background-color: white; border: 1px solid #ddd; border-radius: 8px; overflow: hidden; font-family: Arial, sans-serif;">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px 20px; text-align: center;">
                        <h1 style="margin: 0; font-size: 24px; font-weight: bold;">IT Service Request</h1>
                        <p style="margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;">Hê thong yêu câu dich vu CNTT</p>
                    </div>
                    
                    <div style="padding: 30px 20px;">
                        <h2 style="color: #333; margin-bottom: 20px;">Yêu câu dich vu mõi</h2>
                        
                        <div style="background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;">
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Mã yêu câu:</span>
                                <span style="color: #212529;"><strong>#' . $request_data['id'] . '</strong></span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Tiêu dê:</span>
                                <span style="color: #212529;">' . htmlspecialchars($request_data['title']) . '</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Nguôi tao:</span>
                                <span style="color: #212529;">' . htmlspecialchars($request_data['requester_name']) . '</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Danh mûc:</span>
                                <span style="color: #212529;">' . htmlspecialchars($request_data['category']) . '</span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Uu tiên:</span>
                                <span style="color: #212529;"><span style="padding: 4px 8px; border-radius: 12px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: #fff3e0; color: #ef6c00;">' . htmlspecialchars($request_data['priority']) . '</span></span>
                            </div>
                            <div style="margin-bottom: 12px;">
                                <span style="font-weight: bold; color: #495057; display: inline-block; width: 100px;">Mô tã:</span>
                                <span style="color: #212529;">' . nl2br(htmlspecialchars($request_data['description'])) . '</span>
                            </div>
                        </div>
                        
                        <div style="text-align: center; margin: 30px 0;">
                            <a href="http://localhost/it-service-request/" style="display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 20px; font-weight: bold;" target="_blank">Xem chi tiêt yêu câu -></a>
                        </div>
                    </div>
                    
                    <div style="background: #f8f9fa; padding: 20px; text-align: center; border-top: 1px solid #ddd;">
                        <p style="margin: 5px 0; color: #6c757d; font-size: 12px;"><strong>IT Service Request System</strong></p>
                        <p style="margin: 5px 0; color: #6c757d; font-size: 12px;">Dây là email tu dông. Vui lòng không trá loi email này.</p>
                        <p style="margin: 5px 0; color: #6c757d; font-size: 12px;">Nêu cân hõ trõ, vui lòng liên hê IT Department.</p>
                    </div>
                </div></div>

</body>';
        
        $success_count = 0;
        $total_count = count($recipients);
        
        foreach ($recipients as $recipient) {
            if ($this->sendEmail($recipient['email'], $recipient['full_name'], $subject, $body)) {
                $success_count++;
            }
        }
        
        return $success_count > 0;
    }
    
    public function sendEmail($to, $toName, $subject, $body) {
        try {
            if ($this->sendPhpMail($to, $toName, $subject, $body)) {
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log("EmailHelper Error: " . $e->getMessage());
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
        
        $encoded_subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        return mail($to, $encoded_subject, $body, implode("\r\n", $headers));
    }
}
?>
