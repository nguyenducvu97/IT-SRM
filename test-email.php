<?php
// Test email configuration
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Email Configuration Test</h1>";

// Test 1: Check if mail function is available
echo "<h2>1. Testing PHP mail function</h2>";
if (function_exists('mail')) {
    echo "✅ PHP mail() function is available<br>";
    
    // Test sending a simple email
    $to = 'ndvu@sgitech.com.vn';
    $subject = 'Test Email from IT Service Request';
    $message = 'This is a test email to check if PHP mail is working.';
    $headers = 'From: test@sgitech.com.vn' . "\r\n" .
               'Reply-To: test@sgitech.com.vn' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();
    
    if (@mail($to, $subject, $message, $headers)) {
        echo "✅ Test email sent successfully using PHP mail()<br>";
    } else {
        echo "❌ PHP mail() failed to send email<br>";
        echo "Error: " . error_get_last()['message'] . "<br>";
    }
} else {
    echo "❌ PHP mail() function is not available<br>";
}

// Test 2: Check SMTP connection
echo "<h2>2. Testing SMTP Connection</h2>";
$smtp_host = 'gw.sgitech.com.vn';
$smtp_port = 25;

echo "Attempting to connect to $smtp_host:$smtp_port...<br>";

$socket = @fsockopen($smtp_host, $smtp_port, $errno, $errstr, 10);
if ($socket) {
    echo "✅ Successfully connected to SMTP server<br>";
    fclose($socket);
} else {
    echo "❌ Failed to connect to SMTP server<br>";
    echo "Error: $errstr ($errno)<br>";
}

// Test 3: Check EmailHelper
echo "<h2>3. Testing EmailHelper Class</h2>";
require_once 'lib/EmailHelper.php';

try {
    $emailHelper = new EmailHelper();
    echo "✅ EmailHelper class loaded successfully<br>";
    
    $test_result = $emailHelper->sendEmail(
        'ndvu@sgitech.com.vn',
        'Test User',
        'EmailHelper Test',
        '<h2>Test Email</h2><p>This is a test from EmailHelper.</p>'
    );
    
    if ($test_result) {
        echo "✅ EmailHelper sent email successfully<br>";
    } else {
        echo "❌ EmailHelper failed to send email<br>";
    }
} catch (Exception $e) {
    echo "❌ EmailHelper error: " . $e->getMessage() . "<br>";
}

// Test 4: Check email logs
echo "<h2>4. Recent Email Logs</h2>";
$log_file = 'logs/email_activity.log';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $recent_logs = array_slice(explode("\n", $logs), -5);
    echo "<pre>" . implode("\n", $recent_logs) . "</pre>";
} else {
    echo "No email log file found<br>";
}

echo "<h2>Recommendations</h2>";
echo "<ul>";
echo "<li>If PHP mail() works, the system should use that as fallback</li>";
echo "<li>If SMTP fails, check the server configuration and credentials</li>";
echo "<li>Consider using a third-party email service like SendGrid or Mailgun</li>";
echo "<li>Check XAMPP's Mercury mail server configuration</li>";
echo "</ul>";
?>
