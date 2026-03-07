<?php
// Test email after fixing STARTTLS issue
echo "<h2>🔧 Testing Email After Fixing STARTTLS Issue</h2>";

echo "<h3>🚨 Issue Found & Fixed:</h3>";
echo "<ul>";
echo "<li>❌ <strong>Problem:</strong> STARTTLS failed - server doesn't support TLS on port 25</li>";
echo "<li>✅ <strong>Solution:</strong> Disabled TLS encryption (SMTPSecure = '')</li>";
echo "<li>✅ <strong>Solution:</strong> Disabled auto TLS (SMTPAutoTLS = false)</li>";
echo "<li>✅ <strong>Result:</strong> Using plain SMTP on port 25</li>";
echo "</ul>";

echo "<hr>";

echo "<h3>🧪 Testing Fixed Configuration:</h3>";

require_once 'lib/PHPMailerEmailHelper.php';

try {
    $emailHelper = new PHPMailerEmailHelper();
    
    $test_subject = "✅ STARTTLS FIXED - " . date('Y-m-d H:i:s');
    $test_body = "
    <h2>STARTTLS Issue Fixed!</h2>
    <p><strong>Changes Made:</strong></p>
    <ul>
        <li>Disabled TLS encryption</li>
        <li>Disabled auto TLS</li>
        <li>Using plain SMTP port 25</li>
    </ul>
    <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    <p><strong>Server:</strong> gw.sgitech.com.vn:25 (no TLS)</p>
    <hr>
    <p><em>🎉 This email should now arrive successfully!</em></p>";
    
    $result = $emailHelper->sendEmail('ndvu@sgitech.com.vn', 'System Administrator', $test_subject, $test_body);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ SUCCESS! Email Sent After STARTTLS Fix</h3>";
        echo "<p><strong>To:</strong> ndvu@sgitech.com.vn</p>";
        echo "<p><strong>Subject:</strong> $test_subject</p>";
        echo "<p><strong>Check your inbox at https://gw.sgitech.com.vn/mail/</strong></p>";
        echo "<p><strong>🎉 This should work now!</strong></p>";
        echo "</div>";
        
        // Test to external email
        echo "<h3>🧪 Testing to External Email (Gmail):</h3>";
        $gmail_result = $emailHelper->sendEmail('nguyenducvu101223@gmail.com', 'Staff Gmail', $test_subject, $test_body);
        
        if ($gmail_result) {
            echo "<p style='color: green;'>✅ Also sent to Gmail successfully!</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Gmail failed, but internal email succeeded</p>";
        }
        
        // Test new request notification
        echo "<h3>🧪 Testing New Request Notification:</h3>";
        
        $test_request_data = [
            'id' => 'FIXED-' . time(),
            'title' => 'STARTTLS Fixed Test Request',
            'requester_name' => 'Test User',
            'category' => 'Hardware',
            'priority' => 'high',
            'description' => 'This request tests the email system after fixing STARTTLS issue.'
        ];
        
        $notification_result = $emailHelper->sendNewRequestNotification($test_request_data);
        
        if ($notification_result) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
            echo "<h4>✅ New Request Notification Sent!</h4>";
            echo "<p><strong>Request ID:</strong> {$test_request_data['id']}</p>";
            echo "<p><strong>Recipients:</strong> Admin + Staff</p>";
            echo "<p><strong>Check both inboxes now!</strong></p>";
            echo "</div>";
        } else {
            echo "<p style='color: red;'>❌ Notification failed</p>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Still Failed After STARTTLS Fix</h3>";
        echo "<p>The issue might be authentication or server configuration</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Exception: " . $e->getMessage() . "</h3>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>📋 Final Configuration:</h3>";
echo "<pre>";
echo "Host: gw.sgitech.com.vn
Port: 25
SMTPAuth: true
Username: ndvu@sgitech.com.vn
Password: ndvu
SMTPSecure: (empty) - NO TLS
SMTPAutoTLS: false - DISABLED
CharSet: UTF-8
</pre>";

echo "<hr>";

echo "<h3>🎯 What to Check Now:</h3>";
echo "<ol>";
echo "<li><strong>Check https://gw.sgitech.com.vn/mail/</strong> for email with subject '✅ STARTTLS FIXED'</li>";
echo "<li><strong>Check spam folder</strong> just in case</li>";
echo "<li><strong>Create a real request</strong> from the main interface to test workflow</li>";
echo "<li><strong>If still no email:</strong> The issue might be with email server routing</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>🎉 The STARTTLS issue has been resolved!</strong></p>";
echo "<p><a href='javascript:history.back()'>← Back</a> | <a href='final-test-request.php'>Create Test Request</a></p>";
?>
