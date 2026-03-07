<?php
// Create a real test service request
require_once 'config/database.php';
require_once 'config/session.php';

// Start session and simulate logged in user
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';
$_SESSION['role'] = 'user';

echo "<h2>🧪 Creating Real Test Service Request</h2>";

// Prepare test data
$test_data = [
    'title' => 'TEST EMAIL REQUEST - ' . date('Y-m-d H:i:s'),
    'description' => 'Đây là yêu cầu test thực tế để kiểm tra hệ thống email. Vui lòng kiểm tra email đã nhận được thông báo chi tiết.',
    'category_id' => 1,
    'priority' => 'high'
];

echo "<h3>📋 Request Details:</h3>";
echo "<ul>";
foreach ($test_data as $key => $value) {
    echo "<li><strong>$key:</strong> $value</li>";
}
echo "</ul>";

echo "<hr>";

// Simulate POST request
$_POST = $test_data;

echo "<h3>🚀 Processing Request Creation...</h3>";

// Capture any output
ob_start();

try {
    // Include the service requests API
    include 'api/service_requests.php';
    
    $output = ob_get_clean();
    echo "<h3>📤 API Response:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'><strong>❌ Error:</strong> " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Check recent email logs
echo "<h3>📊 Recent Email Logs:</h3>";
echo "<pre>";
$log_file = __DIR__ . '/logs/email_activity.log';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $lines = explode("\n", $logs);
    $recent_lines = array_slice($lines, -5);
    foreach ($recent_lines as $line) {
        if (!empty($line) && strpos($line, date('Y-m-d')) !== false) {
            echo htmlspecialchars($line) . "\n";
        }
    }
} else {
    echo "No log file found.";
}
echo "</pre>";

echo "<hr>";

echo "<h3>📧 What to Check:</h3>";
echo "<ol>";
echo "<li><strong>Admin Email (ndvu@sgitech.com.vn):</strong> Check inbox and spam folder</li>";
echo "<li><strong>Staff Email (nguyenducvu101223@gmail.com):</strong> Check inbox and spam folder</li>";
echo "<li><strong>Email Subject:</strong> Should contain '🔔 Yêu cầu dịch vụ mới'</li>";
echo "<li><strong>Email Content:</strong> Should contain all request details with proper variables</li>";
echo "</ol>";

echo "<hr>";
echo "<p><strong>✅ Test completed!</strong> Please check your email inboxes now.</p>";
echo "<p><a href='javascript:history.back()'>← Back</a> | <a href='test-final-email-fix.php'>Run Email Test Again</a></p>";
?>
