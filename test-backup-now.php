<?php
/**
 * Test Backup System Immediately
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "<h2>🚨 TEST BACKUP SYSTEM NGAY LẬP TỨC</h2>";

// Mock staff session
startSession();
$_SESSION['user_id'] = 2; // John Smith
$_SESSION['role'] = 'staff';
$_SESSION['full_name'] = 'John Smith';
$_SESSION['username'] = 'staff1';

$db = getDatabaseConnection();

// Create a new test request
$stmt = $db->prepare("
    INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at) 
    VALUES (4, 'BACKUP TEST NOW', 'Test backup system immediately', 1, 'medium', 'open', NOW())
");
$stmt->execute();
$test_request_id = $db->lastInsertId();

echo "<h3>📝 Created test request #{$test_request_id}</h3>";

// Simulate the exact API call
echo "<h3>🔄 Simulating API call to accept_request...</h3>";

// Update request like the API does
$update_query = "UPDATE service_requests 
                SET assigned_to = 2, status = 'in_progress', 
                    assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
                WHERE id = ?";
$update_stmt = $db->prepare($update_query);
$update_stmt->execute([$test_request_id]);

// Get request details like the API does
$request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 
                         staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name
                  FROM service_requests sr
                  LEFT JOIN users u ON sr.user_id = u.id
                  LEFT JOIN users staff ON sr.assigned_to = staff.id
                  LEFT JOIN categories c ON sr.category_id = c.id
                  WHERE sr.id = ?";
$request_stmt = $db->prepare($request_query);
$request_stmt->execute([$test_request_id]);
$request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>🔥 EXECUTING BACKUP SYSTEM...</h3>";

// Execute the exact backup code from the API
error_log("=== CRITICAL: SERVER-SIDE BACKUP START - GUARANTEED EXECUTION ===");

try {
    // Force create notifications directly without relying on JavaScript
    $backup_notificationHelper = new ServiceRequestNotificationHelper();
    
    // Notify user that their request is now in progress
    error_log("BACKUP: Sending user notification...");
    $backup_user_result = $backup_notificationHelper->notifyUserRequestInProgress(
        $test_request_id, 
        $request_data['user_id'], 
        $request_data['assigned_name']
    );
    echo "📤 User notification result: " . ($backup_user_result ? "✅ SUCCESS" : "❌ FAILED") . "<br>";
    
    // Notify admin about staff acceptance
    error_log("BACKUP: Sending admin notification...");
    $backup_admin_result = $backup_notificationHelper->notifyAdminStatusChange(
        $test_request_id, 
        'open', 
        'in_progress', 
        $request_data['assigned_name'], 
        $request_data['title']
    );
    echo "📤 Admin notification result: " . ($backup_admin_result ? "✅ SUCCESS" : "❌ FAILED") . "<br>";
    
    // Log final status
    if ($backup_user_result && $backup_admin_result) {
        error_log("✅ BACKUP SUCCESS: All notifications sent for request #$test_request_id");
        echo "<h3 style='color: green;'>🎉 BACKUP SUCCESS: All notifications sent!</h3>";
    } else {
        error_log("❌ BACKUP FAILED: Some notifications failed for request #$test_request_id");
        echo "<h3 style='color: red;'>❌ BACKUP FAILED: Some notifications failed!</h3>";
    }
    
} catch (Exception $backup_e) {
    error_log("🚨 CRITICAL: Notification backup failed: " . $backup_e->getMessage());
    error_log("🚨 This should never happen - check ServiceRequestNotificationHelper");
    echo "<h3 style='color: red;'>🚨 CRITICAL ERROR: " . $backup_e->getMessage() . "</h3>";
}

error_log("=== CRITICAL: SERVER-SIDE BACKUP END ===");

// Verify notifications were created
echo "<h3>📋 Verifying notifications...</h3>";

$stmt = $db->prepare("
    SELECT n.*, u.full_name as user_name, u.role as user_role 
    FROM notifications n 
    LEFT JOIN users u ON n.user_id = u.id 
    WHERE n.related_id = ? AND n.related_type IN ('request', 'assignment')
    ORDER BY n.created_at DESC
");
$stmt->execute([$test_request_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!empty($notifications)) {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>User</th><th>Role</th><th>Title</th><th>Message</th></tr>";
    foreach ($notifications as $notif) {
        echo "<tr>";
        echo "<td>{$notif['id']}</td>";
        echo "<td>{$notif['user_name']}</td>";
        echo "<td>{$notif['user_role']}</td>";
        echo "<td>{$notif['title']}</td>";
        echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p style='color: red;'>❌ No notifications found!</p>";
}

echo "<h3>🔍 Next Steps:</h3>";
echo "<p>1. Check PHP error log for backup messages</p>";
echo "<p>2. Have staff accept a real request</p>";
echo "<p>3. Check if backup logs appear</p>";
echo "<p>4. Verify notifications in database</p>";
?>
