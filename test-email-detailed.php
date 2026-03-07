<?php
// Test email functionality - Detailed diagnosis
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'lib/EmailHelper.php';

echo "=== Testing Email Configuration ===\n\n";

try {
    $emailHelper = new EmailHelper();
    
    // Test basic email sending
    echo "1. Testing basic email sending...\n";
    $test_result = $emailHelper->sendEmail(
        'ndvu@sgitech.com.vn',
        'IT Support',
        '🧪 Test Email from IT Service System',
        '<h2>Test Email</h2><p>This is a test email to check if the email system is working.</p><p>Sent at: ' . date('Y-m-d H:i:s') . '</p>'
    );
    
    echo "Result: " . ($test_result ? "SUCCESS" : "FAILED") . "\n\n";
    
    // Test new request notification
    echo "2. Testing new request notification...\n";
    $request_data = [
        'id' => 999,
        'title' => 'Test Request',
        'requester_name' => 'Test User',
        'category' => 'Hardware',
        'priority' => 'High',
        'description' => 'This is a test request description'
    ];
    
    $notification_result = $emailHelper->sendNewRequestNotification($request_data);
    echo "Result: " . ($notification_result ? "SUCCESS" : "FAILED") . "\n\n";
    
    // Check email logs
    echo "3. Checking email logs...\n";
    $log_file = __DIR__ . '/logs/email_activity.log';
    if (file_exists($log_file)) {
        $logs = file_get_contents($log_file);
        $recent_logs = substr($logs, -500); // Last 500 characters
        echo "Recent email activity:\n" . $recent_logs . "\n";
    } else {
        echo "No email activity log found.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== Email Configuration Details ===\n";
echo "SMTP Host: gw.sgitech.com.vn\n";
echo "SMTP Port: 25\n";
echo "Username: ndvu@sgitech.com.vn\n";
echo "From Email: ndvu@sgitech.com.vn\n";
echo "To Email: ndvu@sgitech.com.vn\n";

echo "\n=== Troubleshooting Steps ===\n";
echo "1. Check if SMTP server gw.sgitech.com.vn is accessible\n";
echo "2. Verify email credentials are correct\n";
echo "3. Check if port 25 is open\n";
echo "4. Try alternative email configuration\n";
?>
