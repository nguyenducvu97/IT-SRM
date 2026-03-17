<?php
// Test Email Notification System
require_once 'config/database.php';
require_once 'lib/NotificationHelper.php';

echo "<h1>📧 Email Notification System Test</h1>";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✅ Database connected</p>";
    
    // Get a test user (admin or staff)
    $stmt = $db->prepare("SELECT id, email, full_name FROM users WHERE role IN ('admin', 'staff') LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "<p>❌ No admin/staff users found for testing</p>";
        exit;
    }
    
    echo "<h3>📋 Test User Info:</h3>";
    echo "<p><strong>ID:</strong> " . $testUser['id'] . "</p>";
    echo "<p><strong>Email:</strong> " . $testUser['email'] . "</p>";
    echo "<p><strong>Name:</strong> " . $testUser['full_name'] . "</p>";
    
    // Create NotificationHelper
    $notificationHelper = new NotificationHelper($db);
    echo "<p>✅ NotificationHelper initialized</p>";
    
    // Test 1: Simple notification
    echo "<h3>🧪 Test 1: Simple Info Notification</h3>";
    $result1 = $notificationHelper->createNotification(
        $testUser['id'],
        "🧪 Test Email Notification",
        "Đây là email test từ hệ thống IT Service Request vào lúc " . date('H:i:s'),
        'info',
        null,
        null,
        true // Send email
    );
    
    echo "<p><strong>Result:</strong> " . ($result1 ? "✅ Success" : "❌ Failed") . "</p>";
    
    // Test 2: Request-related notification
    echo "<h3>🧪 Test 2: Request-Related Notification</h3>";
    $result2 = $notificationHelper->createNotification(
        $testUser['id'],
        "🔔 Yêu cầu mới #123",
        "Người dùng đã tạo yêu cầu mới: 'Test Request Title'",
        'success',
        123,
        'request',
        true // Send email
    );
    
    echo "<p><strong>Result:</strong> " . ($result2 ? "✅ Success" : "❌ Failed") . "</p>";
    
    // Test 3: Multiple users notification
    echo "<h3>🧪 Test 3: Multiple Users Notification</h3>";
    $stmt = $db->prepare("SELECT id FROM users WHERE role IN ('admin', 'staff')");
    $stmt->execute();
    $allStaff = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $result3 = $notificationHelper->notifyUsers(
        $allStaff,
        "📢 Broadcast Test",
        "Đây là email broadcast test đến tất cả staff/admin vào lúc " . date('H:i:s'),
        'warning',
        null,
        null,
        true // Send email
    );
    
    echo "<p><strong>Notified Users:</strong> {$result3}/" . count($allStaff) . "</p>";
    
    // Check email logs
    echo "<h3>📋 Email Logs:</h3>";
    $logFile = __DIR__ . '/logs/email_activity.log';
    if (file_exists($logFile)) {
        $logs = file_get_contents($logFile);
        $recentLogs = substr($logs, -2000); // Last 2000 characters
        echo "<pre style='background: #f5f5f5; padding: 10px; border-radius: 4px; font-size: 12px;'>" . htmlspecialchars($recentLogs) . "</pre>";
    } else {
        echo "<p>❌ No email log file found</p>";
    }
    
    echo "<br><h3>🎯 Next Steps:</h3>";
    echo "<p>1. Check email inbox: <strong>" . $testUser['email'] . "</strong></p>";
    echo "<p>2. Look for emails with subject: 'Test Email Notification' and 'Yêu cầu mới #123'</p>";
    echo "<p>3. Verify email content and formatting</p>";
    
    echo "<br><a href='test_email_notifications.php'>Test Again</a>";
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}
?>
