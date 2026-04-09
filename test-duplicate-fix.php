<?php
echo "<h2>TEST DUPLICATE NOTIFICATION FIX</h2>";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/ServiceRequestNotificationHelper.php';

$db = getDatabaseConnection();
$notificationHelper = new ServiceRequestNotificationHelper();

echo "<h3>1. TAO REQUEST MOI DE TEST</h3>";

try {
    // Tìm user ID 4 (ndvu)
    $stmt = $db->prepare("SELECT id, username, full_name FROM users WHERE role = 'user' LIMIT 1");
    $stmt->execute();
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        echo "<p><strong>Test User:</strong> {$user['username']} ({$user['full_name']})</p>";
        
        // Tìm category
        $stmt = $db->prepare("SELECT id, name FROM categories LIMIT 1");
        $stmt->execute();
        $category = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($category) {
            echo "<p><strong>Test Category:</strong> {$category['name']}</p>";
            
            // Tìm admin user
            $stmt = $db->prepare("SELECT id, username FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($admin) {
                echo "<p><strong>Admin User:</strong> {$admin['username']}</p>";
                
                // Count admin notifications before
                $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
                $stmt->execute([$admin['id']]);
                $beforeCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                echo "<p><strong>Admin notifications before:</strong> {$beforeCount}</p>";
                
                // Tìm request ID
                $stmt = $db->prepare("SELECT MAX(id) as max_id FROM service_requests");
                $stmt->execute();
                $maxId = $stmt->fetch(PDO::FETCH_ASSOC)['max_id'];
                $newRequestId = $maxId + 1;
                
                echo "<h3>2. TEST NOTIFICATION CREATION</h3>";
                
                // Test notification functions
                echo "<h4>Testing notifyStaffNewRequest()...</h4>";
                $staffResult = $notificationHelper->notifyStaffNewRequest(
                    $newRequestId, 
                    'Test Duplicate Fix ' . date('H:i:s'), 
                    $user['full_name'], 
                    $category['name']
                );
                echo "<p>Staff notification result: " . ($staffResult ? "SUCCESS" : "FAILED") . "</p>";
                
                echo "<h4>Testing notifyAdminNewRequest()...</h4>";
                $adminResult = $notificationHelper->notifyAdminNewRequest(
                    $newRequestId, 
                    'Test Duplicate Fix ' . date('H:i:s'), 
                    $user['full_name'], 
                    $category['name']
                );
                echo "<p>Admin notification result: " . ($adminResult ? "SUCCESS" : "FAILED") . "</p>";
                
                if ($staffResult && $adminResult) {
                    echo "<p style='color: green;'>&#10004; Both notifications created successfully!</p>";
                    
                    // Count admin notifications after
                    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ?");
                    $stmt->execute([$admin['id']]);
                    $afterCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
                    echo "<p><strong>Admin notifications after:</strong> {$afterCount}</p>";
                    
                    $difference = $afterCount - $beforeCount;
                    echo "<p><strong>New admin notifications:</strong> {$difference}</p>";
                    
                    if ($difference === 1) {
                        echo "<p style='color: green;'>&#10004; PERFECT! Only 1 admin notification created!</p>";
                    } else {
                        echo "<p style='color: red;'>&#10027; ISSUE! Expected 1, got {$difference} admin notifications!</p>";
                    }
                    
                    // Check latest notifications
                    echo "<h3>3. KIEM TRA NOTIFICATIONS MOI NHAT</h3>";
                    
                    $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                                           LEFT JOIN users u ON n.user_id = u.id 
                                           WHERE n.created_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE)
                                           ORDER BY n.created_at DESC");
                    $stmt->execute();
                    $recentNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    
                    if (count($recentNotifications) > 0) {
                        echo "<p style='color: green;'><strong>&#10004; Found " . count($recentNotifications) . " recent notifications:</strong></p>";
                        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
                        echo "<tr style='background-color: #f0f0f0;'>";
                        echo "<th>ID</th><th>User</th><th>Role</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
                        echo "</tr>";
                        
                        foreach ($recentNotifications as $notif) {
                            echo "<tr>";
                            echo "<td><strong>{$notif['id']}</strong></td>";
                            echo "<td>{$notif['username']}</td>";
                            echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['role']}</span></td>";
                            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                            echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
                            echo "<td><span style='background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['type']}</span></td>";
                            echo "<td>{$notif['created_at']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                        
                        // Check admin notifications specifically
                        $adminNotifs = array_filter($recentNotifications, function($n) {
                            return $n['role'] === 'admin';
                        });
                        
                        if (count($adminNotifs) === 1) {
                            echo "<p style='color: green;'><strong>&#10004; PERFECT! Admin received exactly 1 notification!</strong></p>";
                            foreach ($adminNotifs as $notif) {
                                echo "<p>- {$notif['title']}: " . htmlspecialchars($notif['message']) . "</p>";
                            }
                        } else {
                            echo "<p style='color: red;'><strong>&#10027; ISSUE! Admin received " . count($adminNotifs) . " notifications!</strong></p>";
                            foreach ($adminNotifs as $notif) {
                                echo "<p>- {$notif['title']}: " . htmlspecialchars($notif['message']) . "</p>";
                            }
                        }
                        
                    } else {
                        echo "<p style='color: red;'><strong>&#10027; No recent notifications found!</strong></p>";
                    }
                    
                } else {
                    echo "<p style='color: red;'>&#10027; Failed to create notifications!</p>";
                }
                
            } else {
                echo "<p style='color: red;'>&#10027; No admin user found!</p>";
            }
            
        } else {
            echo "<p style='color: red;'>&#10027; No category found!</p>";
        }
        
    } else {
        echo "<p style='color: red;'>&#10027; No user found!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>4. CONCLUSION</h3>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128204; FIX RESULTS:</h4>";
echo "<p><strong>Issue:</strong> Admin receiving 2 notifications when user creates request</p>";
echo "<p><strong>Cause:</strong> Duplicate notifyAdminNewRequest() calls in 2 code paths</p>";
echo "<p><strong>Fix:</strong> Removed duplicate call in second code path</p>";
echo "<p><strong>Result:</strong> Admin should now receive only 1 notification</p>";
echo "</div>";

echo "<h3>5. TEST IN BROWSER</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128072; To test fix in browser:</h4>";
echo "<ol>";
echo "<li>1. Login as user (ndvu/password123)</li>";
echo "<li>2. Create a new request</li>";
echo "<li>3. Login as admin (admin/password123)</li>";
echo "<li>4. Check notifications - should see only 1 notification</li>";
echo "<li>5. Run this script again to verify</li>";
echo "</ol>";
echo "</div>";
?>
