<?php
// Test email sending specifically to admin
require_once 'config/database.php';
require_once 'lib/PHPMailerEmailHelper.php';

echo "<h2>🔍 Admin Email Investigation</h2>";

// Check admin users in database
$database = new Database();
$db = $database->getConnection();

echo "<h3>📋 Admin Users in Database:</h3>";
$stmt = $db->prepare("SELECT id, username, full_name, email, role FROM users WHERE role = 'admin'");
$stmt->execute();
$admin_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($admin_users)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th></tr>";
    
    foreach ($admin_users as $admin) {
        echo "<tr>";
        echo "<td>{$admin['id']}</td>";
        echo "<td>{$admin['username']}</td>";
        echo "<td>{$admin['full_name']}</td>";
        echo "<td><strong>{$admin['email']}</strong></td>";
        echo "<td>{$admin['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No admin users found in database!</p>";
}

echo "<h3>📋 All Staff Users in Database:</h3>";
$stmt = $db->prepare("SELECT id, username, full_name, email, role FROM users WHERE role = 'staff'");
$stmt->execute();
$staff_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($staff_users)) {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th></tr>";
    
    foreach ($staff_users as $staff) {
        echo "<tr>";
        echo "<td>{$staff['id']}</td>";
        echo "<td>{$staff['username']}</td>";
        echo "<td>{$staff['full_name']}</td>";
        echo "<td><strong>{$staff['email']}</strong></td>";
        echo "<td>{$staff['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: orange;'>⚠️ No staff users found in database!</p>";
}

echo "<hr>";

// Test sending email to admin specifically
echo "<h3>🧪 Test Email to Admin:</h3>";

$test_subject = "🔍 Admin Email Test - " . date('Y-m-d H:i:s');
$test_message = "
<h2>Admin Email Test</h2>
<p>This is a test email to verify admin email delivery.</p>
<p><strong>Test Time:</strong> " . date('Y-m-d H:i:s') . "</p>
<p><strong>Admin Email:</strong> ndvu@sgitech.com.vn</p>
<hr>
<p><em>IT Service Request System</em></p>
";

try {
    $emailHelper = new PHPMailerEmailHelper();
    
    echo "<p>📧 Testing email to: ndvu@sgitech.com.vn</p>";
    $result = $emailHelper->sendEmail('ndvu@sgitech.com.vn', 'System Administrator', $test_subject, $test_message);
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ Email sent successfully to admin!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Failed to send email to admin</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

// Test the new request notification with admin recipients
echo "<h3>🧪 Test New Request Notification (should go to all admin/staff):</h3>";

$test_request_data = [
    'id' => 'ADMIN-TEST-' . time(),
    'title' => 'Admin Test Request',
    'requester_name' => 'Test User',
    'category' => 'Hardware',
    'priority' => 'high',
    'description' => 'This request tests admin email notifications.'
];

try {
    $emailHelper = new PHPMailerEmailHelper();
    $result = $emailHelper->sendNewRequestNotification($test_request_data);
    
    if ($result) {
        echo "<p style='color: green; font-weight: bold;'>✅ New request notification sent to all admin/staff!</p>";
    } else {
        echo "<p style='color: red; font-weight: bold;'>❌ Failed to send new request notification</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>📊 Recent Email Logs:</h3>";
echo "<pre>";
$log_file = __DIR__ . '/logs/email_activity.log';
if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $lines = explode("\n", $logs);
    $recent_lines = array_slice($lines, -10);
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
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
