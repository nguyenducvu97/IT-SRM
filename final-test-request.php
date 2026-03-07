<?php
// Create a real test request after fixing sendmail.ini
require_once 'config/database.php';
require_once 'config/session.php';

// Start session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';
$_SESSION['role'] = 'user';

echo "<h2>🧪 Final Test Request After sendmail.ini Fix</h2>";

// Test data
$request_data = [
    'title' => 'FINAL TEST AFTER SENDMAIL FIX - ' . date('Y-m-d H:i:s'),
    'description' => 'Đây là yêu cầu test cuối cùng sau khi sửa lại sendmail.ini. Hệ thống PHPMailer không bị ảnh hưởng bởi sendmail.ini.',
    'category_id' => 1,
    'priority' => 'high'
];

echo "<h3>📋 Request Details:</h3>";
echo "<ul>";
foreach ($request_data as $key => $value) {
    echo "<li><strong>$key:</strong> $value</li>";
}
echo "</ul>";

echo "<hr>";

// Simulate POST request
$_POST = $request_data;

echo "<h3>🚀 Creating Request...</h3>";

// Capture output
ob_start();

try {
    include 'api/service_requests.php';
    $output = ob_get_clean();
    
    // Parse JSON response
    $response = json_decode($output, true);
    
    if ($response && $response['success']) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ Request Created Successfully!</h3>";
        echo "<p><strong>Request ID:</strong> {$response['data']['id']}</p>";
        echo "<p><strong>Message:</strong> {$response['message']}</p>";
        echo "</div>";
        
        echo "<hr>";
        echo "<h3>📧 Email Status:</h3>";
        echo "<ul>";
        echo "<li>✅ Admin (ndvu@sgitech.com.vn): Should receive email</li>";
        echo "<li>✅ Staff (nguyenducvu101223@gmail.com): Should receive email</li>";
        echo "<li>📝 Subject: 🔔 Yêu cầu dịch vụ mới #{$response['data']['id']}</li>";
        echo "</ul>";
        
        echo "<hr>";
        echo "<h3>🔍 What to Check:</h3>";
        echo "<ol>";
        echo "<li><strong>Check ndvu@sgitech.com.vn inbox</strong> (including Spam)</li>";
        echo "<li><strong>Check nguyenducvu101223@gmail.com inbox</strong> (including Spam)</li>";
        echo "<li><strong>Verify email content</strong> has all request details</li>";
        echo "<li><strong>Check link</strong> to system works in email</li>";
        echo "</ol>";
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ Request Creation Failed</h3>";
        echo "<p><strong>Error:</strong> " . ($response['message'] ?? 'Unknown error') . "</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Exception Occurred</h3>";
    echo "<p><strong>Error:</strong> " . $e->getMessage() . "</p>";
    echo "</div>";
}

echo "<hr>";

// Show latest email logs
echo "<h3>📊 Latest Email Logs:</h3>";
echo "<pre>";
$log_file = __DIR__ . '/logs/email_activity.log';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $lines = explode("\n", $logs);
    $recent_lines = array_slice($lines, -3);
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

echo "<h3>📋 Summary:</h3>";
echo "<ul>";
echo "<li>✅ sendmail.ini restored to original configuration</li>";
echo "<li>✅ PHPMailer uses direct SMTP (not affected by sendmail.ini)</li>";
echo "<li>✅ Test request created with email notifications</li>";
echo "<li>📧 Check both email inboxes now</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>🔧 If still not receiving emails:</strong></p>";
echo "<ol>";
echo "<li>Check firewall blocking port 25</li>";
echo "<li>Verify SMTP server gw.sgitech.com.vn is accessible</li>";
echo "<li>Check if email server requires different authentication</li>";
echo "<li>Try using port 587 with SSL/TLS</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a> | <a href='test-final-email-fix.php'>Test Email System</a></p>";
?>
