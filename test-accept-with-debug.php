<?php
/**
 * Test accept request with debug logging
 */

require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "<h2>🧪 TEST ACCEPT REQUEST VỚI DEBUG LOGGING</h2>";
echo "<style>
    .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
    .success { background-color: #d4edda; border-color: #c3e6cb; }
    .error { background-color: #f8d7da; border-color: #f5c6cb; }
    .info { background-color: #d1ecf1; border-color: #bee5eb; }
    .warning { background-color: #fff3cd; border-color: #ffeaa7; }
</style>";

// Mock staff session
startSession();
$_SESSION['user_id'] = 2; // John Smith
$_SESSION['role'] = 'staff';
$_SESSION['full_name'] = 'John Smith';
$_SESSION['username'] = 'staff1';

$db = getDatabaseConnection();

echo "<div class='section info'>";
echo "<h3>🔧 TEST SETUP</h3>";

// Create a new test request
$stmt = $db->prepare("
    INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at) 
    VALUES (4, 'DEBUG TEST REQUEST', 'This is a test request for debug logging', 1, 'medium', 'open', NOW())
");
$stmt->execute();
$test_request_id = $db->lastInsertId();

echo "✅ Created test request #{$test_request_id}<br>";
echo "✅ Staff: John Smith (ID: 2)<br>";
echo "✅ User: Nguyễn Đức Vũ (ID: 4)<br>";

echo "</div>";

echo "<div class='section warning'>";
echo "<h3>🔍 KIỂM TRA DEBUG LOGGING TRƯỚC KHI TEST</h3>";

// Check if debug logging exists in service_requests.php
$service_requests_file = file_get_contents('api/service_requests.php');
if (strpos($service_requests_file, 'STAFF ACCEPT REQUEST NOTIFICATION DEBUG') !== false) {
    echo "✅ Debug logging đã được thêm vào service_requests.php<br>";
} else {
    echo "❌ Debug logging CHƯA được thêm vào service_requests.php<br>";
}

echo "</div>";

echo "<div class='section info'>";
echo "<h3>🚀 THỰC HIỆN ACCEPT REQUEST</h3>";

try {
    // Simulate the exact same logic as accept_request endpoint
    $request_id = $test_request_id;
    $user_id = $_SESSION['user_id'];
    $user_role = $_SESSION['role'];
    
    error_log("=== MANUAL TEST STAFF ACCEPT REQUEST DEBUG ===");
    error_log("Request ID: " . $request_id);
    error_log("User ID: " . $user_id);
    error_log("User Role: " . $user_role);
    
    // Check if request exists and is available
    $check_query = "SELECT id, assigned_to, status FROM service_requests 
                   WHERE id = :request_id AND (status = 'open' OR status = 'request_support') 
                   AND (assigned_to IS NULL OR assigned_to = 0)";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(":request_id", $request_id);
    $check_stmt->execute();
    
    if ($check_stmt->rowCount() == 0) {
        echo "❌ Request not available for assignment<br>";
    } else {
        // Update request
        $update_query = "UPDATE service_requests 
                        SET assigned_to = :user_id, status = 'in_progress', 
                            assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
                        WHERE id = :request_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":request_id", $request_id);
        $update_stmt->bindParam(":user_id", $user_id);
        
        if ($update_stmt->execute()) {
            echo "✅ Request updated successfully<br>";
            
            // Get request details
            $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 
                                     staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name
                              FROM service_requests sr
                              LEFT JOIN users u ON sr.user_id = u.id
                              LEFT JOIN users staff ON sr.assigned_to = staff.id
                              LEFT JOIN categories c ON sr.category_id = c.id
                              WHERE sr.id = :request_id";
            $request_stmt = $db->prepare($request_query);
            $request_stmt->bindParam(":request_id", $request_id);
            $request_stmt->execute();
            $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
            
            error_log("Request Data: " . print_r($request_data, true));
            error_log("User ID: " . $request_data['user_id']);
            error_log("Assigned Name: " . $request_data['assigned_name']);
            
            // Send notifications
            require_once 'lib/ServiceRequestNotificationHelper.php';
            $notificationHelper = new ServiceRequestNotificationHelper();
            error_log("NotificationHelper created successfully");
            
            // Notify user
            error_log("Sending user notification...");
            $userResult = $notificationHelper->notifyUserRequestInProgress(
                $request_id, 
                $request_data['user_id'], 
                $request_data['assigned_name']
            );
            error_log("User notification result: " . ($userResult ? "SUCCESS" : "FAILED"));
            
            // Notify admin
            error_log("Sending admin notification...");
            $adminResult = $notificationHelper->notifyAdminStatusChange(
                $request_id, 
                'open', 
                'in_progress', 
                $request_data['assigned_name'], 
                $request_data['title']
            );
            error_log("Admin notification result: " . ($adminResult ? "SUCCESS" : "FAILED"));
            
            error_log("=== END MANUAL TEST STAFF ACCEPT REQUEST DEBUG ===");
            
            echo "✅ User notification: " . ($userResult ? "SUCCESS" : "FAILED") . "<br>";
            echo "✅ Admin notification: " . ($adminResult ? "SUCCESS" : "FAILED") . "<br>";
            
        } else {
            echo "❌ Failed to update request<br>";
        }
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    error_log("Manual test error: " . $e->getMessage());
}

echo "</div>";

echo "<div class='section success'>";
echo "<h3>📋 KẾT QUẢ</h3>";
echo "<p><strong>Test hoàn tất!</strong></p>";
echo "<p>1. Kiểm tra <strong>error.log</strong> để xem debug logs</p>";
echo "<p>2. Kiểm tra <strong>notifications table</strong> để xem thông báo được tạo</p>";
echo "<p>3. So sánh với yêu cầu #132 (không có logs)</p>";
echo "</div>";
?>
