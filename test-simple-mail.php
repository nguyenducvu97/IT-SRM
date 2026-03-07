<?php
// Simple internal mail helper for POP3 system
class SimpleInternalMailHelper {
    
    public function sendEmail($to, $toName, $subject, $body) {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: IT Service Request System <ndvu@sgitech.com.vn>',
            'Reply-To: ndvu@sgitech.com.vn',
            'X-Mailer: PHP/' . phpversion()
        );
        
        $headers_string = implode("\r\n", $headers);
        $result = mail($to, $subject, $body, $headers_string);
        
        // Log the attempt
        $this->logEmail($to, $subject, $result);
        
        return $result;
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
            $this->logEmail('', 'No recipients found', false);
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
                <p>Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý: <a href='http://localhost/it-service-request/'>http://localhost/it-service-request/</a></p>
                <p><em>IT Service Request System</em></p>";
        
        $success_count = 0;
        $total_count = count($recipients);
        
        foreach ($recipients as $recipient) {
            if ($this->sendEmail($recipient['email'], $recipient['full_name'], $subject, $body)) {
                $success_count++;
            }
        }
        
        $this->logEmail('multiple', $subject, $success_count > 0);
        return $success_count > 0;
    }
    
    private function logEmail($to, $subject, $success) {
        $status = $success ? 'SENT_INTERNAL' : 'FAILED_INTERNAL';
        $log_entry = sprintf(
            "[%s] %s | To: %s | Subject: %s\n",
            date("Y-m-d H:i:s"),
            $status,
            $to,
            $subject
        );
        
        $log_file = __DIR__ . "/../logs/email_activity.log";
        file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
    }
}

echo "<h2>🧪 Testing Simple Internal Mail Helper</h2>";

echo "<h3>📧 Simple Mail Helper Features:</h3>";
echo "<ul>";
echo "<li>✅ Uses PHP mail() function (no SMTP)</li>";
echo "<li>✅ Optimized for internal POP3 systems</li>";
echo "<li>✅ Sends to all admin/staff automatically</li>";
echo "<li>✅ Proper UTF-8 encoding</li>";
echo "<li>✅ Detailed logging</li>";
echo "</ul>";

echo "<hr>";

// Test the simple mail helper
echo "<h3>🧪 Testing Simple Mail Helper:</h3>";

try {
    $mailHelper = new SimpleInternalMailHelper();
    
    $test_subject = "🧪 SIMPLE INTERNAL MAIL - " . date('Y-m-d H:i:s');
    $test_body = "
    <h2>Simple Internal Mail Test</h2>
    <p><strong>Method:</strong> PHP mail() function</p>
    <p><strong>System:</strong> Internal POP3</p>
    <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    <hr>
    <p><em>This is a test of the simplified internal mail system.</em></p>";
    
    $result = $mailHelper->sendEmail('ndvu@sgitech.com.vn', 'System Administrator', $test_subject, $test_body);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ Simple Internal Mail Working!</h3>";
        echo "<p><strong>🎉 Email sent successfully!</strong></p>";
        echo "<p><strong>To:</strong> ndvu@sgitech.com.vn</p>";
        echo "<p><strong>Subject:</strong> $test_subject</p>";
        echo "<p><strong>Check your internal mail:</strong> https://gw.sgitech.com.vn/mail/</p>";
        echo "</div>";
        
        // Test new request notification
        echo "<h3>🧪 Testing New Request Notification:</h3>";
        
        $test_request_data = [
            'id' => 'SIMPLE-' . time(),
            'title' => 'Simple Internal Mail Test Request',
            'requester_name' => 'Test User',
            'category' => 'Hardware',
            'priority' => 'high',
            'description' => 'This request tests the simplified internal mail notification system.'
        ];
        
        $notification_result = $mailHelper->sendNewRequestNotification($test_request_data);
        
        if ($notification_result) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
            echo "<h4>✅ New Request Notification Sent!</h4>";
            echo "<p><strong>Request ID:</strong> {$test_request_data['id']}</p>";
            echo "<p><strong>Recipients:</strong> All admin and staff</p>";
            echo "<p><strong>Check both inboxes!</strong></p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Notification failed</p>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Simple Internal Mail Failed</h3>";
        echo "<p>Even the simplified approach failed. This indicates a fundamental issue with the mail server configuration.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Exception: " . $e->getMessage() . "</h3>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>📊 Recent Email Logs:</h3>";
echo "<pre>";
$log_file = __DIR__ . '/logs/email_activity.log';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $lines = explode("\n", $logs);
    $recent_lines = array_slice($lines, -5);
    foreach ($recent_lines as $line) {
        if (!empty($line)) {
            echo htmlspecialchars($line) . "\n";
        }
    }
} else {
    echo "No log file found.";
}
echo "</pre>";

echo "<hr>";

echo "<h3>💡 If This Works:</h3>";
echo "<p>If the simple internal mail works, I can replace the current PHPMailerEmailHelper with this simplified version that's optimized for internal POP3 systems.</p>";

echo "<hr>";
echo "<p><strong>Check your email now for subject: '🧪 SIMPLE INTERNAL MAIL'</strong></p>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
