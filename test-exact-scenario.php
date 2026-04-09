<?php
echo "<h2>TEST CHÍNH XÁC SCENARIO STAFF NHẬN YÊU CẦU</h2>";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/ServiceRequestNotificationHelper.php';

$db = getDatabaseConnection();
$notificationHelper = new ServiceRequestNotificationHelper();

// Tìm request #78
$stmt = $db->prepare("SELECT * FROM service_requests WHERE id = 78");
$stmt->execute();
$request78 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($request78) {
    echo "<h3>Request #78 Info:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>User ID</th><th>Assigned To</th></tr>";
    echo "<tr>";
    echo "<td>{$request78['id']}</td>";
    echo "<td>" . htmlspecialchars($request78['title']) . "</td>";
    echo "<td>{$request78['status']}</td>";
    echo "<td>{$request78['user_id']}</td>";
    echo "<td>{$request78['assigned_to']}</td>";
    echo "</tr>";
    echo "</table>";
    
    echo "<h3>Test Exact Accept Request Logic:</h3>";
    
    // Simulate staff1 nhận request #78
    $staff_id = 2; // staff1
    $staff_name = 'John Smith';
    $request_id = 78;
    
    echo "<p><strong>Staff ID:</strong> {$staff_id} (staff1)</p>";
    echo "<p><strong>Request ID:</strong> {$request_id}</p>";
    echo "<p><strong>User ID:</strong> {$request78['user_id']}</p>";
    
    try {
        // Step 1: Update request status
        $update_query = "UPDATE service_requests 
                       SET assigned_to = ?, status = 'in_progress', updated_at = NOW() 
                       WHERE id = ?";
        
        $update_stmt = $db->prepare($update_query);
        $result = $update_stmt->execute([$staff_id, $request_id]);
        
        if ($result) {
            echo "<p style='color: green;'>&#10004; Request updated successfully!</p>";
            
            // Step 2: Get request details for notifications
            $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 
                                             staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name
                                      FROM service_requests sr
                                      LEFT JOIN users u ON sr.user_id = u.id
                                      LEFT JOIN users staff ON sr.assigned_to = staff.id
                                      LEFT JOIN categories c ON sr.category_id = c.id
                                      WHERE sr.id = ?";
            
            $request_stmt = $db->prepare($request_query);
            $request_stmt->execute([$request_id]);
            $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($request_data) {
                echo "<p style='color: green;'>&#10004; Request data found</p>";
                
                // Step 3: Test notifications EXACTLY like API
                echo "<h4>Testing Notifications:</h4>";
                
                // 3.1 Notify user that request is in progress
                echo "<p><strong>1. notifyUserRequestInProgress():</strong></p>";
                $result1 = $notificationHelper->notifyUserRequestInProgress(
                    $request_id, 
                    $request_data['user_id'], 
                    $request_data['assigned_name']
                );
                echo "<p>Result: " . ($result1 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
                
                // 3.2 Notify admins about assignment
                echo "<p><strong>2. notifyAdminStatusChange():</strong></p>";
                $result2 = $notificationHelper->notifyAdminStatusChange(
                    $request_id, 
                    'open', 
                    'in_progress', 
                    $request_data['assigned_name'], 
                    $request_data['title']
                );
                echo "<p>Result: " . ($result2 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
                
                // 3.3 Notify other staff (excluding the assigned staff)
                echo "<p><strong>3. notifyStaffAdminApproved():</strong></p>";
                $result3 = $notificationHelper->notifyStaffAdminApproved(
                    $request_id, 
                    $request_data['title'], 
                    $request_data['assigned_name']
                );
                echo "<p>Result: " . ($result3 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
                
                // Step 4: Check notifications created
                echo "<h4>Check Notifications Created:</h4>";
                
                $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                                       LEFT JOIN users u ON n.user_id = u.id 
                                       WHERE n.related_id = ? AND n.related_type = 'service_request'
                                       ORDER BY n.created_at DESC");
                $stmt->execute([$request_id]);
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                if (count($notifications) > 0) {
                    echo "<p style='color: green;'><strong>&#10004; Found " . count($notifications) . " notifications for request #{$request_id}:</strong></p>";
                    echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
                    echo "<tr style='background-color: #f0f0f0;'>";
                    echo "<th>ID</th><th>User</th><th>Role</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
                    echo "</tr>";
                    
                    foreach ($notifications as $notif) {
                        echo "<tr>";
                        echo "<td><strong>{$notif['id']}</strong></td>";
                        echo "<td>{$notif['username']}</td>";
                        echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['role']}</span></td>";
                        echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                        echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                        echo "<td><span style='background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['type']}</span></td>";
                        echo "<td>{$notif['created_at']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    
                    // Step 5: Verify expected notifications
                    echo "<h4>Verify Expected Notifications:</h4>";
                    
                    $expectedNotifications = [
                        'user' => [
                            'title' => 'Yêu cầu đang được xử lý',
                            'description' => 'User should receive notification when staff accepts request'
                        ],
                        'admin' => [
                            'title' => 'Thay đổi trạng thái yêu cầu',
                            'description' => 'Admin should receive notification when staff changes status'
                        ],
                        'staff' => [
                            'title' => 'Yêu cầu được Admin phê duyệt',
                            'description' => 'Other staff should receive notification when request is assigned'
                        ]
                    ];
                    
                    foreach ($expectedNotifications as $role => $expected) {
                        $found = false;
                        foreach ($notifications as $notif) {
                            if ($notif['role'] === $role && strpos($notif['title'], $expected['title']) !== false) {
                                $found = true;
                                break;
                            }
                        }
                        
                        if ($found) {
                            echo "<p style='color: green;'>&#10004; {$role}: {$expected['title']} - <strong>FOUND</strong></p>";
                            echo "<p><em>{$expected['description']}</em></p>";
                        } else {
                            echo "<p style='color: red;'>&#10027; {$role}: {$expected['title']} - <strong>MISSING</strong></p>";
                            echo "<p><em>{$expected['description']}</em></p>";
                        }
                    }
                    
                } else {
                    echo "<p style='color: red;'><strong>&#10027; No notifications found for request #{$request_id}!</strong></p>";
                }
                
            } else {
                echo "<p style='color: red;'>&#10027; Request data not found</p>";
            }
            
        } else {
            echo "<p style='color: red;'>&#10027; Failed to update request</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>&#10027; Request #78 not found!</p>";
}

echo "<h3>CONCLUSION</h3>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128204; Test Results:</h4>";
echo "<p>Nếu tất cả 3 notifications đều SUCCESS và FOUND:</p>";
echo "<ul>";
echo "<li>&#10004; Notification functions hoạt động đúng</li>";
echo "<li>&#10004; Database lưu notifications đúng</li>";
echo "<li>&#10004; Logic hoàn hảo</li>";
echo "</ul>";
echo "<p>Vấn đề có thể là:</p>";
echo "<ul>";
echo "<li>1. Browser không fetch notifications từ API</li>";
echo "<li>2. Frontend không hiển thị notifications</li>";
echo "<li>3. User không login đúng cách</li>";
echo "<li>4. Cache issue</li>";
echo "</ul>";
echo "</div>";
?>
