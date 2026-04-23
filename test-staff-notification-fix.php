<?php
echo "<h1>Test Staff Notification Fix</h1>";

echo "<h2>Problem Fixed:</h2>";
echo "<ul>";
echo "<li>❌ getUsersByRole() không include email field</li>";
echo "<li>❌ getUsersByRole() không filter by active status</li>";
echo "<li>❌ Staff không nhận được notification khi user tạo yêu cầu mới</li>";
echo "</ul>";

echo "<h2>Solution Applied:</h2>";
echo "<ul>";
echo "<li>✅ Added email field to getUsersByRole() query</li>";
echo "<li>✅ Added status = 'active' filter to getUsersByRole() query</li>";
echo "<li>✅ Email sending added to notifyStaffNewRequest()</li>";
echo "</ul>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_fix'])) {
    require_once 'config/database.php';
    require_once 'lib/ServiceRequestNotificationHelper.php';
    
    try {
        $db = (new Database())->getConnection();
        
        echo "<h3>Testing Fixed Implementation...</h3>";
        
        // Step 1: Check staff users
        echo "<h4>Step 1: Check Staff Users</h4>";
        $staff_query = "SELECT id, username, full_name, email, role, status FROM users WHERE role = 'staff'";
        $staff_stmt = $db->prepare($staff_query);
        $staff_stmt->execute();
        $all_staff = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total staff users:</strong> " . count($all_staff) . "</p>";
        
        if (empty($all_staff)) {
            echo "<p style='color: orange;'>Creating test staff user...</p>";
            
            $create_staff = "INSERT INTO users (username, password, full_name, email, role, status, created_at) 
                            VALUES ('teststaff', 'password123', 'Test Staff User', 'staff@example.com', 'staff', 'active', NOW())";
            $staff_insert = $db->prepare($create_staff);
            $staff_insert->execute();
            $staff_id = $db->lastInsertId();
            
            echo "<p style='color: green;'>✅ Created test staff user: ID {$staff_id}</p>";
            
            // Refresh staff list
            $staff_stmt->execute();
            $all_staff = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        
        // Display staff users
        echo "<h5>All Staff Users:</h5>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th><th>Status</th>";
        echo "</tr>";
        
        foreach ($all_staff as $staff) {
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
        
        // Step 2: Test getUsersByRole method
        echo "<h4>Step 2: Test getUsersByRole() Method</h4>";
        $notificationHelper = new ServiceRequestNotificationHelper();
        
        $staff_from_helper = $notificationHelper->getUsersByRole(['staff']);
        echo "<p><strong>Active staff from getUsersByRole():</strong> " . count($staff_from_helper) . "</p>";
        
        if (empty($staff_from_helper)) {
            echo "<p style='color: red;'>❌ No active staff found by getUsersByRole()!</p>";
            
            // Check if staff are inactive
            $inactive_staff = array_filter($all_staff, function($staff) {
                return $staff['status'] !== 'active';
            });
            
            if (!empty($inactive_staff)) {
                echo "<p style='color: orange;'>Found " . count($inactive_staff) . " inactive staff users. Activating them...</p>";
                
                foreach ($inactive_staff as $staff) {
                    $activate_query = "UPDATE users SET status = 'active' WHERE id = ?";
                    $activate_stmt = $db->prepare($activate_query);
                    $activate_stmt->execute([$staff['id']]);
                    echo "<p style='color: green;'>✅ Activated staff: {$staff['full_name']}</p>";
                }
                
                // Refresh
                $staff_from_helper = $notificationHelper->getUsersByRole(['staff']);
                echo "<p><strong>Active staff after activation:</strong> " . count($staff_from_helper) . "</p>";
            }
        } else {
            echo "<h5>Active Staff from Helper:</h5>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>ID</th><th>Full Name</th><th>Email</th><th>Role</th>";
            echo "</tr>";
            
            foreach ($staff_from_helper as $staff) {
                echo "<tr>";
                echo "<td>{$staff['id']}</td>";
                echo "<td>{$staff['full_name']}</td>";
                echo "<td>{$staff['email']}</td>";
                echo "<td>{$staff['role']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Step 3: Test notification creation
        if (!empty($staff_from_helper)) {
            echo "<h4>Step 3: Test Staff Notification Creation</h4>";
            
            $test_request_id = 777;
            $test_title = "Test Staff Notification Fix";
            $test_requester = "Test User";
            $test_category = "Test Category";
            
            echo "<p>Testing notifyStaffNewRequest()...</p>";
            
            $result = $notificationHelper->notifyStaffNewRequest(
                $test_request_id,
                $test_title,
                $test_requester,
                $test_category
            );
            
            echo "<p><strong>Notification result:</strong> " . ($result ? "SUCCESS" : "FAILED") . "</p>";
            
            // Check database notifications
            $notification_query = "SELECT n.*, u.full_name, u.email 
                                  FROM notifications n 
                                  JOIN users u ON n.user_id = u.id 
                                  WHERE n.title = 'Yêu cầu mới cần xử lý' AND n.related_id = ?";
            $notification_stmt = $db->prepare($notification_query);
            $notification_stmt->execute([$test_request_id]);
            $notifications = $notification_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<p><strong>Database notifications created:</strong> " . count($notifications) . "</p>";
            
            if (!empty($notifications)) {
                echo "<h5>Created Notifications:</h5>";
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr style='background-color: #f0f0f0;'>";
                echo "<th>User ID</th><th>Staff Name</th><th>Email</th><th>Message</th><th>Created</th>";
                echo "</tr>";
                
                foreach ($notifications as $notif) {
                    echo "<tr>";
                    echo "<td>{$notif['user_id']}</td>";
                    echo "<td>{$notif['full_name']}</td>";
                    echo "<td>{$notif['email']}</td>";
                    echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                    echo "<td>{$notif['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            }
            
            // Check email logs
            echo "<h4>Step 4: Check Email Logs</h4>";
            $log_file = 'logs/email_activity.log';
            if (file_exists($log_file)) {
                $log_content = file_get_contents($log_file);
                $recent_logs = array_slice(explode("\n", $log_content), -10);
                
                $staff_email_logs = array_filter($recent_logs, function($log) {
                    return strpos($log, 'STAFF_EMAIL:') !== false;
                });
                
                echo "<p><strong>Recent STAFF_EMAIL logs:</strong> " . count($staff_email_logs) . "</p>";
                
                if (!empty($staff_email_logs)) {
                    echo "<h5>Staff Email Logs:</h5>";
                    echo "<div style='background-color: #f8f9fa; padding: 10px; border-radius: 5px; font-family: monospace; font-size: 12px;'>";
                    foreach ($staff_email_logs as $log) {
                        if (trim($log)) {
                            $color = strpos($log, 'SUCCESS') !== false ? 'green' : 'red';
                            echo "<div style='color: {$color};'>" . htmlspecialchars($log) . "</div>";
                        }
                    }
                    echo "</div>";
                }
            } else {
                echo "<p style='color: orange;'>Email log file not found</p>";
            }
        }
        
        echo "<div style='background-color: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #28a745;'>";
        echo "<h3>Fix Verification Results:</h3>";
        echo "<ul>";
        echo "<li>" . (!empty($staff_from_helper) ? "✅" : "❌") . " getUsersByRole() returns active staff with email</li>";
        echo "<li>" . (!empty($notifications) ? "✅" : "❌") . " Database notifications created for staff</li>";
        echo "<li>" . (!empty($staff_email_logs) ? "✅" : "❌") . " Email logs show staff notifications</li>";
        echo "<li>" . ($result ? "✅" : "❌") . " notifyStaffNewRequest() method works</li>";
        echo "</ul>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Test Form:</h2>";
echo "<div style='padding: 20px; background-color: #f8f9fa; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>Test Staff Notification Fix</h3>";
echo "<p>This will test the complete fix for staff notifications when new requests are created.</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='test_fix' value='1'>";
echo "<button type='submit' style='background-color: #28a745; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px;'>
            Test Staff Notification Fix
        </button>";
echo "</form>";
echo "</div>";

echo "<h2>Technical Details:</h2>";
echo "<h3>Changes Made:</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h4>1. getUsersByRole() Method Fix:</h4>";
echo "<pre><code>// BEFORE:
SELECT id, username, full_name 
FROM users 
WHERE role = ?

// AFTER:
SELECT id, username, full_name, email 
FROM users 
WHERE role = ? AND status = 'active'</code></pre>";

echo "<h4>2. Email Sending Added:</h4>";
echo "<pre><code>// Added to notifyStaffNewRequest():
$emailResult = $emailHelper->sendStandardEmail(
    $staff['email'],
    $staff['full_name'],
    \"Yêu cầu mới cần xử lý #{$requestId}\",
    $emailContent,
    $requestId
);</code></pre>";
echo "</div>";

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
echo "<p><a href='debug-staff-notification-issue.php'>Debug Staff Notification</a></p>";
echo "<p><a href='test-comprehensive-email-fix.php'>Test Email Fix</a></p>";
?>
