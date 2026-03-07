<?php
// Restore and test original working configuration
echo "<h2>🔄 Restoring Original Working Configuration</h2>";

echo "<h3>🔧 Changes Made:</h3>";
echo "<ul>";
echo "<li>✅ Restored original PHPMailer configuration</li>";
echo "<li>✅ SMTPSecure = 'tls' (original working setting)</li>";
echo "<li>✅ Port = 25 (original)</li>";
echo "<li>✅ Authentication enabled</li>";
echo "<li>✅ Host: gw.sgitech.com.vn</li>";
echo "</ul>";

echo "<hr>";

echo "<h3>📋 Current Configuration (Original):</h3>";
echo "<pre>";
echo "Host: gw.sgitech.com.vn
Port: 25
SMTPAuth: true
Username: ndvu@sgitech.com.vn
Password: ndvu
SMTPSecure: tls
CharSet: UTF-8
From: ndvu@sgitech.com.vn
</pre>";

echo "<hr>";

// Test with original configuration
echo "<h3>🧪 Testing Original Configuration:</h3>";

require_once 'lib/PHPMailerEmailHelper.php';

try {
    $emailHelper = new PHPMailerEmailHelper();
    
    $test_subject = "🔄 ORIGINAL CONFIG Test - " . date('Y-m-d H:i:s');
    $test_body = "
    <h2>Testing Original Configuration</h2>
    <p>This test uses the original working configuration that was working before.</p>
    <p><strong>Configuration:</strong></p>
    <ul>
        <li>SMTP: gw.sgitech.com.vn:25</li>
        <li>Encryption: TLS</li>
        <li>Auth: ndvu@sgitech.com.vn</li>
    </ul>
    <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    <hr>
    <p><em>Please check if this email arrives at https://gw.sgitech.com.vn/mail/</em></p>";
    
    $result = $emailHelper->sendEmail('ndvu@sgitech.com.vn', 'System Administrator', $test_subject, $test_body);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ Email Sent with Original Config!</h3>";
        echo "<p><strong>To:</strong> ndvu@sgitech.com.vn</p>";
        echo "<p><strong>Subject:</strong> $test_subject</p>";
        echo "<p><strong>Check your inbox at https://gw.sgitech.com.vn/mail/</strong></p>";
        echo "<p><strong>If you receive this, the original config is working!</strong></p>";
        echo "</div>";
        
        // Test new request notification with original config
        echo "<h3>🧪 Testing New Request Notification:</h3>";
        
        $test_request_data = [
            'id' => 'ORIG-' . time(),
            'title' => 'Original Config Test Request',
            'requester_name' => 'Test User',
            'category' => 'Hardware',
            'priority' => 'high',
            'description' => 'Testing new request notification with original working configuration.'
        ];
        
        $notification_result = $emailHelper->sendNewRequestNotification($test_request_data);
        
        if ($notification_result) {
            echo "<p style='color: green;'>✅ New request notification sent to admin + staff!</p>";
            echo "<p><strong>Request ID:</strong> {$test_request_data['id']}</p>";
            echo "<p><strong>Check both inboxes!</strong></p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Notification failed, but direct email succeeded</p>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Original Config Also Failed</h3>";
        echo "<p>This suggests the issue is not with configuration but with:</p>";
        echo "<ul>";
        echo "<li>Network connectivity to gw.sgitech.com.vn</li>";
        echo "<li>SMTP server changes</li>";
        echo "<li>Authentication credentials</li>";
        echo "<li>Firewall blocking</li>";
        echo "</ul>";
        echo "</div>";
        
        echo "<h3>🔍 Alternative Solutions:</h3>";
        echo "<ol>";
        echo "<li><strong>Check with IT department:</strong> Has SMTP server changed?</li>";
        echo "<li><strong>Test credentials:</strong> Are username/password still valid?</li>";
        echo "<li><strong>Use external SMTP:</strong> Gmail, SendGrid, etc.</li>";
        echo "<li><strong>Check network:</strong> Can server reach gw.sgitech.com.vn?</li>";
        echo "</ol>";
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

echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Check https://gw.sgitech.com.vn/mail/ now</strong></li>";
echo "<li><strong>If email arrives:</strong> Original config is working</li>";
echo "<li><strong>If still no email:</strong> Issue is with SMTP server or network</li>";
echo "<li><strong>Contact IT admin:</strong> Verify SMTP server status and credentials</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a> | <a href='test-final-email-fix.php'>Test Email System</a></p>";
?>
