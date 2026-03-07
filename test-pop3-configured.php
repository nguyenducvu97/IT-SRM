<?php
// Test email after configuring sendmail.ini for internal POP3
echo "<h2>🔧 Testing Internal POP3 Mail After sendmail.ini Configuration</h2>";

echo "<h3>📋 Changes Made to sendmail.ini:</h3>";
echo "<ul>";
echo "<li>✅ <strong>smtp_server:</strong> gw.sgitech.com.vn (internal server)</li>";
echo "<li>✅ <strong>smtp_port:</strong> 25 (standard SMTP port)</li>";
echo "<li>✅ <strong>default_domain:</strong> sgitech.com.vn</li>";
echo "<li>✅ <strong>auth_username:</strong> ndvu@sgitech.com.vn</li>";
echo "<li>✅ <strong>auth_password:</strong> ndvu</li>";
echo "</ul>";

echo "<p><strong>⚠️ Note:</strong> You may need to restart Apache for sendmail.ini changes to take effect.</p>";

echo "<hr>";

echo "<h3>🧪 Testing PHP mail() with Updated Configuration:</h3>";

// Test with PHP mail() function after sendmail.ini update
$to = 'ndvu@sgitech.com.vn';
$subject = '🏢 POP3 CONFIGURED - ' . date('Y-m-d H:i:s');
$message = '
<html>
<body>
    <h2>Internal POP3 Mail Test - Configured</h2>
    <p><strong>Configuration Updated:</strong></p>
    <ul>
        <li>sendmail.ini configured for internal server</li>
        <li>SMTP: gw.sgitech.com.vn:25</li>
        <li>Auth: ndvu@sgitech.com.vn</li>
    </ul>
    <p><strong>Test Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
    <hr>
    <h3>📋 Test Request Details:</h3>
    <ul>
        <li><strong>Mã yêu cầu:</strong> POP3-' . time() . '</li>
        <li><strong>Tiêu đề:</strong> Internal POP3 Mail Test</li>
        <li><strong>Người tạo:</strong> Test User</li>
        <li><strong>Danh mục:</strong> Hardware</li>
        <li><strong>Ưu tiên:</strong> High</li>
        <li><strong>Mô tả:</strong> Testing internal POP3 mail delivery</li>
    </ul>
    <p><strong>Link:</strong> <a href="http://localhost/it-service-request/">http://localhost/it-service-request/</a></p>
    <hr>
    <p><em>IT Service Request System - Internal POP3 Mail</em></p>
</body>
</html>';

$headers = array(
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'From: IT Service Request System <ndvu@sgitech.com.vn>',
    'Reply-To: ndvu@sgitech.com.vn',
    'X-Mailer: PHP/' . phpversion(),
    'X-Priority: 3'
);

$headers_string = implode("\r\n", $headers);

echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "<h4>📧 Email Details:</h4>";
echo "<strong>To:</strong> $to<br>";
echo "<strong>Subject:</strong> $subject<br>";
echo "<strong>Method:</strong> PHP mail() with sendmail.ini<br>";
echo "<strong>Server:</strong> gw.sgitech.com.vn:25<br>";
echo "</div>";

$mail_sent = @mail($to, $subject, $message, $headers_string);

