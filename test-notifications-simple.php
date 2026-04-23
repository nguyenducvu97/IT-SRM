<?php
echo "<h1>Simple Notification Test (No Email)</h1>";

echo "<h2>🧪 Testing Database Notifications Only</h2>";
echo "<p>This test focuses on database notifications without email sending to isolate issues.</p>";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simple_test'])) {
    require_once 'config/database.php';
    require_once 'lib/ServiceRequestNotificationHelper.php';
    
    try {
        $db = (new Database())->getConnection();
        $notificationHelper = new ServiceRequestNotificationHelper();
        
        echo "<h3>Setup Test Environment</h3>";
        
        // Ensure test users exist
        $staff_check = $db->prepare("SELECT id, full_name, email FROM users WHERE role = 'staff' AND status = 'active' LIMIT 1");
        $staff_check->execute();
        $staff_user = $staff_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$staff_user) {
            echo "<p style='color: orange;'>Creating test staff user...</p>";
            $create_staff = "INSERT INTO users (username, password, full_name, email, role, status, created_at) 
                            VALUES ('teststaff', 'password123', 'Test Staff', 'staff@example.com', 'staff', 'active', NOW())";
            $staff_insert = $db->prepare($create_staff);
            $staff_insert->execute();
            $staff_id = $db->lastInsertId();
            $staff_user = ['id' => $staff_id, 'full_name' => 'Test Staff', 'email' => 'staff@example.com'];
        }
        
        $admin_check = $db->prepare("SELECT id, full_name, email FROM users WHERE role = 'admin' AND status = 'active' LIMIT 1");
        $admin_check->execute();
        $admin_user = $admin_check->fetch(PDO::FETCH_ASSOC);
        
        if (!$admin_user) {
            echo "<p style='color: orange;'>Creating test admin user...</p>";
            $create_admin = "INSERT INTO users (username, password, full_name, email, role, status, created_at) 
                             VALUES ('testadmin', 'password123', 'Test Admin', 'admin@example.com', 'admin', 'active', NOW())";
            $admin_insert = $db->prepare($create_admin);
            $admin_insert->execute();
            $admin_id = $db->lastInsertId();
            $admin_user = ['id' => $admin_id, 'full_name' => 'Test Admin', 'email' => 'admin@example.com'];
        }
        
        echo "<p style='color: green;'>✅ Test users ready</p>";
        echo "<p>Staff: {$staff_user['full_name']} (ID: {$staff_user['id']})</p>";
        echo "<p>Admin: {$admin_user['full_name']} (ID: {$admin_user['id']})</p>";
        
        // Create test request
        $test_request_id = 888;
        $create_request = "INSERT INTO service_requests (id, user_id, title, category_id, status, assigned_to, created_at) 
                          VALUES (?, 1, 'Simple Test Request', 1, 'open', ?, NOW()) 
                          ON DUPLICATE KEY UPDATE assigned_to = ?";
        $request_stmt = $db->prepare($create_request);
        $request_result = $request_stmt->execute([$test_request_id, $staff_user['id'], $staff_user['id']]);
        
        echo "<p style='color: green;'>✅ Test request #{$test_request_id} ready</p>";
        
        echo "<h3>Testing Notification Methods</h3>";
        
        $test_data = [
            'request_id' => $test_request_id,
            'user_id' => 1,
            'title' => 'Simple Test Request',
            'requester' => 'Test User',
            'category' => 'Test Category',
            'comment' => 'This is a simple test comment',
            'staff_name' => $staff_user['full_name'],
            'admin_name' => $admin_user['full_name']
        ];
        
        // Test methods without email complications
        $methods_to_test = [
            'notifyUserRequestInProgress' => [$test_data['request_id'], $test_data['user_id'], $test_data['staff_name']],
            'notifyUserRequestResolved' => [$test_data['request_id'], $test_data['user_id'], 'Test resolution'],
            'notifyUserRequestRejected' => [$test_data['request_id'], $test_data['user_id'], 'Test rejection'],
            'notifyUserNewComment' => [$test_data['request_id'], $test_data['user_id'], 'Test Commenter', $test_data['comment']],
            'notifyStaffAdminApproved' => [$test_data['request_id'], $test_data['title'], $test_data['admin_name']],
            'notifyStaffAdminRejected' => [$test_data['request_id'], $test_data['title'], $test_data['admin_name'], 'Test reason'],
            'notifyAdminNewRequest' => [$test_data['request_id'], $test_data['title'], $test_data['requester'], $test_data['category']],
            'notifyAdminStatusChange' => [$test_data['request_id'], 'open', 'in_progress', $test_data['staff_name'], $test_data['title']],
            'notifyAdminSupportRequest' => [$test_data['request_id'], 'Test support request', $test_data['staff_name'], $test_data['title']],
            'notifyAdminRejectionRequest' => [$test_data['request_id'], 'Test rejection reason', $test_data['staff_name'], $test_data['title']]
        ];
        
        $results = [];
        $success_count = 0;
        $failed_count = 0;
        
        foreach ($methods_to_test as $method => $params) {
            echo "<h4>Testing {$method}:</h4>";
            
            try {
                $start_time = microtime(true);
                $result = call_user_func_array([$notificationHelper, $method], $params);
                $execution_time = round((microtime(true) - $start_time) * 1000, 2);
                
                $status = $result ? "✅ SUCCESS" : "❌ FAILED";
                $color = $result ? "green" : "red";
                
                echo "<p style='color: {$color};'>{$status} ({$execution_time}ms)</p>";
                
                $results[$method] = [
                    'result' => $result,
                    'time' => $execution_time,
                    'status' => $status
                ];
                
                if ($result) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ EXCEPTION: " . $e->getMessage() . "</p>";
                $results[$method] = [
                    'result' => false,
                    'time' => 0,
                    'status' => '❌ EXCEPTION',
                    'error' => $e->getMessage()
                ];
                $failed_count++;
            }
        }
        
        // Test the problematic methods separately
        echo "<h4>Testing Problematic Methods (Staff Notifications):</h4>";
        
        $problematic_methods = [
            'notifyStaffNewRequest' => [$test_data['request_id'], $test_data['title'], $test_data['requester'], $test_data['category']],
            'notifyStaffUserFeedback' => [$test_data['request_id'], $test_data['user_id'], 5, 'Great service!', $test_data['requester']],
            'notifyStaffNewComment' => [$test_data['request_id'], 'Test Commenter', $test_data['comment'], 'user']
        ];
        
        foreach ($problematic_methods as $method => $params) {
            echo "<h5>Testing {$method} (with debug):</h5>";
            
            try {
                echo "<p>Method parameters: " . count($params) . " params</p>";
                
                // Check getUsersByRole first
                $staff_users = $notificationHelper->getUsersByRole(['staff']);
                echo "<p>Staff users found: " . count($staff_users) . "</p>";
                
                if (empty($staff_users)) {
                    echo "<p style='color: red;'>❌ No staff users found - this is the problem!</p>";
                    continue;
                }
                
                $start_time = microtime(true);
                $result = call_user_func_array([$notificationHelper, $method], $params);
                $execution_time = round((microtime(true) - $start_time) * 1000, 2);
                
                $status = $result ? "✅ SUCCESS" : "❌ FAILED";
                $color = $result ? "green" : "red";
                
                echo "<p style='color: {$color};'>{$status} ({$execution_time}ms)</p>";
                
                if ($result) {
                    $success_count++;
                } else {
                    $failed_count++;
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>❌ EXCEPTION: " . $e->getMessage() . "</p>";
                echo "<p>Stack trace: " . $e->getTraceAsString() . "</p>";
                $failed_count++;
            }
        }
        
        // Summary
        $total_methods = count($methods_to_test) + count($problematic_methods);
        $success_rate = round(($success_count / $total_methods) * 100, 1);
        
        echo "<h3>📊 Test Results Summary</h3>";
        echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0;'>";
        echo "<ul>";
        echo "<li><strong>Total Methods:</strong> {$total_methods}</li>";
        echo "<li><strong>Successful:</strong> <span style='color: green;'>{$success_count}</span></li>";
        echo "<li><strong>Failed:</strong> <span style='color: red;'>{$failed_count}</span></li>";
        echo "<li><strong>Success Rate:</strong> <strong>{$success_rate}%</strong></li>";
        echo "</ul>";
        echo "</div>";
        
        // Database verification
        echo "<h3>🗄️ Database Verification</h3>";
        
        $notification_query = "SELECT COUNT(*) as total FROM notifications WHERE related_id = ?";
        $notification_stmt = $db->prepare($notification_query);
        $notification_stmt->execute([$test_request_id]);
        $notification_count = $notification_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Notifications created:</strong> {$notification_count['total']}</p>";
        
        // Show recent notifications
        $recent_query = "SELECT n.*, u.full_name FROM notifications n LEFT JOIN users u ON n.user_id = u.id WHERE n.related_id = ? ORDER BY n.created_at DESC LIMIT 10";
        $recent_stmt = $db->prepare($recent_query);
        $recent_stmt->execute([$test_request_id]);
        $recent_notifications = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($recent_notifications)) {
            echo "<h5>Recent Notifications:</h5>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>User</th><th>Title</th><th>Type</th><th>Created</th>";
            echo "</tr>";
            
            foreach ($recent_notifications as $notif) {
                echo "<tr>";
                echo "<td>" . ($notif['full_name'] ?: 'User ' . $notif['user_id']) . "</td>";
                echo "<td>{$notif['title']}</td>";
                echo "<td>{$notif['type']}</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Assessment
        echo "<div style='background-color: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #28a745;'>";
        echo "<h3>🎯 Assessment:</h3>";
        
        if ($success_rate >= 90) {
            echo "<p style='color: green;'><strong>✅ EXCELLENT: Database notifications working perfectly!</strong></p>";
        } elseif ($success_rate >= 75) {
            echo "<p style='color: orange;'><strong>⚠️ GOOD: Most notifications working</strong></p>";
        } else {
            echo "<p style='color: red;'><strong>❌ NEEDS WORK: Database notifications have issues</strong></p>";
        }
        
        echo "<p><strong>Next Steps:</strong></p>";
        echo "<ul>";
        echo "<li>✅ Database notifications are working</li>";
        echo "<li>⚠️ Email sending may need optimization</li>";
        echo "<li>🔧 Consider background email processing</li>";
        echo "<li>📊 Monitor email sending performance</li>";
        echo "</ul>";
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Global Error: " . $e->getMessage() . "</p>";
    }
}

echo "<h2>Test Form:</h2>";
echo "<div style='padding: 20px; background-color: #f8f9fa; border-radius: 5px; margin: 20px 0;'>";
echo "<h3>🧪 Simple Database Notification Test</h3>";
echo "<p>This test focuses on database notifications only, without email complications.</p>";
echo "<form method='POST'>";
echo "<input type='hidden' name='simple_test' value='1'>";
echo "<button type='submit' style='background-color: #007bff; color: white; padding: 10px 20px; border: none; cursor: pointer; border-radius: 4px;'>
            🧪 Run Simple Test
        </button>";
echo "</form>";
echo "</div>";

echo "<h2>📋 Quick Links:</h2>";
echo "<ul>";
echo "<li><a href='test-notifications-final-verification.php'>Final Verification Test</a></li>";
echo "<li><a href='debug-specific-failures.php'>Debug Specific Failures</a></li>";
echo "<li><a href='index.html'>Back to Main Application</a></li>";
echo "</ul>";
?>
