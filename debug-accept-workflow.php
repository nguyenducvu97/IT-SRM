<?php
require_once 'config/database.php';

echo "<h2>DEBUG ACCEPT REQUEST WORKFLOW</h2>";

// 1. Check if ServiceRequestNotificationHelper exists
echo "<h3>1. Check ServiceRequestNotificationHelper</h3>";

if (file_exists('lib/ServiceRequestNotificationHelper.php')) {
    echo "<p>File exists: lib/ServiceRequestNotificationHelper.php</p>";
    
    try {
        require_once 'lib/ServiceRequestNotificationHelper.php';
        $helper = new ServiceRequestNotificationHelper();
        echo "<p>Class instantiated successfully</p>";
        
        // Test methods exist
        if (method_exists($helper, 'notifyUserRequestInProgress')) {
            echo "<p>Method notifyUserRequestInProgress exists</p>";
        } else {
            echo "<p style='color: red;'>Method notifyUserRequestInProgress missing</p>";
        }
        
        if (method_exists($helper, 'notifyAdminStatusChange')) {
            echo "<p>Method notifyAdminStatusChange exists</p>";
        } else {
            echo "<p style='color: red;'>Method notifyAdminStatusChange missing</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>File missing: lib/ServiceRequestNotificationHelper.php</p>";
}

// 2. Check NotificationHelper
echo "<h3>2. Check NotificationHelper</h3>";

if (file_exists('lib/NotificationHelper.php')) {
    echo "<p>File exists: lib/NotificationHelper.php</p>";
    
    try {
        require_once 'lib/NotificationHelper.php';
        $notifHelper = new NotificationHelper();
        echo "<p>Class instantiated successfully</p>";
        
        // Test createNotification method
        if (method_exists($notifHelper, 'createNotification')) {
            echo "<p>Method createNotification exists</p>";
            
            // Test creating a notification
            $testResult = $notifHelper->createNotification(
                4, // user_id 4 (Nguyêñ Ðúç Vû)
                'Test Accept Workflow',
                'This is a test to verify notification creation works',
                'info',
                999, // test request_id
                'service_request'
            );
            
            if ($testResult) {
                echo "<p style='color: green;'>Test notification created successfully (ID: $testResult)</p>";
            } else {
                echo "<p style='color: red;'>Test notification creation failed</p>";
            }
            
        } else {
            echo "<p style='color: red;'>Method createNotification missing</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
    }
} else {
    echo "<p style='color: red;'>File missing: lib/NotificationHelper.php</p>";
}

// 3. Simulate accept request workflow
echo "<h3>3. Simulate Accept Request Workflow</h3>";

try {
    $pdo = getDatabaseConnection();
    
    // Get a test request
    $testRequestQuery = "SELECT id, user_id, title, status, assigned_to FROM service_requests 
                         WHERE status = 'open' AND (assigned_to IS NULL OR assigned_to = 0) 
                         ORDER BY id DESC LIMIT 1";
    $testRequestStmt = $pdo->prepare($testRequestQuery);
    $testRequestStmt->execute();
    $testRequest = $testRequestStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testRequest) {
        echo "<h4>Found test request:</h4>";
        echo "<pre>" . print_r($testRequest, true) . "</pre>";
        
        // Simulate the accept request logic
        $requestId = $testRequest['id'];
        $userId = $testRequest['user_id'];
        $staffId = 2; // John Smith
        $staffName = 'John Smith';
        
        echo "<h4>Simulating accept request...</h4>";
        
        // Update request
        $updateQuery = "UPDATE service_requests 
                       SET assigned_to = :staff_id, status = 'in_progress', updated_at = NOW() 
                       WHERE id = :request_id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(":request_id", $requestId);
        $updateStmt->bindParam(":staff_id", $staffId);
        
        if ($updateStmt->execute()) {
            echo "<p style='color: green;'>Request updated successfully</p>";
            
            // Test notifications
            $helper = new ServiceRequestNotificationHelper();
            
            echo "<h4>Testing user notification...</h4>";
            $userResult = $helper->notifyUserRequestInProgress($requestId, $userId, $staffName);
            if ($userResult) {
                echo "<p style='color: green;'>User notification created (ID: $userResult)</p>";
            } else {
                echo "<p style='color: red;'>User notification failed</p>";
            }
            
            echo "<h4>Testing admin notification...</h4>";
            $adminResult = $helper->notifyAdminStatusChange(
                $requestId, 
                'open', 
                'in_progress', 
                $staffName, 
                $testRequest['title']
            );
            if ($adminResult) {
                echo "<p style='color: green;'>Admin notification created</p>";
            } else {
                echo "<p style='color: red;'>Admin notification failed</p>";
            }
            
            // Check results in database
            echo "<h4>Checking database results...</h4>";
            
            $checkQuery = "SELECT n.*, u.full_name as user_name 
                          FROM notifications n 
                          LEFT JOIN users u ON n.user_id = u.id 
                          WHERE (n.related_id = :request_id AND n.related_type = 'service_request')
                          OR n.message LIKE '%#{$requestId}%'
                          ORDER BY n.created_at DESC LIMIT 10";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->bindParam(":request_id", $requestId);
            $checkStmt->execute();
            $notifications = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($notifications) > 0) {
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>User</th><th>Title</th><th>Message</th><th>Created At</th></tr>";
                foreach ($notifications as $notif) {
                    echo "<tr>";
                    echo "<td>{$notif['id']}</td>";
                    echo "<td>{$notif['user_name']} (ID: {$notif['user_id']})</td>";
                    echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                    echo "<td>{$notif['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
            } else {
                echo "<p style='color: red;'>No notifications found in database</p>";
            }
            
        } else {
            echo "<p style='color: red;'>Failed to update request</p>";
        }
        
    } else {
        echo "<p style='color: orange;'>No suitable test request found</p>";
        echo "<p>Looking for: status = 'open' AND assigned_to IS NULL</p>";
        
        // Show all requests for debugging
        $allRequestsQuery = "SELECT id, title, status, assigned_to FROM service_requests ORDER BY id DESC LIMIT 5";
        $allRequestsStmt = $pdo->prepare($allRequestsQuery);
        $allRequestsStmt->execute();
        $allRequests = $allRequestsStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>Recent requests:</h4>";
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th></tr>";
        foreach ($allRequests as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>" . htmlspecialchars($req['title']) . "</td>";
            echo "<td>{$req['status']}</td>";
            echo "<td>{$req['assigned_to']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>4. Test API Call</h3>";
echo "<p>Test the actual API endpoint:</p>";
echo "<a href='api/service_requests.php' target='_blank'>api/service_requests.php</a>";
echo "<p>Use POST method with action=accept_request to test</p>";
?>
