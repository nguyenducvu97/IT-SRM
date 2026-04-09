<?php
echo "<h2>DEBUG BROWSER ACCEPT REQUEST</h2>";

// Start session
require_once __DIR__ . '/config/session.php';
startSession();

echo "<h3>Current Session Status</h3>";
if (empty($_SESSION)) {
    echo "<p style='color: red;'>&#10027; NO SESSION DATA - User not logged in!</p>";
    echo "<p><strong>Solution:</strong> Please login first</p>";
} else {
    echo "<p style='color: green;'>&#10004; Session data found:</p>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Key</th><th>Value</th></tr>";
    foreach ($_SESSION as $key => $value) {
        echo "<tr><td>{$key}</td><td>" . (is_array($value) ? json_encode($value) : htmlspecialchars($value)) . "</td></tr>";
    }
    echo "</table>";
    
    // Check if user is staff
    $user_role = $_SESSION['role'] ?? '';
    $user_id = $_SESSION['user_id'] ?? 0;
    
    echo "<h3>User Role Check</h3>";
    echo "<p><strong>Role:</strong> {$user_role}</p>";
    echo "<p><strong>User ID:</strong> {$user_id}</p>";
    
    if ($user_role !== 'staff') {
        echo "<p style='color: red;'>&#10027; User is not staff! Current role: {$user_role}</p>";
        echo "<p><strong>Solution:</strong> Please login as staff user (staff1/password123)</p>";
    } else {
        echo "<p style='color: green;'>&#10004; User is staff - OK!</p>";
        
        // Test direct accept request
        echo "<h3>Test Direct Accept Request</h3>";
        
        require_once __DIR__ . '/config/database.php';
        $db = getDatabaseConnection();
        
        // Find an open request
        $stmt = $db->prepare("SELECT id, title FROM service_requests WHERE status = 'open' AND (assigned_to IS NULL OR assigned_to = 0) LIMIT 1");
        $stmt->execute();
        $openRequest = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($openRequest) {
            echo "<p><strong>Found open request:</strong> #{$openRequest['id']} - {$openRequest['title']}</p>";
            
            // Test accept logic directly
            $request_id = $openRequest['id'];
            
            echo "<h4>Testing Accept Logic...</h4>";
            
            try {
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
                        
                        // Test each notification function
                        $notifications = [];
                        
                        // 1. Notify user
                        $result1 = $notificationHelper->notifyUserRequestInProgress(
                            $request_id, 
                            $request_data['user_id'], 
                            $request_data['assigned_name']
                        );
                        $notifications[] = "User notification: " . ($result1 ? "SUCCESS" : "FAILED");
                        
                        // 2. Notify admins
                        $result2 = $notificationHelper->notifyAdminStatusChange(
                            $request_id, 
                            'open', 
                            'in_progress', 
                            $request_data['assigned_name'], 
                            $request_data['title']
                        );
                        $notifications[] = "Admin notification: " . ($result2 ? "SUCCESS" : "FAILED");
                        
                        // 3. Notify other staff
                        $result3 = $notificationHelper->notifyStaffAdminApproved(
                            $request_id, 
                            $request_data['title'], 
                            $request_data['assigned_name']
                        );
                        $notifications[] = "Staff notification: " . ($result3 ? "SUCCESS" : "FAILED");
                        
                        echo "<h4>Notification Results:</h4>";
                        foreach ($notifications as $notif) {
                            echo "<p>- {$notif}</p>";
                        }
                        
                        // Check notifications in database
                        echo "<h4>Check Database Notifications:</h4>";
                        $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 5");
                        $stmt->execute();
                        $dbNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (count($dbNotifications) > 0) {
                            echo "<p style='color: green;'>&#10004; Found " . count($dbNotifications) . " notifications in database:</p>";
                            echo "<table border='1' cellpadding='5'>";
                            echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th></tr>";
                            foreach ($dbNotifications as $notif) {
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
                            echo "<p style='color: red;'>&#10027; No notifications found in database!</p>";
                        }
                        
                    } else {
                        echo "<p style='color: red;'>&#10027; Request data not found</p>";
                    }
                    
                } else {
                    echo "<p style='color: red;'>&#10027; Failed to update request</p>";
                }
                
            } catch (Exception $e) {
                echo "<p style='color: red;'>&#10027; Error: " . $e->getMessage() . "</p>";
            }
            
        } else {
            echo "<p style='color: orange;'>&#9888; No open requests found for testing</p>";
        }
    }
}

echo "<h3>JavaScript Browser Test</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h4>To test in browser:</h4>";
echo "<ol>";
echo "<li>1. Open browser and login as <strong>staff1</strong> with password <strong>password123</strong></li>";
echo "<li>2. Navigate to any request detail page with status 'Open'</li>";
echo "<li>3. Open browser console (F12)</li>";
echo "<li>4. Click 'Nhận yêu cầu' button</li>";
echo "<li>5. Check console for errors</li>";
echo "<li>6. Check Network tab for API call</li>";
echo "<li>7. Run this script again to check if notifications were created</li>";
echo "</ol>";
echo "</div>";

echo "<h3>Troubleshooting Checklist</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128161; Common Issues:</h4>";
echo "<ul>";
echo "<li>&#10027; <strong>Not logged in:</strong> User must login as staff</li>";
echo "<li>&#10027; <strong>Wrong role:</strong> User role must be 'staff'</li>";
echo "<li>&#10027; <strong>Request assigned:</strong> Request must be unassigned</li>";
echo "<li>&#10027; <strong>Request status:</strong> Request must be 'open'</li>";
echo "<li>&#10027; <strong>API error:</strong> Check browser console for errors</li>";
echo "<li>&#10027; <strong>Network issue:</strong> Check Network tab for failed requests</li>";
echo "</ul>";
echo "</div>";
?>
