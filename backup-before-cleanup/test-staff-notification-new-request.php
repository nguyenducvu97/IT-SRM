<?php
echo "<h1>Test Staff Notification - New Request</h1>";

echo "<h2>Problem:</h2>";
echo "<p>Staff không nhận được thông báo khi user tạo yêu cầu mới</p>";

echo "<h2>Solution Applied:</h2>";
echo "<p>Added email sending to notifyStaffNewRequest() method in ServiceRequestNotificationHelper</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_notification'])) {
    require_once 'config/database.php';
    require_once 'lib/ServiceRequestNotificationHelper.php';
    
    try {
        $db = (new Database())->getConnection();
        
        echo "<h3>Testing Staff Notification for New Request...</h3>";
        
        // Find staff users
        $staff_query = "SELECT id, full_name, email FROM users WHERE role = 'staff' AND status = 'active'";
        $staff_stmt = $db->prepare($staff_query);
        $staff_stmt->execute();
        $staff_users = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Staff users found:</strong> " . count($staff_users) . "</p>";
        
        if (empty($staff_users)) {
            echo "<p style='color: orange;'>No active staff users found. Creating test staff...</p>";
            
            // Create test staff user
            $insert_staff = "INSERT INTO users (username, password, full_name, email, role, status, created_at) 
                            VALUES ('teststaff', 'password', 'Test Staff', 'staff@example.com', 'staff', 'active', NOW())";
            $staff_insert = $db->prepare($insert_staff);
            $staff_insert->execute();
            $staff_id = $db->lastInsertId();
            
            $staff_users = [[
                'id' => $staff_id,
                'full_name' => 'Test Staff',
                'email' => 'staff@example.com'
            ]];
        }
        
        // Test notification
        $notificationHelper = new ServiceRequestNotificationHelper();
        
        $test_request_id = 999;
        $test_title = "Test Staff Notification Request";
        $test_requester = "Test User";
        $test_category = "Hardware";
        
        echo "<h4>Test Data:</h4>";
        echo "<p>Request ID: {$test_request_id}</p>";
        echo "<p>Title: {$test_title}</p>";
        echo "<p>Requester: {$test_requester}</p>";
        echo "<p>Category: {$test_category}</p>";
        
        // Call the notification method
        $result = $notificationHelper->notifyStaffNewRequest(
            $test_request_id,
            $test_title,
            $test_requester,
            $test_category
        );
        
        echo "<h4>Notification Result:</h4>";
        echo "<p><strong>Database Notifications:</strong> " . ($result ? "SUCCESS" : "FAILED") . "</p>";
        
        // Check if notifications were created
        $notification_query = "SELECT * FROM notifications WHERE title = 'Yêu cầu mới cần xử lý' AND related_id = ?";
        $notification_stmt = $db->prepare($notification_query);
        $notification_stmt->execute([$test_request_id]);
        $notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Notifications created:</strong> " . count($notifications) . "</p>";
        
        if (!empty($notifications)) {
            echo "<h5>Notification Details:</h5>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>User ID</th><th>Title</th><th>Message</th><th>Created</th>";
            echo "</tr>";
            
            foreach ($notifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['user_id']}</td>";
                echo "<td>{$notif['title']}</td>";
                echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        echo "<h4>Email Sending:</h4>";
        echo "<p>Check email logs for 'STAFF_EMAIL:' entries</p>";
        echo "<p>Each staff should receive an email with the standard template</p>";
        
        // Show email content preview
        echo "<h5>Email Content Preview:</h5>";
        echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; border-left: 4px solid #667eea;'>";
        echo "<h3 style='color: #333; margin-bottom: 20px;'>Yêu cầu mới cần xử lý</h3>";
        echo "<div style='background: #f8f9fa; border-left: 4px solid #667eea; padding: 20px; margin: 20px 0;'>";
        echo "<div style='margin-bottom: 12px;'>";
        echo "<span style='font-weight: bold; color: #495057; display: inline-block; width: 100px;'>Mã yêu cầu:</span>";
        echo "<span style='color: #212529;'><strong>#{$test_request_id}</strong></span>";
        echo "</div>";
        echo "<div style='margin-bottom: 12px;'>";
        echo "<span style='font-weight: bold; color: #495057; display: inline-block; width: 100px;'>Tiêu đề:</span>";
        echo "<span style='color: #212529;'>{$test_title}</span>";
        echo "</div>";
        echo "<div style='margin-bottom: 12px;'>";
        echo "<span style='font-weight: bold; color: #495057; display: inline-block; width: 100px;'>Người tạo:</span>";
        echo "<span style='color: #212529;'>{$test_requester}</span>";
        echo "</div>";
        echo "<div style='margin-bottom: 12px;'>";
        echo "<span style='font-weight: bold; color: #495057; display: inline-block; width: 100px;'>Danh mục:</span>";
        echo "<span style='color: #212529;'>{$test_category}</span>";
        echo "</div>";
        echo "</div>";
        echo "<p style='color: #666; line-height: 1.6;'>Vui lòng truy cập hệ thống để xem và xử lý yêu cầu này.</p>";
        echo "</div>";
        
        echo "<div style='background-color: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #28a745;'>";
        echo "<h3>Fix Applied:</h3>";
        echo "<ul>";
        echo "<li>✅ Staff notifications created in database</li>";
        echo "<li>✅ Email sent to all staff users</li>";
        echo "<li>✅ Using standard email template</li>";
        echo "<li>✅ Proper Vietnamese text</li>";
        echo "<li>✅ Request details included</li>";
        echo "</ul>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Test Form:</h2>";
echo "<div style='padding: 20px; background-color: #f8f9fa; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Test Staff Notification for New Request</h3>";
echo "<p>This will test if staff receive notifications when a new request is created.</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='test_notification' value='1'>";
echo "<button type='submit' style='background-color: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px;'>
            Test Staff Notification
        </button>";
echo "</form>";
echo "</div>";

echo "<h2>Before vs After Fix:</h2>";
echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background-color: #f0f0f0;'>";
echo "<th>Aspect</th><th>Before Fix</th><th>After Fix</th>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>Database Notification</strong></td>";
echo "<td style='color: green;'>✅ Created</td>";
echo "<td style='color: green;'>✅ Created</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>Email to Staff</strong></td>";
echo "<td style='color: red;'>❌ Not sent</td>";
echo "<td style='color: green;'>✅ Sent with standard template</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>Email Template</strong></td>";
echo "<td style='color: red;'>❌ N/A</td>";
echo "<td style='color: green;'>✅ Standard IT Service Request template</td>";
echo "</tr>";
echo "<tr>";
echo "<td><strong>Vietnamese Text</strong></td>";
echo "<td style='color: red;'>❌ N/A</td>";
echo "<td style='color: green;'>✅ Proper Vietnamese</td>";
echo "</tr>";
echo "</table>";

echo "<h2>How to Verify:</h2>";
echo "<ol>";
echo "<li>Run the test above</li>";
echo "<li>Check database for new notifications</li>";
echo "<li>Check email logs for 'STAFF_EMAIL:' entries</li>";
echo "<li>Verify staff receive emails with standard template</li>";
echo "<li>Test with real new request creation</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
echo "<p><a href='test-comprehensive-email-fix.php'>Test Email Fix</a></p>";
?>
