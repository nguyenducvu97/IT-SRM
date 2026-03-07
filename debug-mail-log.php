<?php
// Test and create PHP mail log to debug email issues
echo "<h2>🔍 PHP Mail Log Analysis</h2>";

echo "<h3>📋 Current php.ini Mail Configuration:</h3>";
echo "<pre>";
echo "SMTP=gw.sgitech.com.vn
smtp_port=25
sendmail_from = ndvu@sgitech.com.vn
sendmail_path = \"C:\\xampp\\sendmail\\sendmail.exe\" -t
mail.log = \"C:\\xampp\\php\\logs\\mail.log\"
</pre>";

echo "<hr>";

// Check if mail log directory exists and create if needed
$log_dir = 'C:\xampp\php\logs';
$log_file = $log_dir . '\mail.log';

if (!is_dir($log_dir)) {
    mkdir($log_dir, 0777, true);
    echo "<p style='color: orange;'>⚠️ Created log directory: $log_dir</p>";
}

echo "<h3>🧪 Testing PHP mail() with Logging Enabled:</h3>";

// Test email to generate log entries
$to = 'ndvu@sgitech.com.vn';
$subject = '🔍 MAIL LOG TEST - ' . date('Y-m-d H:i:s');
$message = '
<html>
<body>
    <h2>PHP Mail Log Test</h2>
    <p>This test will generate detailed log entries to help diagnose the email issue.</p>
    <p><strong>Test Time:</strong> ' . date('Y-m-d H:i:s') . '</p>
    <p><strong>SMTP Server:</strong> gw.sgitech.com.vn:25</p>
    <p><strong>Sendmail Path:</strong> C:\xampp\sendmail\sendmail.exe</p>
    <hr>
    <p><em>Check the mail log after this test.</em></p>
</body>
</html>';

$headers = array(
    'MIME-Version: 1.0',
    'Content-Type: text/html; charset=UTF-8',
    'From: IT Service Request System <ndvu@sgitech.com.vn>',
    'Reply-To: ndvu@sgitech.com.vn',
    'X-Mailer: PHP/' . phpversion()
);

$headers_string = implode("\r\n", $headers);

echo "<div style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
echo "<h4>📧 Sending Test Email:</h4>";
echo "<strong>To:</strong> $to<br>";
echo "<strong>Subject:</strong> $subject<br>";
echo "<strong>Method:</strong> PHP mail() with logging<br>";
echo "</div>";

// Clear previous log if exists
if (file_exists($log_file)) {
    unlink($log_file);
    echo "<p style='color: blue;'>🗑️ Cleared previous mail log</p>";
}

// Send email
$mail_sent = mail($to, $subject, $message, $headers_string);

echo "<div style='background: " . ($mail_sent ? '#d4edda' : '#f8d7da') . "; color: " . ($mail_sent ? '#155724' : '#721c24') . "; padding: 15px; border-radius: 5px;'>";
echo "<h3>" . ($mail_sent ? '✅ Mail Function Returned True' : '❌ Mail Function Returned False') . "</h3>";
echo "<p><strong>Result:</strong> " . ($mail_sent ? 'PHP mail() thinks it succeeded' : 'PHP mail() failed immediately') . "</p>";
echo "</div>";

// Wait a moment for log to be written
sleep(2);

// Check mail log
echo "<h3>📊 PHP Mail Log Contents:</h3>";

if (file_exists($log_file)) {
    $log_contents = file_get_contents($log_file);
    
    if (!empty($log_contents)) {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
        echo "<h4>📋 Mail Log Entries:</h4>";
        echo nl2br(htmlspecialchars($log_contents));
        echo "</div>";
        
        // Analyze log for common issues
        if (strpos($log_contents, 'Connection refused') !== false) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
            echo "<h4>🚨 Issue Found: Connection Refused</h4>";
            echo "<p>The SMTP server is refusing connections. Check if:</p>";
            echo "<ul>";
            echo "<li>gw.sgitech.com.vn is accessible from this server</li>";
            echo "<li>Port 25 is open and not blocked by firewall</li>";
            echo "<li>SMTP service is running on the mail server</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        if (strpos($log_contents, 'authentication') !== false && strpos($log_contents, 'failed') !== false) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
            echo "<h4>🚨 Issue Found: Authentication Failed</h4>";
            echo "<p>SMTP authentication is failing. Check:</p>";
            echo "<ul>";
            echo "<li>Username ndvu@sgitech.com.vn is correct</li>";
            echo "<li>Password ndvu is correct</li>";
            echo "<li>Account is not locked or disabled</li>";
            echo "</ul>";
            echo "</div>";
        }
        
        if (strpos($log_contents, 'STARTTLS') !== false && strpos($log_contents, 'failed') !== false) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
            echo "<h4>⚠️ Issue Found: STARTTLS Failed</h4>";
            echo "<p>The server doesn't support STARTTLS. This is expected for internal mail servers.</p>";
            echo "<p>Solution: Configure php.ini to not use encryption or use sendmail.exe directly.</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
        echo "<h4>⚠️ Mail Log is Empty</h4>";
        echo "<p>The mail log exists but is empty. This could mean:</p>";
        echo "<ul>";
        echo "<li>PHP mail() is not actually trying to send</li>";
        echo "<li>sendmail.exe is not working properly</li>";
        echo "<li>Configuration issue in sendmail.ini</li>";
        echo "</ul>";
        echo "</div>";
    }
} else {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h4>❌ Mail Log Not Created</h4>";
    echo "<p>The mail log file was not created. This indicates:</p>";
    echo "<ul>";
    echo "<li>PHP doesn't have permission to write to log directory</li>";
    echo "<li>Mail logging is not actually enabled</li>";
    echo "<li>PHP configuration issue</li>";
    echo "</ul>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>🔍 Additional Debugging:</h3>";

// Check sendmail.exe
$sendmail_path = 'C:\xampp\sendmail\sendmail.exe';
if (file_exists($sendmail_path)) {
    echo "<p style='color: green;'>✅ sendmail.exe found at: $sendmail_path</p>";
} else {
    echo "<p style='color: red;'>❌ sendmail.exe NOT found at: $sendmail_path</p>";
}

// Check sendmail.ini
$sendmail_ini = 'C:\xampp\sendmail\sendmail.ini';
if (file_exists($sendmail_ini)) {
    echo "<p style='color: green;'>✅ sendmail.ini found</p>";
    echo "<details><summary>View sendmail.ini</summary><pre>" . htmlspecialchars(file_get_contents($sendmail_ini)) . "</pre></details>";
} else {
    echo "<p style='color: red;'>❌ sendmail.ini NOT found</p>";
}

echo "<hr>";

echo "<h3>💡 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Check mail log above</strong> for specific error messages</li>";
echo "<li><strong>If log shows connection issues:</strong> Check network/firewall</li>";
echo "<li><strong>If log shows auth issues:</strong> Verify credentials</li>";
echo "<li><strong>If log is empty:</strong> sendmail.exe may not be working</li>";
echo "<li><strong>Consider alternative:</strong> Use external SMTP service</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>Check your email for subject: '🔍 MAIL LOG TEST'</strong></p>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
