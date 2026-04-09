<?php
echo "<h2>TEST STAFF NHẬN YÊU CẦU #79</h2>";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/ServiceRequestNotificationHelper.php';

$db = getDatabaseConnection();
$notificationHelper = new ServiceRequestNotificationHelper();

// Kiểm tra request #79
$stmt = $db->prepare("SELECT * FROM service_requests WHERE id = 79");
$stmt->execute();
$request79 = $stmt->fetch(PDO::FETCH_ASSOC);

if ($request79) {
    echo "<h3>Request #79 Info:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><td>{$request79['id']}</td></tr>";
    echo "<tr><th>Title</th><td>" . htmlspecialchars($request79['title']) . "</td></tr>";
    echo "<tr><th>Status</th><td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$request79['status']}</span></td></tr>";
    echo "<tr><th>User ID</th><td>{$request79['user_id']}</td></tr>";
    echo "<tr><th>Assigned To</th><td>{$request79['assigned_to']}</td></tr>";
    echo "</table>";
    
    if ($request79['status'] === 'open' && (!$request79['assigned_to'] || $request79['assigned_to'] == 0)) {
        echo "<h3>Simulate Staff1 Accept Request #79</h3>";
        
        $staff_id = 2; // staff1
        $staff_name = 'John Smith';
        
        try {
            // Step 1: Update request
            $update_query = "UPDATE service_requests 
                           SET assigned_to = ?, status = 'in_progress', updated_at = NOW() 
                           WHERE id = ?";
            
            $update_stmt = $db->prepare($update_query);
            $result = $update_stmt->execute([$staff_id, 79]);
            
            if ($result) {
                echo "<p style='color: green;'>&#10004; Request #79 updated successfully!</p>";
                
                // Step 2: Get request details
                $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 
                                                 staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name
                                          FROM service_requests sr
                                          LEFT JOIN users u ON sr.user_id = u.id
                                          LEFT JOIN users staff ON sr.assigned_to = staff.id
                                          LEFT JOIN categories c ON sr.category_id = c.id
                                          WHERE sr.id = ?";
                
                $request_stmt = $db->prepare($request_query);
                $request_stmt->execute([79]);
                $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($request_data) {
                    echo "<p style='color: green;'>&#10004; Request data found</p>";
                    echo "<table border='1' cellpadding='5'>";
                    echo "<tr><th>Request ID</th><td>{$request_data['id']}</td></tr>";
                    echo "<tr><th>Title</th><td>" . htmlspecialchars($request_data['title']) . "</td></tr>";
                    echo "<tr><th>User ID</th><td>{$request_data['user_id']} ({$request_data['requester_name']})</td></tr>";
                    echo "<tr><th>Assigned To</th><td>{$request_data['assigned_to']} ({$request_data['assigned_name']})</td></tr>";
                    echo "</table>";
                    
                    // Step 3: Send notifications
                    echo "<h4>Sending Notifications...</h4>";
                    
                    // 3.1 Notify user
                    echo "<p><strong>1. notifyUserRequestInProgress():</strong></p>";
                    $result1 = $notificationHelper->notifyUserRequestInProgress(
                        79, 
                        $request_data['user_id'], 
                        $request_data['assigned_name']
                    );
                    echo "<p>Result: " . ($result1 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
                    
                    // 3.2 Notify admins
                    echo "<p><strong>2. notifyAdminStatusChange():</strong></p>";
                    $result2 = $notificationHelper->notifyAdminStatusChange(
                        79, 
                        'open', 
                        'in_progress', 
                        $request_data['assigned_name'], 
                        $request_data['title']
                    );
                    echo "<p>Result: " . ($result2 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
                    
                    // 3.3 Notify other staff
                    echo "<p><strong>3. notifyStaffAdminApproved():</strong></p>";
                    $result3 = $notificationHelper->notifyStaffAdminApproved(
                        79, 
                        $request_data['title'], 
                        $request_data['assigned_name']
                    );
                    echo "<p>Result: " . ($result3 ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
                    
                    // Step 4: Check notifications
                    echo "<h4>Check Notifications Created:</h4>";
                    
                    $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                                           LEFT JOIN users u ON n.user_id = u.id 
                                           WHERE n.related_id = 79 AND n.related_type = 'service_request'
                                           ORDER BY n.created_at DESC");
                    $stmt->execute();
                    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($notifications) > 0) {
                        echo "<p style='color: green;'><strong>&#10004; Found " . count($notifications) . " notifications for request #79:</strong></p>";
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
                                'user_id' => 4, // ndvu
                                'description' => 'User (ndvu) should receive notification when staff accepts request'
                            ],
                            'admin' => [
                                'title' => 'Thay đổi trạng thái yêu cầu',
                                'user_id' => 1, // admin
                                'description' => 'Admin should receive notification when staff changes status'
                            ],
                            'staff' => [
                                'title' => 'Yêu cầu được Admin phê duyệt',
                                'user_id' => 3, // staff2
                                'description' => 'Other staff (staff2) should receive notification when request is assigned'
                            ]
                        ];
                        
                        foreach ($expectedNotifications as $role => $expected) {
                            $found = false;
                            foreach ($notifications as $notif) {
                                if ($notif['role'] === $role && strpos($notif['title'], $expected['title']) !== false) {
                                    $found = true;
                                    echo "<p style='color: green;'>&#10004; {$role} ({$notif['username']}): {$expected['title']} - <strong>FOUND</strong></p>";
                                    echo "<p><em>{$expected['description']}</em></p>";
                                    break;
                                }
                            }
                            
                            if (!$found) {
                                echo "<p style='color: red;'>&#10027; {$role}: {$expected['title']} - <strong>MISSING</strong></p>";
                                echo "<p><em>{$expected['description']}</em></p>";
                            }
                        }
                        
                    } else {
                        echo "<p style='color: red;'><strong>&#10027; No notifications found for request #79!</strong></p>";
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
        echo "<p style='color: orange;'>&#9888; Request #79 is not available for assignment</p>";
        echo "<p><strong>Current status:</strong> {$request79['status']}</p>";
        echo "<p><strong>Assigned to:</strong> {$request79['assigned_to']}</p>";
        
        if ($request79['status'] === 'in_progress') {
            echo "<p>Request đã được nhận rồi! Hãy kiểm tra notifications đã được tạo:</p>";
            
            $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                                   LEFT JOIN users u ON n.user_id = u.id 
                                   WHERE n.related_id = 79 AND n.related_type = 'service_request'
                                   ORDER BY n.created_at DESC");
            $stmt->execute();
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($notifications) > 0) {
                echo "<p style='color: green;'><strong>&#10004; Found " . count($notifications) . " notifications for request #79:</strong></p>";
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
            } else {
                echo "<p style='color: red;'><strong>&#10027; No notifications found for request #79!</strong></p>";
            }
        }
    }
    
} else {
    echo "<p style='color: red;'>&#10027; Request #79 not found!</p>";
}

echo "<h3>NEXT STEPS</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128072; Nếu test script này tạo notifications thành công:</h4>";
echo "<ol>";
echo "<li><strong>Database có notifications:</strong> Vấn đề ở browser/API call</li>";
echo "<li><strong>Test trong browser:</strong> Login staff1 → Nhận request #79</li>";
echo "<li><strong>Kiểm tra console:</strong> F12 → Console và Network tab</li>";
echo "<li><strong>Kiểm tra session:</strong> Đảm bảo staff login đúng</li>";
echo "</ol>";
echo "</div>";
?>