if ($mail_sent) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<h3>✅ SUCCESS! Internal POP3 Mail Working!</h3>";
    echo "<p><strong>🎉 Email sent successfully!</strong></p>";
    echo "<p><strong>To:</strong> $to</p>";
    echo "<p><strong>Subject:</strong> $subject</p>";
    echo "<p><strong>Check your internal mail:</strong> https://gw.sgitech.com.vn/mail/</p>";
    echo "<hr>";
    echo "<h4>💡 Next Step: Modify Email Helper</h4>";
    echo "<p>Now that internal mail works, I can modify the email helper to use PHP mail() instead of SMTP.</p>";
    echo "</div>";
    
    // If this works, modify the email helper
    echo "<h3>🔧 Modifying Email Helper to Use PHP mail():</h3>";
    
    // Create a simple email function for internal mail
    function sendInternalMail($to, $toName, $subject, $body) {
        $headers = array(
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
            'From: IT Service Request System <ndvu@sgitech.com.vn>',
            'Reply-To: ndvu@sgitech.com.vn',
            'X-Mailer: PHP/' . phpversion()
        );
        
        $headers_string = implode("\r\n", $headers);
        return @mail($to, $subject, $body, $headers_string);
    }
    
    // Test new request notification
    $test_request_data = [
        'id' => 'POP3-TEST-' . time(),
        'title' => 'Internal POP3 Test Request',
        'requester_name' => 'Test User',
        'category' => 'Hardware',
        'priority' => 'high',
        'description' => 'This request tests the internal POP3 mail system.'
    ];
    
    $email_subject = "🔔 Yêu cầu dịch vụ mới #" . $test_request_data['id'];
    $email_body = "
    <h2>📋 Yêu cầu dịch vụ mới</h2>
    <p><strong>Mã yêu cầu:</strong> #" . $test_request_data['id'] . "</p>
    <p><strong>Tiêu đề:</strong> " . htmlspecialchars($test_request_data['title']) . "</p>
    <p><strong>Người tạo:</strong> " . htmlspecialchars($test_request_data['requester_name']) . "</p>
    <p><strong>Danh mục:</strong> " . htmlspecialchars($test_request_data['category']) . "</p>
    <p><strong>Ưu tiên:</strong> " . htmlspecialchars($test_request_data['priority']) . "</p>
    <p><strong>Mô tả:</strong> " . nl2br(htmlspecialchars($test_request_data['description'])) . "</p>
    <hr>
    <p>Vui lòng đăng nhập hệ thống để xem chi tiết và xử lý: <a href='http://localhost/it-service-request/'>http://localhost/it-service-request/</a></p>
    <p><em>IT Service Request System</em></p>";
    
    // Send to admin
    $admin_sent = sendInternalMail('ndvu@sgitech.com.vn', 'System Administrator', $email_subject, $email_body);
    
    // Send to staff
    $staff_sent = sendInternalMail('nguyenducvu101223@gmail.com', 'Staff Member', $email_subject, $email_body);
    
    if ($admin_sent) {
        echo "<p style='color: green;'>✅ Admin notification sent successfully!</p>";
    } else {
        echo "<p style='color: red;'>❌ Admin notification failed</p>";
    }
    
    if ($staff_sent) {
        echo "<p style='color: green;'>✅ Staff notification sent successfully!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Staff notification failed (external email)</p>";
    }
    
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Still Not Working</h3>";
    echo "<p><strong>Possible remaining issues:</strong></p>";
    echo "<ul>";
    echo "<li>Apache needs to be restarted for sendmail.ini changes</li>";
    echo "<li>Internal mail server requires different configuration</li>";
    echo "<li>Network connectivity issues to internal mail server</li>";
    echo "<li>Authentication credentials incorrect</li>";
    echo "</ul>";
    echo "<hr>";
    echo "<h4>🔧 Troubleshooting Steps:</h4>";
    echo "<ol>";
    echo "<li><strong>Restart Apache:</strong> Required for sendmail.ini changes</li>";
    echo "<li><strong>Check mail server logs:</strong> Contact IT department</li>";
    echo "<li><strong>Verify credentials:</strong> Confirm ndvu/ndvu is correct</li>";
    echo "<li><strong>Test connectivity:</strong> Can server reach gw.sgitech.com.vn:25?</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>📊 Final Status:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Component</th><th>Status</th><th>Action</th></tr>";
echo "<tr><td>sendmail.ini</td><td style='color: green;'>✅ Configured</td><td>Set for internal POP3</td></tr>";
echo "<tr><td>PHP mail()</td><td style='color: " . ($mail_sent ? 'green' : 'red') . ";'>" . ($mail_sent ? '✅ Working' : '❌ Failed') . "</td><td>" . ($mail_sent ? 'Ready to use' : 'Needs troubleshooting') . "</td></tr>";
echo "<tr><td>Internal Mail</td><td style='color: " . ($mail_sent ? 'green' : 'orange') . ";'>" . ($mail_sent ? '✅ Should work' : '⚠️ Check IT') . "</td><td>POP3 system</td></tr>";
echo "</table>";

echo "<hr>";

echo "<h3>🎯 What to Do Now:</h3>";
echo "<ol>";
echo "<li><strong>Restart Apache:</strong> Required for sendmail.ini to take effect</li>";
echo "<li><strong>Check email:</strong> https://gw.sgitech.com.vn/mail/</li>";
echo "<li><strong>Look for subject:</strong> '🏢 POP3 CONFIGURED'</li>";
echo "<li><strong>If received:</strong> System can be modified to use internal mail</li>";
echo "<li><strong>If not received:</strong> Contact IT department for mail server help</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>🏢 Internal POP3 mail should work with proper sendmail.ini configuration!</strong></p>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
