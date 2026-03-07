<?php
// Deep dive into SMTP debugging to find why emails aren't arriving
echo "<h2>🔍 Deep SMTP Debug Analysis</h2>";

echo "<h3>🔬 Testing with Maximum SMTP Debug Output</h3>";

require_once 'lib/PHPMailerEmailHelper.php';

// Create fresh PHPMailer instance with maximum debugging
$mail = new PHPMailer();

try {
    // Enable verbose debugging
    $mail->SMTPDebug = 3; // Maximum debug level
    $mail->Debugoutput = 'html';
    
    // Server settings (exact original config)
    $mail->isSMTP();
    $mail->Host = 'gw.sgitech.com.vn';
    $mail->SMTPAuth = true;
    $mail->Username = 'ndvu@sgitech.com.vn';
    $mail->Password = 'ndvu';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 25;
    
    // Recipients
    $mail->setFrom('ndvu@sgitech.com.vn', 'IT Service Request System');
    $mail->addAddress('ndvu@sgitech.com.vn', 'System Administrator');
    $mail->CharSet = 'UTF-8';
    
    // Content
    $mail->Subject = '🔍 DEEP DEBUG Test - ' . date('Y-m-d H:i:s');
    $mail->Body = '<h2>Deep SMTP Debug Test</h2><p>This test shows full SMTP conversation.</p><p><strong>Time:</strong> ' . date('Y-m-d H:i:s') . '</p>';
    $mail->isHTML(true);
    
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
    echo "<h4>📡 Full SMTP Conversation:</h4>";
    
    // Capture and display full SMTP debug
    ob_start();
    $result = $mail->send();
    $debug_output = ob_get_clean();
    
    echo $debug_output;
    echo "</div>";
    
    if ($result) {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
        echo "<h3>⚠️ Email Accepted by SMTP Server</h3>";
        echo "<p><strong>SMTP server accepted the email</strong>, but it may not be delivered due to:</p>";
        echo "<ul>";
        echo "<li>🔄 Email queued for later delivery</li>";
        echo "<li>🚫 Server silently rejecting the email</li>";
        echo "<li>📧 Email routed to spam/junk folder</li>";
        echo "<li>🔒 Recipient server blocking the email</li>";
        echo "</ul>";
        echo "</div>";
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ SMTP Server Rejected Email</h3>";
        echo "<p><strong>Error:</strong> " . $mail->ErrorInfo . "</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Exception: " . $e->getMessage() . "</h3>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>🧪 Testing with External Email Addresses</h3>";

$test_emails = [
    ['email' => 'test@gmail.com', 'name' => 'Gmail Test'],
    ['email' => 'test@yahoo.com', 'name' => 'Yahoo Test'],
    ['email' => 'nguyenducvu101223@gmail.com', 'name' => 'Staff Gmail']
];

foreach ($test_emails as $test_email) {
    echo "<h4>📧 Testing to: {$test_email['email']}</h4>";
    
    try {
        $mail = new PHPMailer();
        $mail->SMTPDebug = 0; // Minimal debug for cleaner output
        
        $mail->isSMTP();
        $mail->Host = 'gw.sgitech.com.vn';
        $mail->SMTPAuth = true;
        $mail->Username = 'ndvu@sgitech.com.vn';
        $mail->Password = 'ndvu';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 25;
        
        $mail->setFrom('ndvu@sgitech.com.vn', 'IT Service Request System');
        $mail->addAddress($test_email['email'], $test_email['name']);
        $mail->CharSet = 'UTF-8';
        
        $mail->Subject = "Test to {$test_email['name']} - " . date('H:i:s');
        $mail->Body = "<p>Test email to {$test_email['email']}</p>";
        $mail->isHTML(true);
        
        if ($mail->send()) {
            echo "<p style='color: green;'>✅ Sent to {$test_email['email']}</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to {$test_email['email']}: " . $mail->ErrorInfo . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Exception to {$test_email['email']}: " . $e->getMessage() . "</p>";
    }
}

echo "<hr>";

echo "<h3>🔍 Checking Email Server Response Analysis</h3>";

// Test with detailed error capture
echo "<h4>📡 Analyzing SMTP Response Codes:</h4>";

try {
    $mail = new PHPMailer();
    $mail->SMTPDebug = 2; // Show server responses
    $mail->Debugoutput = function($str, $level) {
        echo "<div style='font-family: monospace; font-size: 11px; color: #333; background: #f0f0f0; padding: 2px; margin: 1px 0;'>";
        echo htmlspecialchars($str);
        echo "</div>";
    };
    
    $mail->isSMTP();
    $mail->Host = 'gw.sgitech.com.vn';
    $mail->SMTPAuth = true;
    $mail->Username = 'ndvu@sgitech.com.vn';
    $mail->Password = 'ndvu';
    $mail->SMTPSecure = 'tls';
    $mail->Port = 25;
    
    $mail->setFrom('ndvu@sgitech.com.vn', 'IT Service Request System');
    $mail->addAddress('ndvu@sgitech.com.vn', 'Debug Test');
    $mail->CharSet = 'UTF-8';
    
    $mail->Subject = 'SMTP Response Analysis - ' . date('H:i:s');
    $mail->Body = '<p>Analyzing SMTP server responses</p>';
    $mail->isHTML(true);
    
    echo "<div style='background: #e9ecef; padding: 10px; border-radius: 5px;'>";
    echo "<strong>SMTP Server Communication:</strong><br>";
    $result = $mail->send();
    echo "</div>";
    
    echo "<p><strong>Final Result:</strong> " . ($result ? "✅ Accepted" : "❌ Rejected") . "</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'><strong>Exception:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";

echo "<h3>🎯 Possible Issues & Solutions:</h3>";
echo "<div style='display: grid; grid-template-columns: 1fr 1fr; gap: 20px;'>";
echo "<div>";
echo "<h4>🔍 Possible Issues:</h4>";
echo "<ul>";
echo "<li>📧 Email queued but not delivered</li>";
echo "<li>🚫 Server silently rejecting</li>";
echo "<li>🔒 IP blacklisted</li>";
echo "<li>📊 Authentication issues</li>";
echo "<li>🌐 Network routing problems</li>";
echo "<li>📭 Recipient server blocking</li>";
echo "</ul>";
echo "</div>";
echo "<div>";
echo "<h4>💡 Solutions:</h4>";
echo "<ul>";
echo "<li>📞 Contact email server admin</li>";
echo "<li>🔍 Check SMTP server logs</li>";
echo "<li>📧 Test with different from address</li>";
echo "<li>🌐 Try external SMTP service</li>";
echo "<li>🔍 Check network connectivity</li>";
echo "<li>📊 Verify DNS/MX records</li>";
echo "</ul>";
echo "</div>";
echo "</div>";

echo "<hr>";

echo "<h3>📞 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Contact IT/Email Admin:</strong> Ask them to check SMTP server logs for ndvu@sgitech.com.vn</li>";
echo "<li><strong>Check webmail filters:</strong> Look in spam, trash, and other folders</li>";
echo "<li><strong>Test with external email:</strong> Try Gmail/Yahoo to see if they receive</li>";
echo "<li><strong>Consider backup SMTP:</strong> Use external service if internal fails</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
