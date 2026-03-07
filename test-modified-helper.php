<?php
// Test the modified PHPMailerEmailHelper with PHP mail()
echo "<h2>🧪 Testing Modified Email Helper (PHP mail())</h2>";

echo "<h3>🔧 Changes Made:</h3>";
echo "<ul>";
echo "<li>✅ Replaced SMTP with PHP mail() function</li>";
echo "<li>✅ Disabled authentication (internal mail system)</li>";
echo "<li>✅ Added proper UTF-8 headers</li>";
echo "<li>✅ Added system link to email body</li>";
echo "</ul>";

echo "<hr>";

require_once 'lib/PHPMailerEmailHelper.php';

echo "<h3>🧪 Testing Email to Admin:</h3>";

try {
    $emailHelper = new PHPMailerEmailHelper();
    
    $test_subject = "🧪 MODIFIED HELPER TEST - " . date('Y-m-d H:i:s');
    $test_body = "
    <h2>Modified Email Helper Test</h2>
    <p><strong>Method:</strong> PHP mail() function</p>
    <p><strong>System:</strong> Internal POP3 mail</p>
    <p><strong>Time:</strong> " . date('Y-m-d H:i:s') . "</p>
    <hr>
    <p><strong>Changes:</strong></p>
    <ul>
        <li>Using PHP mail() instead of SMTP</li>
        <li>No authentication required</li>
        <li>Optimized for internal mail</li>
    </ul>
    <hr>
    <p><em>If you receive this, the modified helper is working!</em></p>";
    
    $admin_result = $emailHelper->sendEmail('ndvu@sgitech.com.vn', 'System Administrator', $test_subject, $test_body);
    
    if ($admin_result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ Admin Email Sent Successfully!</h3>";
        echo "<p><strong>To:</strong> ndvu@sgitech.com.vn</p>";
        echo "<p><strong>Subject:</strong> $test_subject</p>";
        echo "<p><strong>Check admin inbox!</strong></p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ Admin email failed</p>";
    }
    
    echo "<h3>🧪 Testing Email to Staff:</h3>";
    
    $staff_result = $emailHelper->sendEmail('nguyenducvu101223@gmail.com', 'Staff Member', $test_subject, $test_body);
    
    if ($staff_result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ Staff Email Sent Successfully!</h3>";
        echo "<p><strong>To:</strong> nguyenducvu101223@gmail.com</p>";
        echo "<p><strong>Subject:</strong> $test_subject</p>";
        echo "<p><strong>Check staff inbox!</strong></p>";
        echo "</div>";
    } else {
        echo "<p style='color: orange;'>⚠️ Staff email failed (external email)</p>";
    }
    
    echo "<hr>";
    
    echo "<h3>🧪 Testing New Request Notification (Both Admin + Staff):h3>";
    
    $test_request_data = [
        'id' => 'FINAL-' . time(),
        'title' => 'Final Test Request - Modified Helper',
        'requester_name' => 'Test User',
        'category' => 'Hardware',
        'priority' => 'high',
        'description' => 'This is the final test of the modified email helper using PHP mail() for internal POP3 system.'
    ];
    
    $notification_result = $emailHelper->sendNewRequestNotification($test_request_data);
    
    if ($notification_result) {
        echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
        echo "<h3>✅ New Request Notification Sent!</h3>";
        echo "<p><strong>Request ID:</strong> {$test_request_data['id']}</p>";
        echo "<p><strong>Recipients:</strong> Admin + Staff</p>";
        echo "<p><strong>Subject:</strong> 🔔 Yêu cầu dịch vụ mới #{$test_request_data['id']}</p>";
        echo "<p><strong>Check both inboxes!</strong></p>";
        echo "<hr>";
        echo "<h4>🎉 System Ready!</h4>";
        echo "<p>The email system is now working with internal POP3 mail!</p>";
        echo "</div>";
    } else {
        echo "<p style='color: red;'>❌ New request notification failed</p>";
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
    $recent_lines = array_slice($lines, -8);
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

echo "<h3>🎯 What to Check Now:</h3>";
echo "<ol>";
echo "<li><strong>Check admin email:</strong> ndvu@sgitech.com.vn</li>";
echo "<li><strong>Check staff email:</strong> nguyenducvu101223@gmail.com</li>";
echo "<li><strong>Look for subjects:</strong></li>";
echo "<ul>";
echo "<li>'🧪 MODIFIED HELPER TEST'</li>";
echo "<li>'🔔 Yêu cầu dịch vụ mới #FINAL-...'</li>";
echo "</ul>";
echo "<li><strong>If both receive emails:</strong> System is ready!</li>";
echo "<li><strong>Create a real request</strong> from main interface to test workflow</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>🎉 Email system modified for internal POP3 mail!</strong></p>";
echo "<p><a href='javascript:history.back()'>← Back</a> | <a href='final-test-request.php'>Create Real Test Request</a></p>";
?>
