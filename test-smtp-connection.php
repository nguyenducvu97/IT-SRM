<?php
// Test SMTP connection and create alternative email solution
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== Testing SMTP Connection ===\n\n";

// Test 1: Check if SMTP server is reachable
echo "1. Testing SMTP server connectivity...\n";
$smtp_host = 'gw.sgitech.com.vn';
$smtp_port = 25;

$socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
if ($socket) {
    echo "✅ SMTP server is reachable on port $smtp_port\n";
    fclose($socket);
} else {
    echo "❌ SMTP server connection failed: $errno - $errstr\n";
}

echo "\n2. Testing PHP mail() function...\n";
$test_subject = '🧪 Test Email from IT Service System';
$test_message = '<h2>Test Email</h2><p>This is a test email to check if PHP mail() works.</p><p>Sent at: ' . date('Y-m-d H:i:s') . '</p>';
$headers = array(
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: IT Service Request System <ndvu@sgitech.com.vn>',
    'Reply-To: ndvu@sgitech.com.vn',
    'X-Mailer: PHP/' . phpversion()
);

$header_string = implode("\r\n", $headers);
$mail_result = @mail('ndvu@sgitech.com.vn', $test_subject, $test_message, $header_string);

echo "PHP mail() result: " . ($mail_result ? "SUCCESS" : "FAILED") . "\n";

echo "\n3. Creating improved EmailHelper...\n";

// Create an improved EmailHelper that uses PHP mail as fallback
$improved_helper = '<?php
// Improved Email Helper with better fallback
class ImprovedEmailHelper {
    private $config;
    
    public function __construct() {
        $this->config = array(
            'from_email' => 'ndvu@sgitech.com.vn',
            'from_name' => 'IT Service Request System'
        );
    }
    
    public function sendEmail($to, $toName, $subject, $body) {
        // Try PHP mail first (more reliable)
        if ($this->sendPhpMail($to, $toName, $subject, $body)) {
            $this->logEmail($to, $subject, $body, 'SENT_PHPMAIL');
            return true;
        }
        
        // Log failure
        $this->logEmail($to, $subject, $body, 'FAILED');
        return false;
    }
    
    private function sendPhpMail($to, $toName, $subject, $body) {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'From: ' . $this->config[\'from_name\'] . ' <' . $this->config[\'from_email\'] . '>',
            'Reply-To: ' . $this->config[\'from_email\'],
            'X-Mailer: PHP/' . phpversion()
        );
        
        $header_string = implode("\\r\\n", $headers);
        
        return @mail($to, $subject, $body, $header_string);
    }
    
    private function logEmail($to, $subject, $body, $status) {
        $log_entry = sprintf(
            "[%s] %s | To: %s | Subject: %s\\n",
            date(\'Y-m-d H:i:s\'),
            $status,
            $to,
            $subject
        );
        
        $log_file = __DIR__ . \'/../logs/email_activity.log\';
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
    
    public function sendNewRequestNotification($request_data) {
        $subject = "🔔 Yêu cầu dịch vụ mới #" . $request_data[\'id\'];
        
        $body = "<h2>📋 Yêu cầu dịch vụ mới</h2>
                <p><strong>Mã yêu cầu:</strong> #" . $request_data[\'id\'] . "</p>
                <p><strong>Tiêu đề:</strong> " . htmlspecialchars($request_data[\'title\']) . "</p>
                <p><strong>Người tạo:</strong> " . htmlspecialchars($request_data[\'requester_name\']) . "</p>
                <p><strong>Danh mục:</strong> " . htmlspecialchars($request_data[\'category\']) . "</p>
                <p><strong>Ưu tiên:</strong> " . htmlspecialchars($request_data[\'priority\']) . "</p>
                <p><strong>Mô tả:</strong> " . nl2br(htmlspecialchars($request_data[\'description\'])) . "</p>
                <hr>
                <p>Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý.</p>
                <p><em>IT Service Request System</em></p>";
        
        return $this->sendEmail(
            \'ndvu@sgitech.com.vn\',
            \'IT Support\',
            $subject,
            $body
        );
    }
}
?>';

file_put_contents('lib/ImprovedEmailHelper.php', $improved_helper);
echo "✅ Created ImprovedEmailHelper.php\n";

echo "\n4. Testing improved email helper...\n";
require_once 'lib/ImprovedEmailHelper.php';

$improvedEmail = new ImprovedEmailHelper();
$test_result = $improvedEmail->sendNewRequestNotification([
    'id' => 888,
    'title' => 'Test with Improved Helper',
    'requester_name' => 'Test User',
    'category' => 'Hardware',
    'priority' => 'Medium',
    'description' => 'Testing improved email helper'
]);

echo "Improved helper result: " . ($test_result ? "SUCCESS" : "FAILED") . "\n";

echo "\n=== Recommendations ===\n";
echo "1. Replace EmailHelper with ImprovedEmailHelper in service_requests.php\n";
echo "2. Configure PHP mail settings in XAMPP (php.ini)\n";
echo "3. Consider using external email service like SendGrid or Mailgun\n";
echo "4. Check server firewall and SMTP settings\n";
?>
