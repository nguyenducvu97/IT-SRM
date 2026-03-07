<?php
// Final test of email system after fixes
require_once 'config/database.php';
require_once 'lib/PHPMailerEmailHelper.php';

echo "<h2>🔧 Final Email System Test After Fixes</h2>";

// Test data
$test_request_data = [
    'id' => 'FINAL-TEST-' . time(),
    'title' => 'Final Test Request After Fixes',
    'requester_name' => 'Test User',
    'category' => 'Hardware',
    'priority' => 'high',
    'description' => 'This is the final test after fixing email template issues and ensuring proper email delivery.'
];

echo "<h3>📋 Test Data:</h3>";
echo "<ul>";
foreach ($test_request_data as $key => $value) {
    echo "<li><strong>$key:</strong> $value</li>";
}
echo "</ul>";

echo "<hr>";

// Test 1: New request notification to admin/staff
echo "<h3>🧪 Test 1: New Request Notification (to Admin/Staff)</h3>";
try {
    $emailHelper = new PHPMailerEmailHelper();
    $result = $emailHelper->sendNewRequestNotification($test_request_data);
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ New request notification sent successfully!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Failed to send new request notification</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test 2: Status update notification to requester
echo "<h3>🧪 Test 2: Status Update Notification (to Requester)</h3>";
$test_status_data = [
    'id' => $test_request_data['id'],
    'title' => $test_request_data['title'],
    'description' => $test_request_data['description'],
    'status' => 'in_progress',
    'requester_email' => 'test@example.com',
    'requester_name' => $test_request_data['requester_name']
];

try {
    $emailHelper = new PHPMailerEmailHelper();
    $result = $emailHelper->sendStatusUpdateNotification($test_status_data, 'Test Staff Name');
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ Status update notification sent successfully!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Failed to send status update notification</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Show recent logs
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

// Check admin/staff recipients
echo "<h3>👥 Admin/Staff Recipients:</h3>";
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT email, full_name, role FROM users WHERE role IN ('admin', 'staff') ORDER BY role, full_name");
$stmt->execute();
$recipients = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($recipients)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Email</th><th>Full Name</th><th>Role</th></tr>";
    
    foreach ($recipients as $recipient) {
        echo "<tr>";
        echo "<td><strong>{$recipient['email']}</strong></td>";
        echo "<td>{$recipient['full_name']}</td>";
        echo "<td>{$recipient['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    echo "<p><strong>Total recipients:</strong> " . count($recipients) . "</p>";
} else {
    echo "<p style='color: red;'>❌ No admin or staff users found!</p>";
}

echo "<hr>";
echo "<h3>✅ Summary of Fixes Applied:</h3>";
echo "<ul>";
echo "<li>✅ Fixed ImprovedEmailHelper to send to all admin/staff (not just hardcoded email)</li>";
echo "<li>✅ Updated service_requests.php to use PHPMailerEmailHelper consistently</li>";
echo "<li>✅ Fixed staff acceptance notification to use proper email helper</li>";
echo "<li>✅ Added proper link to system in email templates</li>";
echo "<li>✅ Ensured template variables are properly replaced</li>";
echo "</ul>";

echo "<hr>";
echo "<p><strong>Next Steps:</strong></p>";
echo "<ol>";
echo "<li>Check your email inbox (including Spam folder)</li>";
echo "<li>Create a new request from the main interface to test real workflow</li>";
echo "<li>Have staff accept a request to test user notifications</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
