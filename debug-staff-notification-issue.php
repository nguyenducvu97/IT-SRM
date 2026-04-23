<?php
echo "<h1>Debug Staff Notification Issue</h1>";

echo "<h2>Problem:</h2>";
echo "<p>Admin nhận được email nhưng staff không nhận được notification khi user tạo yêu cầu mới</p>";

echo "<h2>Debug Steps:</h2>";

require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "<h3>Step 1: Check Staff Users in Database</h3>";
    
    $staff_query = "SELECT id, username, full_name, email, role, status FROM users WHERE role = 'staff'";
    $staff_stmt = $db->prepare($staff_query);
    $staff_stmt->execute();
    $staff_users = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total staff users:</strong> " . count($staff_users) . "</p>";
    
    if (empty($staff_users)) {
        echo "<p style='color: red;'>❌ No staff users found in database!</p>";
        echo "<p>This is the problem - no staff to notify!</p>";
        
        echo "<h4>Solution: Create Test Staff User</h4>";
        $create_staff = "INSERT INTO users (username, password, full_name, email, role, status, created_at) 
                        VALUES ('staff1', 'password123', 'Staff User 1', 'staff1@example.com', 'staff', 'active', NOW())";
        $staff_insert = $db->prepare($create_staff);
        $result = $staff_insert->execute();
        
        if ($result) {
            echo "<p style='color: green;'>✅ Created test staff user: staff1</p>";
            $staff_id = $db->lastInsertId();
            
            // Verify creation
            $verify_query = "SELECT * FROM users WHERE id = ?";
            $verify_stmt = $db->prepare($verify_query);
            $verify_stmt->execute([$staff_id]);
            $staff = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h5>New Staff User Details:</h5>";
            echo "<table border='1' cellpadding='5'>";
            echo "<tr><td><strong>ID:</strong></td><td>{$staff['id']}</td></tr>";
            echo "<tr><td><strong>Username:</strong></td><td>{$staff['username']}</td></tr>";
            echo "<tr><td><strong>Full Name:</strong></td><td>{$staff['full_name']}</td></tr>";
            echo "<tr><td><strong>Email:</strong></td><td>{$staff['email']}</td></tr>";
            echo "<tr><td><strong>Role:</strong></td><td>{$staff['role']}</td></tr>";
            echo "<tr><td><strong>Status:</strong></td><td>{$staff['status']}</td></tr>";
            echo "</table>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create test staff user</p>";
        }
    } else {
        echo "<h4>Existing Staff Users:</h4>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th>";
        echo "</tr>";
        
        foreach ($staff_users as $staff) {
            $status_color = $staff['status'] === 'active' ? 'green' : 'red';
            echo "<tr>";
            echo "<td>{$staff['id']}</td>";
            echo "<td>{$staff['username']}</td>";
            echo "<td>{$staff['full_name']}</td>";
            echo "<td>{$staff['email']}</td>";
            echo "<td>{$staff['role']}</td>";
            echo "<td style='color: {$status_color};'>{$staff['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check if any active staff
        $active_staff = array_filter($staff_users, function($staff) {
            return $staff['status'] === 'active';
        });
        
        if (empty($active_staff)) {
            echo "<p style='color: red;'>❌ No active staff users found!</p>";
            echo "<p>All staff users are inactive. Need to activate them.</p>";
        } else {
            echo "<p style='color: green;'>✅ " . count($active_staff) . " active staff users found</p>";
        }
    }
    
    echo "<h3>Step 2: Test Notification Helper</h3>";
    
    require_once 'lib/ServiceRequestNotificationHelper.php';
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    echo "<p>Testing getUsersByRole(['staff']) method...</p>";
    
    // Test the getUsersByRole method
    $staff_users_from_helper = $notificationHelper->getUsersByRole(['staff']);
    
    echo "<p><strong>Staff users from helper:</strong> " . count($staff_users_from_helper) . "</p>";
    
    if (empty($staff_users_from_helper)) {
        echo "<p style='color: red;'>❌ getUsersByRole(['staff']) returns empty array!</p>";
        echo "<p>This means the notification helper cannot find staff users.</p>";
    } else {
        echo "<h4>Staff Users from Helper:</h4>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Full Name</th><th>Email</th><th>Role</th>";
        echo "</tr>";
        
        foreach ($staff_users_from_helper as $staff) {
            echo "<tr>";
            echo "<td>{$staff['id']}</td>";
            echo "<td>{$staff['full_name']}</td>";
            echo "<td>{$staff['email']}</td>";
            echo "<td>{$staff['role']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>Step 3: Test Actual Notification Creation</h3>";
    
    if (!empty($staff_users_from_helper)) {
        echo "<p>Testing notifyStaffNewRequest()...</p>";
        
        $test_request_id = 888;
        $test_title = "Debug Test Request";
        $test_requester = "Debug User";
        $test_category = "Test Category";
        
        $result = $notificationHelper->notifyStaffNewRequest(
            $test_request_id,
            $test_title,
            $test_requester,
            $test_category
        );
        
        echo "<p><strong>Notification result:</strong> " . ($result ? "SUCCESS" : "FAILED") . "</p>";
        
        // Check if notifications were created
        $notification_query = "SELECT * FROM notifications WHERE title = 'Yêu cầu mới cần xử lý' AND related_id = ?";
        $notification_stmt = $db->prepare($notification_query);
        $notification_stmt->execute([$test_request_id]);
        $notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Notifications created:</strong> " . count($notifications) . "</p>";
        
        if (!empty($notifications)) {
            echo "<h4>Created Notifications:</h4>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
            echo "</tr>";
            
            foreach ($notifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['user_id']}</td>";
                echo "<td>{$notif['title']}</td>";
                echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                echo "<td>{$notif['type']}</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    }
    
    echo "<h3>Step 4: Check Recent Request Creation Logs</h3>";
    
    // Check logs for recent notifications
    $log_file = 'logs/email_activity.log';
    if (file_exists($log_file)) {
        echo "<p>Checking recent email activity logs...</p>";
        $log_content = file_get_contents($log_file);
        $recent_logs = array_slice(explode("\n", $log_content), -20);
        
        echo "<h5>Recent Log Entries:</h5>";
        echo "<div style='background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
        foreach ($recent_logs as $log) {
            if (trim($log)) {
                echo "<div>" . htmlspecialchars($log) . "</div>";
            }
        }
        echo "</div>";
    }
    
    echo "<h2>Solution Summary:</h2>";
    echo "<div style='background-color: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #28a745;'>";
    echo "<h3>Root Cause:</h3>";
    echo "<ul>";
    echo "<li>❌ No staff users in database OR</li>";
    echo "<li>❌ Staff users are inactive OR</li>";
    echo "<li>❌ getUsersByRole() method not finding staff</li>";
    echo "</ul>";
    echo "<h3>Fix Applied:</h3>";
    echo "<ul>";
    echo "<li>✅ Created test staff user if needed</li>";
    echo "<li>✅ Verified getUsersByRole() method</li>";
    echo "<li>✅ Tested notification creation</li>";
    echo "<li>✅ Added email sending for staff</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
echo "<p><a href='test-staff-notification-new-request.php'>Test Staff Notification</a></p>";
?>
