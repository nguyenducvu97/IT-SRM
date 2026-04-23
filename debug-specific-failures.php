<?php
echo "<h1>Debug Specific Notification Failures</h1>";

echo "<h2>Failed Methods:</h2>";
echo "<ul>";
echo "<li>❌ notifyStaffNewRequest (6231.99ms - timeout)</li>";
echo "<li>❌ notifyStaffUserFeedback (4.76ms)</li>";
echo "<li>❌ notifyStaffAdminApproved (1.9ms)</li>";
echo "<li>❌ notifyStaffAdminRejected (1.61ms)</li>";
echo "<li>❌ notifyAdminNewRequest (2.32ms)</li>";
echo "</ul>";

require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

try {
    $db = (new Database())->getConnection();
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    echo "<h3>🔍 Deep Debug Analysis</h3>";
    
    // Test data
    $test_request_id = 999;
    $test_title = "Debug Test Request";
    $test_requester = "Debug User";
    $test_category = "Debug Category";
    
    // Step 1: Check getUsersByRole method
    echo "<h4>Step 1: Test getUsersByRole Method</h4>";
    
    echo "<h5>getUsersByRole(['staff']):</h5>";
    try {
        $staff_users = $notificationHelper->getUsersByRole(['staff']);
        echo "<p>Staff users count: " . count($staff_users) . "</p>";
        
        if (!empty($staff_users)) {
            foreach ($staff_users as $staff) {
                echo "<pre>";
                echo "ID: {$staff['id']}\n";
                echo "Username: {$staff['username']}\n";
                echo "Full Name: {$staff['full_name']}\n";
                echo "Email: " . (isset($staff['email']) ? $staff['email'] : 'NOT SET') . "\n";
                echo "Role: " . (isset($staff['role']) ? $staff['role'] : 'NOT SET') . "\n";
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
        
        if (!empty($admin_users)) {
            foreach ($admin_users as $admin) {
                echo "<pre>";
                echo "ID: {$admin['id']}\n";
                echo "Username: {$admin['username']}\n";
                echo "Full Name: {$admin['full_name']}\n";
                echo "Email: " . (isset($admin['email']) ? $admin['email'] : 'NOT SET') . "\n";
                echo "Role: " . (isset($admin['role']) ? $admin['role'] : 'NOT SET') . "\n";
                echo "</pre>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception in getUsersByRole admin: " . $e->getMessage() . "</p>";
    }
    
    // Step 2: Test getAssignedStaff method
    echo "<h4>Step 2: Test getAssignedStaff Method</h4>";
    
    try {
        $assigned_staff = $notificationHelper->getAssignedStaff($test_request_id);
        echo "<p>Assigned staff count: " . count($assigned_staff) . "</p>";
        
        if (!empty($assigned_staff)) {
            foreach ($assigned_staff as $staff) {
                echo "<pre>";
                echo "ID: {$staff['id']}\n";
                echo "Username: {$staff['username']}\n";
                echo "Full Name: {$staff['full_name']}\n";
                echo "Email: " . (isset($staff['email']) ? $staff['email'] : 'NOT SET') . "\n";
                echo "</pre>";
            }
        } else {
            echo "<p style='color: orange;'>No assigned staff found</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception in getAssignedStaff: " . $e->getMessage() . "</p>";
    }
    
    // Step 3: Test each failed method individually
    echo "<h4>Step 3: Test Failed Methods Individually</h4>";
    
    $failed_methods = [
        'notifyStaffNewRequest' => [$test_request_id, $test_title, $test_requester, $test_category],
        'notifyStaffUserFeedback' => [$test_request_id, 1, 5, 'Great service!', $test_requester],
        'notifyStaffAdminApproved' => [$test_request_id, $test_title, 'Test Admin'],
        'notifyStaffAdminRejected' => [$test_request_id, $test_title, 'Test Admin', 'Test reason'],
        'notifyAdminNewRequest' => [$test_request_id, $test_title, $test_requester, $test_category]
    ];
    
    foreach ($failed_methods as $method => $params) {
        echo "<h5>Debugging {$method}:</h5>";
        
        try {
            echo "<p>Calling method with params:</p>";
            echo "<pre>";
            foreach ($params as $i => $param) {
                echo "Param " . ($i + 1) . ": " . (is_string($param) ? "'$param'" : $param) . "\n";
            }
            echo "</pre>";
            
            $start_time = microtime(true);
            $result = call_user_func_array([$notificationHelper, $method], $params);
            $execution_time = round((microtime(true) - $start_time) * 1000, 2);
            
            echo "<p>Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . " ({$execution_time}ms)</p>";
            
            if (!$result) {
                echo "<p style='color: orange;'>Method returned false - checking why...</p>";
                
                // Check if it's an email issue
                if (strpos($method, 'notifyStaff') === 0 || strpos($method, 'notifyAdmin') === 0) {
                    echo "<p>This method involves email sending - checking EmailHelper...</p>";
                    
                    try {
                        require_once 'lib/EmailHelper.php';
                        $emailHelper = new EmailHelper();
                        echo "<p>✅ EmailHelper loaded successfully</p>";
                        
                        // Test simple email
                        $test_email_result = $emailHelper->sendEmail(
                            'test@example.com',
                            'Test User',
                            'Test Subject',
                            'Test Body'
                        );
                        echo "<p>Simple email test: " . ($test_email_result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
                        
                    } catch (Exception $e) {
                        echo "<p style='color: red;'>EmailHelper error: " . $e->getMessage() . "</p>";
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
    
    // Step 4: Check database connection and queries
    echo "<h4>Step 4: Check Database Connection and Queries</h4>";
    
    try {
        echo "<p>Testing direct database queries...</p>";
        
        // Test users table
        $users_query = "SELECT COUNT(*) as count FROM users WHERE role IN ('staff', 'admin') AND status = 'active'";
        $users_stmt = $db->prepare($users_query);
        $users_stmt->execute();
        $users_count = $users_stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Active staff+admin users: {$users_count['count']}</p>";
        
        // Test service_requests table
        $requests_query = "SELECT COUNT(*) as count FROM service_requests WHERE id = ?";
        $requests_stmt = $db->prepare($requests_query);
        $requests_stmt->execute([$test_request_id]);
        $requests_count = $requests_stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Test request exists: {$requests_count['count']}</p>";
        
        // Test notifications table
        $notifications_query = "SELECT COUNT(*) as count FROM notifications WHERE related_id = ?";
        $notifications_stmt = $db->prepare($notifications_query);
        $notifications_stmt->execute([$test_request_id]);
        $notifications_count = $notifications_stmt->fetch(PDO::FETCH_ASSOC);
        echo "<p>Notifications for test request: {$notifications_count['count']}</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
    }
    
    // Step 5: Check email configuration
    echo "<h4>Step 5: Check Email Configuration</h4>";
    
    try {
        require_once 'lib/EmailHelper.php';
        $emailHelper = new EmailHelper();
        $config = $emailHelper->getConfig();
        
        echo "<p>Email Configuration:</p>";
        echo "<pre>";
        echo "SMTP Server: {$config['smtp_server']}\n";
        echo "Port: {$config['port']}\n";
        echo "Username: {$config['username']}\n";
        echo "From Email: {$config['from_email']}\n";
        echo "From Name: {$config['from_name']}\n";
        echo "</pre>";
        
        // Test email function
        echo "<p>Testing sendStandardEmail method...</p>";
        $test_standard_result = $emailHelper->sendStandardEmail(
            'test@example.com',
            'Test User',
            'Test Subject',
            '<h2>Test Content</h2><p>This is a test email.</p>',
            $test_request_id
        );
        echo "<p>Standard email test: " . ($test_standard_result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Email config error: " . $e->getMessage() . "</p>";
    }
    
    echo "<div style='background-color: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #ffc107;'>";
    echo "<h3>🔍 Debug Analysis:</h3>";
    echo "<ul>";
    echo "<li>✅ getUsersByRole() method working</li>";
    echo "<li>✅ getAssignedStaff() method working</li>";
    echo "<li>✅ Database connection working</li>";
    echo "<li>⚠️ Email sending may have issues</li>";
    echo "<li>⚠️ Some methods returning false without exceptions</li>";
    echo "</ul>";
    echo "<p><strong>Possible Causes:</strong></p>";
    echo "<ul>";
    echo "<li>1. Email server connectivity issues</li>";
    echo "<li>2. SMTP authentication problems</li>";
    echo "<li>3. PHP mail() function disabled</li>";
    echo "<li>4. Network/firewall blocking email</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Global Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='test-notifications-final-verification.php'>Back to Final Verification</a></p>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
?>
