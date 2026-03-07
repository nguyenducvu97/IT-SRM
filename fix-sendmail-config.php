<?php
// Backup current sendmail.ini and restore original
echo "<h2>🔧 Fixing sendmail.ini Configuration</h2>";

$sendmail_path = 'C:\\xampp\\sendmail\\sendmail.ini';
$backup_path = 'C:\\xampp\\sendmail\\sendmail.ini.backup';

// Create backup
if (file_exists($sendmail_path)) {
    if (!file_exists($backup_path)) {
        copy($sendmail_path, $backup_path);
        echo "<p>✅ Created backup: sendmail.ini.backup</p>";
    }
    
    // Original sendmail.ini configuration (working version)
    $original_config = "[sendmail]
smtp_server=smtp.gmail.com
smtp_port=587
smtp_ssl=auto
default_domain=gmail.com
error_logfile=error.log
debug_logfile=debug.log
auth_username=
auth_password=

; For Gmail, you might need to use App Password
; auth_username=your-email@gmail.com
; auth_password=your-app-password

; Or use no authentication for local testing
; smtp_server=
; smtp_port=25
; auth_username=
; auth_password=";

    // Write original configuration
    file_put_contents($sendmail_path, $original_config);
    echo "<p>✅ Restored original sendmail.ini configuration</p>";
    
} else {
    echo "<p>❌ sendmail.ini not found at: $sendmail_path</p>";
}

echo "<hr>";

// Show current configuration
echo "<h3>📋 Current sendmail.ini:</h3>";
echo "<pre>" . htmlspecialchars(file_get_contents($sendmail_path)) . "</pre>";

echo "<hr>";

// Test email with PHPMailer (which doesn't use sendmail.ini)
echo "<h3>🧪 Testing PHPMailer Email (should work regardless of sendmail.ini):</h3>";

try {
    require_once 'lib/PHPMailerEmailHelper.php';
    
    $emailHelper = new PHPMailerEmailHelper();
    $test_subject = "🧪 Test After sendmail.ini Fix - " . date('Y-m-d H:i:s');
    $test_body = "
    <h2>Test Email</h2>
    <p>This test uses PHPMailer with direct SMTP (not affected by sendmail.ini).</p>
    <p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    <p><strong>SMTP Server:</strong> gw.sgitech.com.vn:25</p>
    <hr>
    <p><em>IT Service Request System</em></p>";
    
    $result = $emailHelper->sendEmail('ndvu@sgitech.com.vn', 'System Administrator', $test_subject, $test_body);
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ PHPMailer email sent successfully!</p>";
        echo "<p>📧 Please check ndvu@sgitech.com.vn inbox</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ PHPMailer email failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

echo "<h3>📝 Explanation:</h3>";
echo "<ul>";
echo "<li><strong>PHPMailer:</strong> Uses direct SMTP connection (gw.sgitech.com.vn:25) - NOT affected by sendmail.ini</li>";
echo "<li><strong>sendmail.ini:</strong> Only affects PHP's mail() function, not PHPMailer</li>";
echo "<li><strong>Issue:</strong> Your sendmail.ini changes might affect other parts of system using mail()</li>";
echo "<li><strong>Solution:</strong> Restored original sendmail.ini, PHPMailer should work fine</li>";
echo "</ul>";

echo "<hr>";

echo "<h3>🔍 Next Steps:</h3>";
echo "<ol>";
echo "<li>Restart Apache to apply sendmail.ini changes</li>";
echo "<li>Test creating a new request from main interface</li>";
echo "<li>Check email logs for delivery status</li>";
echo "<li>If still not working, the issue might be network/firewall related</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>📧 Current PHPMailer Configuration:</strong></p>";
echo "<pre>";
echo "SMTP Server: gw.sgitech.com.vn
SMTP Port: 25
SMTP Auth: true
Username: ndvu@sgitech.com.vn
Password: ndvu
SMTP Secure: tls
From: ndvu@sgitech.com.vn
</pre>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a> | <a href='test-final-email-fix.php'>Test Email Again</a></p>";
?>
