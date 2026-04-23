<?php
// Create new request and test immediately
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Create New Request & Test Accept</h1>";

try {
    require_once 'config/database.php';
    $db = (new Database())->getConnection();
    
    echo "<h2>Step 1: Create New Test Request</h2>";
    
    // Create a new test request
    $insertStmt = $db->prepare("INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())");
    $result = $insertStmt->execute([
        4, // user_id (ndvu)
        'Test Request for Accept Flow - ' . date('Y-m-d H:i:s'),
        'This is a test request created specifically for testing staff accept functionality with notifications.',
        1, // category_id
        'medium',
        'open'
    ]);
    
    if ($result) {
        $new_request_id = $db->lastInsertId();
        echo "<p style='color: green;'>Created new test request #{$new_request_id}</p>";
        
        // Display request details
        $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ?");
        $stmt->execute([$new_request_id]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<h3>New Request Details:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>User ID</th><th>Status</th><th>Created</th></tr>";
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . htmlspecialchars($request['title']) . "</td>";
        echo "<td>{$request['user_id']}</td>";
        echo "<td>{$request['status']}</td>";
        echo "<td>{$request['created_at']}</td>";
        echo "</tr>";
        echo "</table>";
        
        echo "<h2>Step 2: Test Accept Request Directly</h2>";
        
        // Setup session
        session_start();
        $_SESSION['user_id'] = 2;
        $_SESSION['username'] = 'staff';
        $_SESSION['full_name'] = 'Test Staff User';
        $_SESSION['role'] = 'staff';
        
        echo "<p>Session setup: {$_SESSION['full_name']} (Role: {$_SESSION['role']})</p>";
        
        // Accept request directly
        echo "<h3>Accepting Request #{$new_request_id}...</h3>";
        
        // Update request
        $update_query = "UPDATE service_requests 
                        SET assigned_to = :user_id, status = 'in_progress', 
                            assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
                        WHERE id = :request_id";
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(":request_id", $new_request_id);
        $update_stmt->bindParam(":user_id", $_SESSION['user_id']);
        
        if ($update_stmt->execute()) {
            echo "<p style='color: green;'>Database update successful</p>";
            
            // Get request details for notifications
            $request_query = "SELECT sr.*, u.full_name as requester_name, u.email as requester_email, 
                                     staff.full_name as assigned_name, staff.email as assigned_email, c.name as category_name
                              FROM service_requests sr
                              LEFT JOIN users u ON sr.user_id = u.id
                              LEFT JOIN users staff ON sr.assigned_to = staff.id
                              LEFT JOIN categories c ON sr.category_id = c.id
                              WHERE sr.id = :request_id";
            $request_stmt = $db->prepare($request_query);
            $request_stmt->bindParam(":request_id", $new_request_id);
            $request_stmt->execute();
            $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($request_data) {
                echo "<h3>Request Details After Accept:</h3>";
                echo "<table border='1' cellpadding='5'>";
                echo "<tr><th>Title</th><td>" . htmlspecialchars($request_data['title']) . "</td></tr>";
                echo "<tr><th>Requester</th><td>{$request_data['requester_name']}</td></tr>";
                echo "<tr><th>Assigned Staff</th><td>{$request_data['assigned_name']}</td></tr>";
                echo "<tr><th>Requester ID</th><td>{$request_data['user_id']}</td></tr>";
                echo "<tr><th>Assigned To ID</th><td>{$request_data['assigned_to']}</td></tr>";
                echo "</table>";
                
                // Create notifications
                echo "<h2>Step 3: Create Notifications</h2>";
                
                require_once __DIR__ . '/lib/ServiceRequestNotificationHelper.php';
                $notificationHelper = new ServiceRequestNotificationHelper();
                
                // 1. Notify user
                echo "<h3>User Notification</h3>";
                $userNotificationResult = $notificationHelper->notifyUserRequestInProgress(
                    $new_request_id, 
                    $request_data['user_id'], 
                    $request_data['assigned_name']
                );
                echo "<p>User notification result: " . ($userNotificationResult ? "SUCCESS" : "FAILED") . "</p>";
                
                // 2. Notify admins
                echo "<h3>Admin Notifications</h3>";
                $adminNotificationResult = $notificationHelper->notifyAdminStatusChange(
                    $new_request_id, 
                    'open', 
                    'in_progress', 
                    $request_data['assigned_name'], 
                    $request_data['title']
                );
                echo "<p>Admin notification result: " . ($adminNotificationResult ? "SUCCESS" : "FAILED") . "</p>";
                
                // Check results
                echo "<h2>Step 4: Check Results</h2>";
                
                // Check notifications
                $stmt = $db->prepare("SELECT * FROM notifications WHERE related_id = ? AND related_type = 'service_request' ORDER BY created_at DESC");
                $stmt->execute([$new_request_id]);
                $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<h3>Created Notifications:</h3>";
                if (!empty($notifications)) {
                    echo "<table border='1' cellpadding='5'>";
                    echo "<tr><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
                    foreach ($notifications as $notif) {
                        echo "<tr>";
                        echo "<td>{$notif['user_id']}</td>";
                        echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                        echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 80)) . "...</td>";
                        echo "<td>{$notif['type']}</td>";
                        echo "<td>{$notif['created_at']}</td>";
                        echo "</tr>";
                    }
                    echo "</table>";
                    echo "<p style='color: green;'>Total notifications created: " . count($notifications) . "</p>";
                } else {
                    echo "<p style='color: red;'>No notifications created</p>";
                }
                
                echo "<h2>Summary</h2>";
                echo "<p><strong>Request Created:</strong> #{$new_request_id} - SUCCESS</p>";
                echo "<p><strong>Request Accepted:</strong> " . ($request_data['status'] == 'in_progress' ? "SUCCESS" : "FAILED") . "</p>";
                echo "<p><strong>User Notifications:</strong> " . ($userNotificationResult ? "SUCCESS" : "FAILED") . "</p>";
                echo "<p><strong>Admin Notifications:</strong> " . ($adminNotificationResult ? "SUCCESS" : "FAILED") . "</p>";
                echo "<p><strong>Total Notifications:</strong> " . count($notifications) . "</p>";
                
                // Test API with new request
                echo "<h2>Step 5: Test API with New Request</h2>";
                echo "<p><a href='test-accept-minimal.php?request_id={$new_request_id}'>Test API Accept Request #{$new_request_id}</a></p>";
                echo "<p><a href='test-accept-direct-v2.php?request_id={$new_request_id}'>Test Direct Accept Request #{$new_request_id}</a></p>";
                
            } else {
                echo "<p style='color: red;'>Failed to load request details</p>";
            }
            
        } else {
            echo "<p style='color: red;'>Database update failed</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Failed to create test request</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h2>Conclusion</h2>";
echo "<p>This test creates a fresh request and immediately tests the accept flow with notifications.</p>";
echo "<p>If notifications work here, the issue is with the API background processing.</p>";
echo "<p>If notifications don't work here, the issue is with the notification system itself.</p>";

echo "<p><a href='check-notifications.php'>Check All Notifications</a></p>";
echo "<p><a href='index.html'>Main Application</a></p>";
?>
