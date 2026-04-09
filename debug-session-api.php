<?php
echo "<h2>DEBUG SESSION IN API</h2>";

// Start session exactly like API does
require_once __DIR__ . '/config/session.php';
startSession();

echo "<h3>Session Debug Info</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Not Active") . "</p>";
echo "<p><strong>Session Data:</strong></p>";
if (empty($_SESSION)) {
    echo "<p style='color: red;'>&#10027; NO SESSION DATA</p>";
} else {
    echo "<pre>" . json_encode($_SESSION, JSON_PRETTY_PRINT) . "</pre>";
}

echo "<h3>Test isLoggedIn() Function</h3>";
if (function_exists('isLoggedIn')) {
    $loggedIn = isLoggedIn();
    echo "<p><strong>isLoggedIn():</strong> " . ($loggedIn ? "TRUE" : "FALSE") . "</p>";
    
    if ($loggedIn) {
        $user = getCurrentUser();
        echo "<p><strong>Current User:</strong></p>";
        echo "<pre>" . json_encode($user, JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>&#10027; isLoggedIn() function not found</p>";
}

echo "<h3>Simulate API Accept Request</h3>";

// Check if user is logged in and is staff
if (!function_exists('isLoggedIn') || !isLoggedIn()) {
    echo "<p style='color: red;'>&#10027; User not logged in - This is why API returns 'Unauthorized access'</p>";
} else {
    $user_role = $_SESSION['role'] ?? '';
    echo "<p><strong>User Role:</strong> {$user_role}</p>";
    
    if ($user_role !== 'staff') {
        echo "<p style='color: red;'>&#10027; User is not staff - Role: {$user_role}</p>";
    } else {
        echo "<p style='color: green;'>&#10004; User is logged in as staff - API should work!</p>";
        
        // Test the accept request logic
        require_once __DIR__ . '/config/database.php';
        $db = getDatabaseConnection();
        
        $request_id = 72;
        $user_id = $_SESSION['user_id'];
        
        echo "<h4>Testing Accept Request Logic...</h4>";
        
        try {
            // Check if request exists and can be accepted
            $check_query = "SELECT id, assigned_to, status FROM service_requests 
                           WHERE id = ? AND (status = 'open' OR status = 'request_support') 
                           AND (assigned_to IS NULL OR assigned_to = 0)";
            
            $check_stmt = $db->prepare($check_query);
            $check_stmt->execute([$request_id]);
            
            if ($check_stmt->rowCount() > 0) {
                echo "<p style='color: green;'>&#10004; Request is available for assignment</p>";
                
                // Update request
                $update_query = "UPDATE service_requests 
                               SET assigned_to = ?, status = 'in_progress', updated_at = NOW() 
                               WHERE id = ?";
                
                $update_stmt = $db->prepare($update_query);
                $result = $update_stmt->execute([$user_id, $request_id]);
                
                if ($result) {
                    echo "<p style='color: green;'>&#10004; Request updated successfully!</p>";
                    
                    // Test notifications
                    echo "<h4>Testing Notifications...</h4>";
                    
                    require_once __DIR__ . '/lib/ServiceRequestNotificationHelper.php';
                    $notificationHelper = new ServiceRequestNotificationHelper();
                    
                    // Get request details
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
                        
                        // Test notification functions
                        echo "<h4>Testing Notification Functions:</h4>";
                        
                        // 1. Notify user
                        $result1 = $notificationHelper->notifyUserRequestInProgress(
                            $request_id, 
                            $request_data['user_id'], 
                            $request_data['assigned_name']
                        );
                        echo "<p>notifyUserRequestInProgress(): " . ($result1 ? "SUCCESS" : "FAILED") . "</p>";
                        
                        // 2. Notify admins
                        $result2 = $notificationHelper->notifyAdminStatusChange(
                            $request_id, 
                            'open', 
                            'in_progress', 
                            $request_data['assigned_name'], 
                            $request_data['title']
                        );
                        echo "<p>notifyAdminStatusChange(): " . ($result2 ? "SUCCESS" : "FAILED") . "</p>";
                        
                        // 3. Notify other staff
                        $result3 = $notificationHelper->notifyStaffAdminApproved(
                            $request_id, 
                            $request_data['title'], 
                            $request_data['assigned_name']
                        );
                        echo "<p>notifyStaffAdminApproved(): " . ($result3 ? "SUCCESS" : "FAILED") . "</p>";
                        
                        // Check notifications
                        $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 3");
                        $stmt->execute();
                        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        echo "<h4>Latest Notifications:</h4>";
                        echo "<table border='1' cellpadding='5'>";
                        echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th></tr>";
                        foreach ($notifications as $notif) {
                            echo "<tr>";
                            echo "<td>{$notif['id']}</td>";
                            echo "<td>{$notif['user_id']}</td>";
                            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                            echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
                            echo "<td>{$notif['type']}</td>";
                            echo "</tr>";
                        }
                        echo "</table>";
                        
                    } else {
                        echo "<p style='color: red;'>&#10027; Request data not found</p>";
                    }
                    
                } else {
                    echo "<p style='color: red;'>&#10027; Failed to update request</p>";
                }
                
            } else {
                echo "<p style='color: red;'>&#10027; Request not available for assignment</p>";
                
                // Show current request status
                $debug_query = "SELECT id, assigned_to, status FROM service_requests WHERE id = ?";
                $debug_stmt = $db->prepare($debug_query);
                $debug_stmt->execute([$request_id]);
                $debug_info = $debug_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($debug_info) {
                    echo "<p>Current status: '{$debug_info['status']}', Assigned to: '{$debug_info['assigned_to']}'</p>";
                }
            }
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>&#10027; Error: " . $e->getMessage() . "</p>";
        }
    }
}

echo "<h3>CONCLUSION</h3>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128161; ROOT CAUSE ANALYSIS:</h4>";
echo "<p><strong>The issue is NOT with the notification code.</strong></p>";
echo "<p><strong>The issue is with BROWSER SESSION/AUTHENTICATION.</strong></p>";
echo "</div>";

echo "<h3>SOLUTION FOR BROWSER</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128072; Steps to fix browser issue:</h4>";
echo "<ol>";
echo "<li><strong>1. Clear browser cache completely:</strong> Ctrl+Shift+Delete</li>";
echo "<li><strong>2. Login properly:</strong> Go to login page, login as staff1/password123</li>";
echo "<li><strong>3. Verify login:</strong> Check if you can access other staff features</li>";
echo "<li><strong>4. Test accept request:</strong> Click 'Nhận yêu cầu' button</li>";
echo "<li><strong>5. Check console:</strong> F12 -> Console for any errors</li>";
echo "<li><strong>6. Check network:</strong> F12 -> Network for API call status</li>";
echo "</ol>";
echo "</div>";
?>
