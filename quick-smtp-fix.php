<?php
// Quick SMTP Fix
// Cập nhật EmailHelper với Gmail SMTP ngay lập tức

echo "<h1>🚀 Quick SMTP Fix</h1>";

// Backup current config
$backup_file = __DIR__ . '/lib/EmailHelper.php.backup';
if (!file_exists($backup_file)) {
    copy(__DIR__ . '/lib/EmailHelper.php', $backup_file);
    echo "<p>✅ <strong>Backup created:</strong> {$backup_file}</p>";
} else {
    echo "<p>⚠️ <strong>Backup already exists:</strong> {$backup_file}</p>";
}

// New Gmail configuration
$gmail_config = "<?php
// Real-time Email Helper - Gửi email ngay lập tức
class EmailHelper {
    public \$mail;
    public \$config;
    
    public function __construct() {
        // Use Gmail as backup SMTP
        \$this->config = array(
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => 'your-gmail@gmail.com',
            'password' => 'your-app-password',
            'from_email' => 'your-gmail@gmail.com',
            'from_name' => 'IT Service Request System',
            'encryption' => 'tls'
        );
    }
    
    public function sendEmail(\$to, \$toName, \$subject, \$body) {
        try {
            \$this->logEmail(\$to, \$subject, \$body, 'SENT_GMAIL');
            
            // Use PHPMailer for Gmail
            \$this->mail = new PHPMailer(true);
            
            // Server settings
            \$this->mail->isSMTP();
            \$this->mail->Host = \$this->config['host'];
            \$this->mail->SMTPAuth = true;
            \$this->mail->Username = \$this->config['username'];
            \$this->mail->Password = \$this->config['password'];
            \$this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            \$this->mail->Port = \$this->config['port'];
            
            // Recipients
            \$this->mail->setFrom(\$this->config['from_email'], \$this->config['from_name']);
            \$this->mail->addAddress(\$to, \$toName);
            
            // Content
            \$this->mail->isHTML(true);
            \$this->mail->Subject = \$subject;
            \$this->mail->Body = \$body;
            
            // Send
            if (\$this->mail->send()) {
                \$this->logEmail(\$to, \$subject, \$body, 'SENT_GMAIL_SUCCESS');
                return true;
            } else {
                \$this->logEmail(\$to, \$subject, \$body, 'FAILED_GMAIL');
                return false;
            }
            
        } catch (Exception \$e) {
            \$this->logEmail(\$to, \$subject, \$body, 'ERROR_GMAIL');
            error_log('Gmail sending error: ' . \$e->getMessage());
            return false;
        }
    }
    
    private function logEmail(\$to, \$subject, \$body, \$status) {
        \$log_message = \"[\" . date('Y-m-d H:i:s') . \"] {\$status} | To: {\$to} | Subject: {\$subject}\";
        error_log(\$log_message);
    }
}
?>";

// Write new EmailHelper
file_put_contents(__DIR__ . '/lib/EmailHelper.php', $gmail_config);

echo "<p>✅ <strong>EmailHelper updated with Gmail SMTP</strong></p>";
echo "<p>📧 <strong>Next:</strong> Update your Gmail credentials in the config</p>";
echo "<p>🔄 <strong>Then test:</strong> <code>test-full-email-flow.php</code></p>";
echo "<hr>";
echo "<h2>📋 Gmail Setup Instructions:</h2>";
echo "<ol>";
echo "<li><strong>1. Enable 2FA:</strong> Go to Gmail settings → Security → 2-Step Verification</li>";
echo "<li><strong>2. Create App Password:</strong> Go to Google Account → Security → App Passwords → Generate</li>";
echo "<li><strong>3. Update credentials:</strong> Replace 'your-gmail@gmail.com' and 'your-app-password' in EmailHelper.php</li>";
echo "<li><strong>4. Test:</strong> Run <code>test-full-email-flow.php</code></li>";
echo "</ol>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<p><strong>⚠️ Important:</strong> Keep your Gmail app password secure!</p>";
echo "</div>";
?>
