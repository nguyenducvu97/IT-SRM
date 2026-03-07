<?php
// Test email after fixing SMTP configuration
echo "<h2>🧪 Testing Email After SMTP Configuration Fix</h2>";

echo "<h3>🔧 Changes Made:</h3>";
echo "<ul>";
echo "<li>✅ Disabled TLS/SSL encryption (SMTPSecure = '')</li>";
echo "<li>✅ Disabled auto TLS (SMTPAutoTLS = false)</li>";
echo "<li>✅ Using port 25 with plain SMTP</li>";
echo "<li>✅ Authentication still enabled</li>";
echo "</ul>";

echo "<hr>";

// Test the fixed configuration
echo "<h3>🧪 Testing Fixed PHPMailer Configuration:</h3>";

require_once 'lib/PHPMailerEmailHelper.php';

try {
    $emailHelper = new PHPMailerEmailHelper();
    
    $test_subject = "🔧 FIXED SMTP Test - " . date('Y-m-d H:i:s');
    $test_body = "
    <h2>SMTP Configuration Fixed</h2>
    <p><strong>Changes:</strong></p>
    <ul>
        <li>No TLS/SSL encryption</li>
        <li>Port 25 plain SMTP</li>
        <li>Authentication enabled</li>
    </ul>
    <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    <p><strong>Server:</strong> gw.sgitech.com.vn:25</p>
    <hr>
    <p><em>If you receive this email, the SMTP issue is fixed!</em></p>";
    
    $result = $emailHelper->sendEmail('ndvu@sgitech.com.vn', 'System Administrator', $test_subject, $test_body);
    
    if ($result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ Email Sent Successfully!</h3>";
        echo "<p><strong>To:</strong> ndvu@sgitech.com.vn</p>";
        echo "<p><strong>Subject:</strong> $test_subject</p>";
        echo "<p><strong>Check your inbox at https://gw.sgitech.com.vn/mail/</strong></p>";
        echo "</div>";
        
        // Also test to staff email
        echo "<h3>🧪 Testing to Staff Email:</h3>";
        $staff_result = $emailHelper->sendEmail('nguyenducvu101223@gmail.com', 'Nguyễn văn tín', $test_subject, $test_body);
        
        if ($staff_result) {
            echo "<p style='color: green;'>✅ Also sent to staff: nguyenducvu101223@gmail.com</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Staff email failed, but admin email succeeded</p>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Email Still Failed</h3>";
        echo "<p>The issue might be:</p>";
        echo "<ul>";
        echo "<li>Authentication credentials incorrect</li>";
        echo "<li>Server not allowing relaying from this IP</li>";
        echo "<li>Firewall blocking SMTP traffic</li>";
        echo "</ul>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Exception Occurred</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a> | <a href='final-test-request.php'>Create Test Request</a></p>";
?>
