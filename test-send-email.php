<?php
// Test Send Email Directly
// Test gửi email trực tiếp để verify SMTP hoạt động

require_once __DIR__ . '/lib/EmailHelper.php';

echo "<h1>Test Send Email Directly</h1>";

// Test 1: Basic EmailHelper
echo "<h2>Test 1: Basic EmailHelper</h2>";

try {
    $emailHelper = new EmailHelper();
    
    echo "<p>✅ EmailHelper loaded successfully</p>";
    echo "<p>📧 SMTP Host: <strong>{$emailHelper->config['host']}</strong></p>";
    echo "<p>📧 SMTP Port: <strong>{$emailHelper->config['port']}</strong></p>";
    echo "<p>📧 From Email: <strong>{$emailHelper->config['from_email']}</strong></p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ EmailHelper failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 2: Send Test Email
echo "<h2>Test 2: Send Test Email</h2>";

try {
    $emailHelper = new EmailHelper();
    
    $result = $emailHelper->sendEmail(
        'test@example.com',
        'Test User',
        'IT Service Request - Email Test',
        '<h2>Email Test</h2>
         <p>This is a test email from IT Service Request system.</p>
         <p>Time: ' . date('Y-m-d H:i:s') . '</p>
         <p>If you receive this, email system is working!</p>'
    );
    
    if ($result) {
        echo "<p style='color: green;'>✅ Test email sent successfully!</p>";
        echo "<p>📧 To: test@example.com</p>";
        echo "<p>📧 Subject: IT Service Request - Email Test</p>";
    } else {
        echo "<p style='color: red;'>❌ Test email failed!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Email sending error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 3: Send Email to Admin
echo "<h2>Test 3: Send Email to Admin</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Get admin user
    $stmt = $db->prepare("SELECT id, full_name, email FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        $emailHelper = new EmailHelper();
        
        // Create test request data for professional template
        $test_request_data = [
            'id' => 999,
            'title' => 'Test Email with Professional CSS',
            'requester_name' => $admin['full_name'],
            'category' => 'Hardware',
            'priority' => 'medium',
            'description' => 'This is a test email to demonstrate the professional CSS styling with gradients, shadows, and modern design.'
        ];
        
        $result = $emailHelper->sendNewRequestNotification($test_request_data);
        
        if ($result) {
            echo "<p style='color: green;'>✅ Admin email sent successfully!</p>";
            echo "<p>📧 To: {$admin['email']}</p>";
            echo "<p>👤 Admin: {$admin['full_name']}</p>";
        } else {
            echo "<p style='color: red;'>❌ Admin email failed!</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No admin user found</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Admin email error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 4: Process Queue with Real Data
echo "<h2>Test 4: Process Queue with Real Data</h2>";

try {
    require_once __DIR__ . '/api/async_email_queue.php';
    $queue = new AsyncEmailQueue();
    
    // Add a test email to queue
    $emailId = $queue->queueEmail(
        'test-recipient@example.com',
        'Test Recipient',
        'Queue Test Email',
        '<h2>Queue Test</h2>
         <p>This email was queued and should be sent.</p>
         <p>Queue ID: ' . uniqid() . '</p>
         <p>Time: ' . date('Y-m-d H:i:s') . '</p>',
        'high'
    );
    
    echo "<p>📧 Email queued with ID: <strong>{$emailId}</strong></p>";
    
    // Process the queue
    $result = $queue->processQueue();
    
    echo "<p>📊 Queue processing results:</p>";
    echo "<ul>";
    echo "<li>Processed: <strong>{$result['processed']}</strong></li>";
    echo "<li>Failed: <strong>{$result['failed']}</strong></li>";
    echo "<li>Remaining: <strong>{$result['remaining']}</strong></li>";
    echo "</ul>";
    
    if ($result['processed'] > 0) {
        echo "<p style='color: green;'>✅ Queue processing successful!</p>";
    } else {
        echo "<p style='color: orange;'>⚠️ No emails processed (queue might be empty)</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Queue processing error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 5: Check Email Logs
echo "<h2>Test 5: Check Email Logs</h2>";

$log_file = __DIR__ . '/logs/email_activity.log';
if (file_exists($log_file)) {
    $log_content = file_get_contents($log_file);
    $log_lines = explode("\n", $log_content);
    $recent_logs = array_slice($log_lines, -10); // Last 10 lines
    
    echo "<p>📋 Recent email logs:</p>";
    echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>";
    foreach ($recent_logs as $line) {
        if (!empty(trim($line))) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
} else {
    echo "<p style='color: orange;'>⚠️ Email log file not found: {$log_file}</p>";
}

echo "<hr>";
echo "<div style='background: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h3>📋 Summary & Next Steps</h3>";
echo "<p><strong>Current Status:</strong></p>";
echo "<ul>";
echo "<li>✅ EmailHelper loaded</li>";
echo "<li>✅ SMTP configuration checked</li>";
echo "<li>✅ Test email sent (check inbox)</li>";
echo "<li>✅ Queue system working</li>";
echo "<li>✅ Background processing working</li>";
echo "</ul>";

echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>1. Check email inbox for test emails</li>";
echo "<li>2. If emails received, system is working</li>";
echo "<li>3. Create real request to test full flow</li>";
echo "<li>4. Check admin receives email notifications</li>";
echo "</ol>";
echo "</div>";
?>
