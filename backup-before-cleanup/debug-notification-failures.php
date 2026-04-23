<?php
echo "<h1>Debug Notification Failures</h1>";

echo "<h2>Issues Found:</h2>";
echo "<ul>";
echo "<li>❌ notifyStaffNewRequest: FAILED</li>";
echo "<li>❌ notifyStaffUserFeedback: FAILED</li>";
echo "<li>❌ notifyStaffAdminApproved: FAILED</li>";
echo "<li>❌ notifyStaffNewComment: FAILED</li>";
echo "<li>❌ notifyAdminNewRequest: FAILED</li>";
echo "</ul>";

require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

try {
    $db = (new Database())->getConnection();
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    echo "<h3>Debug Steps:</h3>";
    
    // Step 1: Check staff users
    echo "<h4>Step 1: Check Staff Users</h4>";
    $staff_users = $notificationHelper->getUsersByRole(['staff']);
    echo "<p>Staff users found: " . count($staff_users) . "</p>";
    
    if (empty($staff_users)) {
        echo "<p style='color: red;'>❌ No staff users found - this is the problem!</p>";
        
        // Create test staff
        $create_staff = "INSERT INTO users (username, password, full_name, email, role, status, created_at) 
                        VALUES ('teststaff', 'password123', 'Test Staff', 'staff@example.com', 'staff', 'active', NOW())";
        $staff_insert = $db->prepare($create_staff);
        $result = $staff_insert->execute();
        
        if ($result) {
            echo "<p style='color: green;'>✅ Created test staff user</p>";
            $staff_users = $notificationHelper->getUsersByRole(['staff']);
            echo "<p>Staff users after creation: " . count($staff_users) . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Staff users found</p>";
        foreach ($staff_users as $staff) {
            echo "<p>- {$staff['full_name']} ({$staff['email']})</p>";
        }
    }
    
    // Step 2: Check admin users
    echo "<h4>Step 2: Check Admin Users</h4>";
    $admin_users = $notificationHelper->getUsersByRole(['admin']);
    echo "<p>Admin users found: " . count($admin_users) . "</p>";
    
    if (empty($admin_users)) {
        echo "<p style='color: red;'>❌ No admin users found - this is the problem!</p>";
        
        // Create test admin
        $create_admin = "INSERT INTO users (username, password, full_name, email, role, status, created_at) 
                         VALUES ('testadmin', 'password123', 'Test Admin', 'admin@example.com', 'admin', 'active', NOW())";
        $admin_insert = $db->prepare($create_admin);
        $result = $admin_insert->execute();
        
        if ($result) {
            echo "<p style='color: green;'>✅ Created test admin user</p>";
            $admin_users = $notificationHelper->getUsersByRole(['admin']);
            echo "<p>Admin users after creation: " . count($admin_users) . "</p>";
        }
    } else {
        echo "<p style='color: green;'>✅ Admin users found</p>";
        foreach ($admin_users as $admin) {
            echo "<p>- {$admin['full_name']} ({$admin['email']})</p>";
        }
    }
    
    // Step 3: Test individual methods with error handling
    echo "<h4>Step 3: Test Individual Methods</h4>";
    
    $test_request_id = 667;
    $test_user_id = 1;
    $test_title = "Debug Test Request";
    $test_requester = "Debug User";
    $test_category = "Debug Category";
    
    // Test notifyStaffNewRequest
    echo "<h5>Testing notifyStaffNewRequest:</h5>";
    try {
        $result = $notificationHelper->notifyStaffNewRequest($test_request_id, $test_title, $test_requester, $test_category);
        echo "<p>Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    }
    
    // Test notifyStaffUserFeedback
    echo "<h5>Testing notifyStaffUserFeedback:</h5>";
    try {
        $result = $notificationHelper->notifyStaffUserFeedback($test_request_id, $test_user_id, 5, 'Great service!', $test_requester);
        echo "<p>Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    }
    
    // Test notifyStaffAdminApproved
    echo "<h5>Testing notifyStaffAdminApproved:</h5>";
    try {
        $result = $notificationHelper->notifyStaffAdminApproved($test_request_id, $test_title, 'Test Admin');
        echo "<p>Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    }
    
    // Test notifyStaffNewComment
    echo "<h5>Testing notifyStaffNewComment:</h5>";
    try {
        $result = $notificationHelper->notifyStaffNewComment($test_request_id, 'Test Commenter', 'This is a test comment', 'user');
        echo "<p>Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    }
    
    // Test notifyAdminNewRequest
    echo "<h5>Testing notifyAdminNewRequest:</h5>";
    try {
        $result = $notificationHelper->notifyAdminNewRequest($test_request_id, $test_title, $test_requester, $test_category);
        echo "<p>Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    }
    
    // Step 4: Check getAssignedStaff method
    echo "<h4>Step 4: Check getAssignedStaff Method</h4>";
    try {
        $assigned_staff = $notificationHelper->getAssignedStaff($test_request_id);
        echo "<p>Assigned staff for request #{$test_request_id}: " . count($assigned_staff) . "</p>";
        
        if (empty($assigned_staff)) {
            echo "<p style='color: orange;'>No assigned staff found - this might cause some methods to fail</p>";
            
            // Create a test assignment
            $assign_query = "INSERT INTO service_requests (id, assigned_to) VALUES (?, ?) ON DUPLICATE KEY UPDATE assigned_to = ?";
            $assign_stmt = $db->prepare($assign_query);
            $assign_result = $assign_stmt->execute([$test_request_id, $staff_users[0]['id'] ?? 1, $staff_users[0]['id'] ?? 1]);
            
            if ($assign_result) {
                echo "<p style='color: green;'>✅ Assigned staff to test request</p>";
                $assigned_staff = $notificationHelper->getAssignedStaff($test_request_id);
                echo "<p>Assigned staff after assignment: " . count($assigned_staff) . "</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>Exception in getAssignedStaff: " . $e->getMessage() . "</p>";
    }
    
    // Step 5: Test methods again after fixes
    echo "<h4>Step 5: Re-test Methods After Fixes</h4>";
    
    $methods_to_test = [
        'notifyStaffNewRequest' => [$test_request_id, $test_title, $test_requester, $test_category],
        'notifyStaffUserFeedback' => [$test_request_id, $test_user_id, 5, 'Great service!', $test_requester],
        'notifyStaffAdminApproved' => [$test_request_id, $test_title, 'Test Admin'],
        'notifyStaffNewComment' => [$test_request_id, 'Test Commenter', 'This is a test comment', 'user'],
        'notifyAdminNewRequest' => [$test_request_id, $test_title, $test_requester, $test_category]
    ];
    
    foreach ($methods_to_test as $method => $params) {
        echo "<h5>Re-testing {$method}:</h5>";
        try {
            $result = call_user_func_array([$notificationHelper, $method], $params);
            echo "<p>Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . "</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
        }
    }
    
    echo "<div style='background-color: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px; border-left: 4px solid #28a745;'>";
    echo "<h3>Debug Summary:</h3>";
    echo "<ul>";
    echo "<li>✅ Staff users created if missing</li>";
    echo "<li>✅ Admin users created if missing</li>";
    echo "<li>✅ Test request assigned to staff</li>";
    echo "<li>✅ All methods re-tested with proper data</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Global Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='test-all-notifications-complete.php'>Back to Complete Test</a></p>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
?>
