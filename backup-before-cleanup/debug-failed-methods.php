<?php
echo "<h1>Debug Failed Notification Methods</h1>";

echo "<h2>Failed Methods (Fast Failures):</h2>";
echo "<ul>";
echo "<li>❌ notifyStaffNewRequest (0.95ms)</li>";
echo "<li>❌ notifyStaffAdminApproved (0.9ms)</li>";
echo "<li>❌ notifyStaffAdminRejected (0.63ms)</li>";
echo "<li>❌ notifyAdminNewRequest (1.06ms)</li>";
echo "</ul>";

require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

try {
    $db = (new Database())->getConnection();
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    echo "<h3>🔍 Deep Debug Analysis</h3>";
    
    $test_request_id = 777;
    $test_title = "Debug Test Request";
    $test_requester = "Debug User";
    $test_category = "Debug Category";
    $test_admin_name = "Test Admin";
    
    // Step 1: Test getUsersByRole in detail
    echo "<h4>Step 1: Test getUsersByRole in Detail</h4>";
    
    echo "<h5>getUsersByRole(['staff']):</h5>";
    try {
        $staff_users = $notificationHelper->getUsersByRole(['staff']);
        echo "<p>Staff users count: " . count($staff_users) . "</p>";
        
        if (empty($staff_users)) {
            echo "<p style='color: red;'>❌ NO STAFF USERS FOUND - This is the problem!</p>";
        } else {
            echo "<p style='color: green;'>✅ Staff users found</p>";
            foreach ($staff_users as $i => $staff) {
                echo "<pre>";
                echo "Staff #$i:\n";
                echo "  ID: {$staff['id']}\n";
                echo "  Username: {$staff['username']}\n";
                echo "  Full Name: {$staff['full_name']}\n";
                echo "  Email: " . (isset($staff['email']) ? $staff['email'] : 'NOT SET') . "\n";
                echo "  Role: " . (isset($staff['role']) ? $staff['role'] : 'NOT SET') . "\n";
                echo "</pre>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception in getUsersByRole staff: " . $e->getMessage() . "</p>";
    }
    
    echo "<h5>getUsersByRole(['admin']):</h5>";
    try {
        $admin_users = $notificationHelper->getUsersByRole(['admin']);
        echo "<p>Admin users count: " . count($admin_users) . "</p>";
        
        if (empty($admin_users)) {
            echo "<p style='color: red;'>❌ NO ADMIN USERS FOUND - This is the problem!</p>";
        } else {
            echo "<p style='color: green;'>✅ Admin users found</p>";
            foreach ($admin_users as $i => $admin) {
                echo "<pre>";
                echo "Admin #$i:\n";
                echo "  ID: {$admin['id']}\n";
                echo "  Username: {$admin['username']}\n";
                echo "  Full Name: {$admin['full_name']}\n";
                echo "  Email: " . (isset($admin['email']) ? $admin['email'] : 'NOT SET') . "\n";
                echo "  Role: " . (isset($admin['role']) ? $admin['role'] : 'NOT SET') . "\n";
                echo "</pre>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception in getUsersByRole admin: " . $e->getMessage() . "</p>";
    }
    
    // Step 2: Test notificationHelper->createNotification directly
    echo "<h4>Step 2: Test notificationHelper->createNotification Directly</h4>";
    
    try {
        $test_user_id = 2; // Staff user
        $test_title = "Test Notification";
        $test_message = "This is a test notification";
        
        echo "<p>Testing createNotification with user_id={$test_user_id}...</p>";
        
        $create_result = $notificationHelper->notificationHelper->createNotification(
            $test_user_id,
            $test_title,
            $test_message,
            'info',
            $test_request_id,
            'service_request',
            false
        );
        
        echo "<p>createNotification result: " . ($create_result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
        
        if (!$create_result) {
            echo "<p style='color: red;'>createNotification is returning false - checking why...</p>";
            
            // Check if user exists
            $user_check = $db->prepare("SELECT id, full_name, status FROM users WHERE id = ?");
            $user_check->execute([$test_user_id]);
            $user = $user_check->fetch(PDO::FETCH_ASSOC);
            
            if (!$user) {
                echo "<p style='color: red;'>❌ User ID {$test_user_id} does not exist in database!</p>";
            } else {
                echo "<p>✅ User exists: {$user['full_name']} (status: {$user['status']})</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception in createNotification: " . $e->getMessage() . "</p>";
        echo "<p>Stack trace:</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // Step 3: Test each failed method with detailed logging
    echo "<h4>Step 3: Test Failed Methods with Detailed Logging</h4>";
    
    $failed_methods = [
        'notifyStaffNewRequest' => [$test_request_id, $test_title, $test_requester, $test_category],
        'notifyStaffAdminApproved' => [$test_request_id, $test_title, $test_admin_name],
        'notifyStaffAdminRejected' => [$test_request_id, $test_title, $test_admin_name, 'Test reason'],
        'notifyAdminNewRequest' => [$test_request_id, $test_title, $test_requester, $test_category]
    ];
    
    foreach ($failed_methods as $method => $params) {
        echo "<h5>Debugging {$method}:</h5>";
        echo "<p>Parameters: " . count($params) . " params</p>";
        
        try {
            // Test getUsersByRole first
            if (strpos($method, 'notifyStaff') === 0) {
                $staff_users = $notificationHelper->getUsersByRole(['staff']);
                echo "<p>Staff users before method call: " . count($staff_users) . "</p>";
                
                if (empty($staff_users)) {
                    echo "<p style='color: red;'>❌ No staff users - method will fail!</p>";
                    continue;
                }
            } elseif (strpos($method, 'notifyAdmin') === 0) {
                $admin_users = $notificationHelper->getUsersByRole(['admin']);
                echo "<p>Admin users before method call: " . count($admin_users) . "</p>";
                
                if (empty($admin_users)) {
                    echo "<p style='color: red;'>❌ No admin users - method will fail!</p>";
                    continue;
                }
            }
            
            // Call the method
            $start_time = microtime(true);
            $result = call_user_func_array([$notificationHelper, $method], $params);
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            echo "<p>Method result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . " ({$execution_time}ms)</p>";
            
            if (!$result) {
                echo "<p style='color: orange;'>Method returned false - checking return values...</p>";
                
                // Check if it's because of empty results array
                if (strpos($method, 'notifyStaff') === 0) {
                    $staff_users = $notificationHelper->getUsersByRole(['staff']);
                    echo "<p>Staff users: " . count($staff_users) . "</p>";
                    
                    if (count($staff_users) == 0) {
                        echo "<p style='color: red;'>❌ Empty staff users array causes in_array(false, []) = true</p>";
                    } else {
                        echo "<p>Staff users not empty - checking createNotification results...</p>";
                        
                        // Test createNotification for each staff
                        foreach ($staff_users as $staff) {
                            $test_result = $notificationHelper->notificationHelper->createNotification(
                                $staff['id'],
                                "Test",
                                "Test message",
                                'info',
                                null,
                                null,
                                false
                            );
                            echo "<p>createNotification for staff {$staff['id']}: " . ($test_result ? "✅" : "❌") . "</p>";
                        }
                    }
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Exception in {$method}: " . $e->getMessage() . "</p>";
            echo "<p>Stack trace:</p>";
            echo "<pre>" . $e->getTraceAsString() . "</pre>";
        }
        
        echo "<hr>";
    }
    
    // Step 4: Check database directly
    echo "<h4>Step 4: Check Database Directly</h4>";
    
    try {
        $staff_query = "SELECT id, username, full_name, email, role, status FROM users WHERE role = 'staff' AND status = 'active'";
        $staff_stmt = $db->query($staff_query);
        $staff_from_db = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Direct DB query for staff: " . count($staff_from_db) . " results</p>";
        
        if (!empty($staff_from_db)) {
            foreach ($staff_from_db as $staff) {
                echo "<pre>";
                echo "ID: {$staff['id']}\n";
                echo "Username: {$staff['username']}\n";
                echo "Full Name: {$staff['full_name']}\n";
                echo "Email: {$staff['email']}\n";
                echo "Role: {$staff['role']}\n";
                echo "Status: {$staff['status']}\n";
                echo "</pre>";
            }
        }
        
        $admin_query = "SELECT id, username, full_name, email, role, status FROM users WHERE role = 'admin' AND status = 'active'";
        $admin_stmt = $db->query($admin_query);
        $admin_from_db = $admin_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p>Direct DB query for admin: " . count($admin_from_db) . " results</p>";
        
        if (!empty($admin_from_db)) {
            foreach ($admin_from_db as $admin) {
                echo "<pre>";
                echo "ID: {$admin['id']}\n";
                echo "Username: {$admin['username']}\n";
                echo "Full Name: {$admin['full_name']}\n";
                echo "Email: {$admin['email']}\n";
                echo "Role: {$admin['role']}\n";
                echo "Status: {$admin['status']}\n";
                echo "</pre>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>DB query error: " . $e->getMessage() . "</p>";
    }
    
    echo "<div style='background-color: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #ffc107;'>";
    echo "<h3>🔍 Debug Summary:</h3>";
    echo "<ul>";
    echo "<li>✅ getUsersByRole() method is being tested</li>";
    echo "<li>✅ createNotification() method is being tested</li>";
    echo "<li>✅ Direct database queries are being tested</li>";
    echo "<li>⚠️ Failed methods are being analyzed individually</li>";
    echo "</ul>";
    echo "<p><strong>Possible Issues:</strong></p>";
    echo "<ul>";
    echo "<li>1. getUsersByRole() returning empty array</li>";
    echo "<li>2. createNotification() returning false for specific users</li>";
    echo "<li>3. Database query issues</li>";
    echo "<li>4. User status filtering problems</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Global Error: " . $e->getMessage() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='test-notifications-database-only.php'>Back to Database-Only Test</a></p>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
?>
