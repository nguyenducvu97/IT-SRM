<?php
session_start();
require_once 'config/database.php';
require_once 'config/session.php';

echo "<h1>Complete Notification Flow Test - Staff Accept Request</h1>";

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    die("Please login first");
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'] ?? 'user';
$user_name = $_SESSION['full_name'] ?? 'Unknown';

echo "<p><strong>Current User:</strong> $user_name (ID: $user_id, Role: $user_role)</p>";

if ($user_role !== 'staff' && $user_role !== 'admin') {
    die("<p style='color: red;'>Access denied: Only staff and admin can test</p>");
}

try {
    $db = (new Database())->getConnection();
    
    // Step 1: Create a test request if none exists
    echo "<h2>Step 1: Creating Test Request</h2>";
    
    $check_query = "SELECT COUNT(*) as count FROM service_requests 
                    WHERE status = 'open' AND (assigned_to IS NULL OR assigned_to = 0)";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute();
    $open_count = $check_stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($open_count == 0) {
        // Find a regular user to create request for
        $user_query = "SELECT id, full_name, email FROM users WHERE role = 'user' LIMIT 1";
        $user_stmt = $db->prepare($user_query);
        $user_stmt->execute();
        $test_user = $user_stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$test_user) {
            die("<p style='color: red;'>No regular user found to create test request</p>");
        }
        
        $insert_query = "INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at, updated_at) 
                         VALUES (:user_id, :title, :description, :category_id, :priority, 'open', NOW(), NOW())";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([
            ':user_id' => $test_user['id'],
            ':title' => 'Test Request for Notification Flow',
            ':description' => 'This is a test request to verify notification functionality when staff accepts request',
            ':category_id' => 1,
            ':priority' => 'medium'
        ]);
        
        $request_id = $db->lastInsertId();
        echo "<p style='color: green;'>Created test request #$request_id for {$test_user['full_name']}</p>";
    } else {
        // Get an existing open request
        $get_query = "SELECT id, user_id, title FROM service_requests 
                      WHERE status = 'open' AND (assigned_to IS NULL OR assigned_to = 0) 
                      ORDER BY created_at DESC LIMIT 1";
        $get_stmt = $db->prepare($get_query);
        $get_stmt->execute();
        $request = $get_stmt->fetch(PDO::FETCH_ASSOC);
        $request_id = $request['id'];
        echo "<p style='color: blue;'>Using existing request #$request_id</p>";
    }
    
    // Step 2: Get request details
    echo "<h2>Step 2: Request Details</h2>";
    $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email 
                      FROM service_requests sr
                      LEFT JOIN users u ON sr.user_id = u.id
                      WHERE sr.id = :request_id";
    $request_stmt = $db->prepare($request_query);
    $request_stmt->bindParam(":request_id", $request_id);
    $request_stmt->execute();
    $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$request_data) {
        die("<p style='color: red;'>Request not found</p>");
    }
    
    echo "<div style='background-color: #e8f5e8; padding: 10px; margin: 10px 0; border-radius: 5px;'>";
    echo "<p><strong>Request ID:</strong> {$request_data['id']}</p>";
    echo "<p><strong>Title:</strong> " . htmlspecialchars($request_data['title']) . "</p>";
    echo "<p><strong>Requester:</strong> {$request_data['requester_name']} ({$request_data['requester_email']})</p>";
    echo "<p><strong>Status:</strong> {$request_data['status']}</p>";
    echo "</div>";
    
    // Step 3: Simulate API call to accept request
    echo "<h2>Step 3: Simulating Staff Accept Request</h2>";
    
    // Update request (simulate API accept_request action)
    $update_query = "UPDATE service_requests 
                    SET assigned_to = :user_id, status = 'in_progress', 
                        assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
                    WHERE id = :request_id";
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(":request_id", $request_id);
    $update_stmt->bindParam(":user_id", $user_id);
    
    if ($update_stmt->execute()) {
        echo "<p style='color: green;'>Request updated successfully!</p>";
        
        // Step 4: Test notifications
        echo "<h2>Step 4: Testing Notifications</h2>";
        
        try {
            require_once 'lib/ServiceRequestNotificationHelper.php';
            $notificationHelper = new ServiceRequestNotificationHelper();
            
            // Test user notification
            echo "<h3>4.1 User Notification</h3>";
            $userNotifResult = $notificationHelper->notifyUserRequestInProgress(
                $request_id, 
                $request_data['user_id'], 
                $user_name
            );
            echo "<p>User notification result: " . ($userNotifResult ? "SUCCESS" : "FAILED") . "</p>";
            
            // Test admin notification
            echo "<h3>4.2 Admin Notification</h3>";
            $adminNotifResult = $notificationHelper->notifyAdminStatusChange(
                $request_id, 
                'open', 
                'in_progress', 
                $user_name, 
                $request_data['title']
            );
            echo "<p>Admin notification result: " . ($adminNotifResult ? "SUCCESS" : "FAILED") . "</p>";
            
            // Step 5: Verify notifications in database
            echo "<h2>Step 5: Verify Notifications in Database</h2>";
            
            $notif_query = "SELECT n.*, u.full_name as user_name, u.role 
                            FROM notifications n
                            LEFT JOIN users u ON n.user_id = u.id
                            WHERE n.related_id = :request_id AND n.related_type = 'service_request'
                            ORDER BY n.created_at DESC";
            $notif_stmt = $db->prepare($notif_query);
            $notif_stmt->bindParam(":request_id", $request_id);
            $notif_stmt->execute();
            $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Found " . count($notifications) . " notifications:</h3>";
            
            if (!empty($notifications)) {
                echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin: 10px 0;'>";
                echo "<tr style='background-color: #f0f0f0;'>
                        <th>ID</th>
                        <th>User</th>
                        <th>Role</th>
                        <th>Title</th>
                        <th>Message</th>
                        <th>Type</th>
                        <th>Created</th>
                        <th>Read</th>
                      </tr>";
                
                foreach ($notifications as $notif) {
                    echo "<tr>";
                    echo "<td>{$notif['id']}</td>";
                    echo "<td>{$notif['user_name']}</td>";
                    echo "<td>{$notif['role']}</td>";
                    echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                    echo "<td>{$notif['type']}</td>";
                    echo "<td>{$notif['created_at']}</td>";
                    echo "<td>" . ($notif['is_read'] ? 'Yes' : 'No') . "</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: red;'>No notifications found in database!</p>";
            }
            
            // Step 6: Test email functionality
            echo "<h2>Step 6: Testing Email Functionality</h2>";
            
            try {
                require_once 'lib/EmailHelper.php';
                $emailHelper = new EmailHelper();
                
                $subject = "Test: Yêu câu #{$request_id} - Tràng thái thay thành 'in_progress'";
                $body = "Chào {$request_data['requester_name']},\n\n";
                $body .= "Yêu câu #{$request_id} ('{$request_data['title']}') cua ban da duoc nhan boi nhân viên IT.\n\n";
                $body .= "Nhân viên phu trách: {$user_name}\n\n";
                $body .= "Trang thái: in_progress\n\n";
                $body .= "Ban có the xem chi tiêt tai: http://localhost/it-service-request/request-detail.html?id={$request_id}\n\n";
                $body .= "Trân tr,\n";
                $body .= "IT Service Request System";
                
                $emailResult = $emailHelper->sendEmail(
                    $request_data['requester_email'],
                    $request_data['requester_name'],
                    $subject,
                    $body
                );
                
                echo "<p>Email result: " . ($emailResult ? "SUCCESS" : "FAILED") . "</p>";
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>Email test failed: " . $e->getMessage() . "</p>";
            }
            
            // Step 7: Check notification system status
            echo "<h2>Step 7: System Status Check</h2>";
            
            // Check admin users
            $admin_query = "SELECT id, full_name, email FROM users WHERE role = 'admin'";
            $admin_stmt = $db->prepare($admin_query);
            $admin_stmt->execute();
            $admins = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<h3>Admin Users (should receive notifications):</h3>";
            foreach ($admins as $admin) {
                echo "<p>- {$admin['full_name']} (ID: {$admin['id']}, Email: {$admin['email']})</p>";
            }
            
            // Check notification helper status
            echo "<h3>Notification Helper Status:</h3>";
            echo "<p>- ServiceRequestNotificationHelper: Loaded</p>";
            echo "<p>- EmailHelper: Loaded</p>";
            echo "<p>- Database Connection: Active</p>";
            
            // Summary
            echo "<h2>Test Summary</h2>";
            echo "<div style='background-color: #f0f8ff; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #007bff;'>";
            echo "<h3>Results:</h3>";
            echo "<p>1. Request Creation: " . ($request_id ? "SUCCESS" : "FAILED") . "</p>";
            echo "<p>2. Request Accept: SUCCESS</p>";
            echo "<p>3. User Notification: " . ($userNotifResult ? "SUCCESS" : "FAILED") . "</p>";
            echo "<p>4. Admin Notification: " . ($adminNotifResult ? "SUCCESS" : "FAILED") . "</p>";
            echo "<p>5. Email Notification: " . ($emailResult ?? "NOT TESTED") . "</p>";
            echo "<p>6. Database Records: " . (count($notifications) > 0 ? "SUCCESS" : "FAILED") . "</p>";
            echo "</div>";
            
            echo "<h3>Next Steps:</h3>";
            echo "<ol>";
            echo "<li>Check the notification dropdown in the main application</li>";
            echo "<li>Verify that admin users can see the status change notification</li>";
            echo "<li>Verify that the requester can see the 'in progress' notification</li>";
            echo "<li>Check email logs to confirm email delivery</li>";
            echo "<li>Test the real-time notification refresh functionality</li>";
            echo "</ol>";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Notification test failed: " . $e->getMessage() . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Failed to update request</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
echo "<p><a href='request-detail.html?id=$request_id'>View Request Detail</a></p>";
echo "<p><a href='test-notifications-debug-accept.php'>Run Debug Test Again</a></p>";
?>
