<?php
// Test email for POP3/internal mail system
echo "<h2>🔧 Testing Email for POP3/Internal Mail System</h2>";

echo "<h3>💡 Problem Identified:</h3>";
echo "<ul>";
echo "<li>🏢 <strong>Company uses internal POP3 mail system</strong></li>";
echo "<li>❌ <strong>SMTP configuration won't work</strong> with POP3</li>";
echo "<li>✅ <strong>Solution:</strong> Use PHP mail() function for internal mail</li>";
echo "<li>📧 <strong>Internal mail:</strong> Uses local mail server, not external SMTP</li>";
echo "</ul>";

echo "<hr>";

echo "<h3>🧪 Testing PHP mail() for Internal Mail System:</h3>";

// Test with PHP mail() function - best for internal mail
$to = 'ndvu@sgitech.com.vn';
$subject = '🏢 INTERNAL MAIL TEST - ' . date('Y-m-d H:i:s');
$message = '
<html>
<body>
    <h2>Internal Mail System Test</h2>
    <p><strong>Test Purpose:</strong> Verify internal mail delivery</p>
    <p><strong>Mail System:</strong> Internal POP3</p>
    <p><strong>Method:</strong> PHP mail() function</p>
    <p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
    <hr>
    <p><strong>Request Details:</strong></p>
    <ul>
        <li><strong>Mã yêu cầu:</strong> TEST-' . time() . '</li>
        <li><strong>Tiêu đề:</strong> Test Internal Mail</li>
        <li><strong>Người tạo:</strong> Test User</li>
        <li><strong>Danh mục:</strong> Hardware</li>
        <li><strong>Ưu tiên:</strong> High</li>
    </ul>
    <hr>
    <p><em>IT Service Request System - Internal Mail</em></p>
</body>
</html>';

$headers = array(
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'Content-Transfer-Encoding: 8bit',
    'From: IT Service Request System <ndvu@sgitech.com.vn>',
    'Reply-To: ndvu@sgitech.com.vn',
    'X-Mailer: PHP/' . phpversion(),
    'X-Priority: 3' // Normal priority
);

$headers_string = implode("\r\n", $headers);

echo "<h4>📧 Sending via PHP mail() (Internal Mail):</h4>";
echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
echo "<strong>To:</strong> $to<br>";
echo "<strong>Subject:</strong> $subject<br>";
echo "<strong>Method:</strong> PHP mail()<br>";
echo "<strong>Headers:</strong> HTML, UTF-8<br>";
echo "</div>";

$mail_sent = @mail($to, $subject, $message, $headers_string);

if ($mail_sent) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<h3>✅ Internal Mail Sent Successfully!</h3>";
    echo "<p><strong>✅ PHP mail() function worked!</strong></p>";
    echo "<p><strong>To:</strong> $to</p>";
    echo "<p><strong>Subject:</strong> $subject</p>";
    echo "<p><strong>Check your internal mail at https://gw.sgitech.com.vn/mail/</strong></p>";
    echo "<hr>";
    echo "<h4>💡 Solution: Modify Email Helper to Use PHP mail()</h4>";
    echo "<p>Since PHP mail() works with internal POP3 system, I can modify the email helper to use it instead of SMTP.</p>";
    echo "</div>";
    
    // Test to staff email as well
    echo "<h4>📧 Testing to Staff Email:</h4>";
    $staff_to = 'nguyenducvu101223@gmail.com';
    $staff_subject = '🏢 STAFF MAIL TEST - ' . date('Y-m-d H:i:s');
    $staff_sent = @mail($staff_to, $staff_subject, $message, $headers_string);
    
    if ($staff_sent) {
        echo "<p style='color: green;'>✅ Also sent to staff: $staff_to</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ Staff email failed (external), but internal mail succeeded</p>";
    }
    
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ PHP mail() Also Failed</h3>";
    echo "<p>This suggests the internal mail server needs configuration.</p>";
    echo "<p><strong>Possible issues:</strong></p>";
    echo "<ul>";
    echo "<li>sendmail.ini not configured for internal mail</li>";
    echo "<li>Internal mail server not accessible from PHP</li>";
    echo "<li>Permissions issues with mail function</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>🔧 Recommended Solution:</h3>";

if ($mail_sent) {
    echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
    echo "<h4>✅ SOLUTION FOUND: Use PHP mail() for Internal POP3</h4>";
    echo "<p><strong>Why this works:</strong></p>";
    echo "<ul>";
    echo "<li>Internal POP3 systems work best with PHP mail()</li>";
    echo "<li>PHP mail() uses local mail server configuration</li>";
    echo "<li>No SMTP authentication needed for internal mail</li>";
    echo "<li>Better compatibility with internal mail systems</li>";
    echo "</ul>";
    echo "<p><strong>Next step:</strong> I can modify PHPMailerEmailHelper to use PHP mail() instead of SMTP.</p>";
    echo "</div>";
} else {
    echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
    echo "<h4>⚠️ Need to Configure Internal Mail Server</h4>";
    echo "<p><strong>Options:</strong></p>";
    echo "<ol>";
    echo "<li>Configure sendmail.ini for internal mail server</li>";
    echo "<li>Check internal mail server settings</li>";
    echo "<li>Contact IT department for mail server configuration</li>";
    echo "<li>Consider using external SMTP as backup</li>";
    echo "</ol>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>📊 Current Email System Status:</h3>";
echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
echo "<tr><th>Method</th><th>Status</th><th>Notes</th></tr>";
echo "<tr><td>SMTP (gw.sgitech.com.vn)</td><td style='color: red;'>❌ Failed</td><td>STARTTLS issues, server doesn't support</td></tr>";
echo "<tr><td>PHP mail() - Internal</td><td style='color: " . ($mail_sent ? 'green' : 'red') . ";'>" . ($mail_sent ? '✅ Works' : '❌ Failed') . "</td><td>Best for POP3 internal mail</td></tr>";
echo "<tr><td>External SMTP (Gmail)</td><td style='color: orange;'>⚠️ Not tested</td><td>Requires App Password setup</td></tr>";
echo "</table>";

echo "<hr>";

echo "<h3>🎯 What to Check Now:</h3>";
echo "<ol>";
echo "<li><strong>Check internal mail:</strong> https://gw.sgitech.com.vn/mail/</li>";
echo "<li><strong>Look for email:</strong> Subject '🏢 INTERNAL MAIL TEST'</li>";
echo "<li><strong>If received:</strong> I'll modify system to use PHP mail()</li>";
echo "<li><strong>If not received:</strong> Need IT help with mail server</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>🏢 Internal POP3 mail systems work best with PHP mail() function!</strong></p>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
