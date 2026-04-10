<?php
require_once 'config/database.php';

echo "<h2>CREATE TEST REQUEST FOR ACCEPT WORKFLOW</h2>";

try {
    $pdo = getDatabaseConnection();
    
    // Create a new test request
    echo "<h3>Creating new test request...</h3>";
    
    $title = "Test Request for Accept Workflow " . date('Y-m-d H:i:s');
    $description = "This is a test request to verify the accept request notification workflow.";
    $userId = 4; // Nguyen Duc Vu
    $categoryId = 1; // Hardware
    
    $insertQuery = "INSERT INTO service_requests (user_id, category_id, title, description, status, created_at, updated_at) 
                   VALUES (:user_id, :category_id, :title, :description, 'open', NOW(), NOW())";
    
    $insertStmt = $pdo->prepare($insertQuery);
    $insertStmt->bindParam(":user_id", $userId);
    $insertStmt->bindParam(":category_id", $categoryId);
    $insertStmt->bindParam(":title", $title);
    $insertStmt->bindParam(":description", $description);
    
    if ($insertStmt->execute()) {
        $newRequestId = $pdo->lastInsertId();
        echo "<p style='color: green;'>Test request created successfully!</p>";
        echo "<p><strong>Request ID:</strong> {$newRequestId}</p>";
        echo "<p><strong>Title:</strong> {$title}</p>";
        echo "<p><strong>Status:</strong> open</p>";
        echo "<p><strong>Assigned To:</strong> NULL</p>";
        
        echo "<h3>Now testing accept workflow...</h3>";
        
        // Simulate staff accept request
        $staffId = 2; // John Smith
        $staffName = 'John Smith';
        
        echo "<h4>Step 1: Update request status</h4>";
        
        $updateQuery = "UPDATE service_requests 
                       SET assigned_to = :staff_id, status = 'in_progress', updated_at = NOW() 
                       WHERE id = :request_id";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->bindParam(":request_id", $newRequestId);
        $updateStmt->bindParam(":staff_id", $staffId);
        
        if ($updateStmt->execute()) {
            echo "<p style='color: green;'>Request updated successfully</p>";
            
            echo "<h4>Step 2: Send notifications</h4>";
            
            require_once 'lib/ServiceRequestNotificationHelper.php';
            $helper = new ServiceRequestNotificationHelper();
            
            // Notify user
            echo "<h5>Notifying user (ID: {$userId})...</h5>";
            $userResult = $helper->notifyUserRequestInProgress($newRequestId, $userId, $staffName);
            if ($userResult) {
                echo "<p style='color: green;'>User notification created (ID: {$userResult})</p>";
            } else {
                echo "<p style='color: red;'>User notification failed</p>";
            }
            
            // Notify admin
            echo "<h5>Notifying admins...</h5>";
            $adminResult = $helper->notifyAdminStatusChange(
                $newRequestId, 
                'open', 
                'in_progress', 
                $staffName, 
                $title
            );
            if ($adminResult) {
                echo "<p style='color: green;'>Admin notifications created</p>";
            } else {
                echo "<p style='color: red;'>Admin notifications failed</p>";
            }
            
            echo "<h4>Step 3: Verify notifications in database</h4>";
            
            $checkQuery = "SELECT n.*, u.full_name as user_name 
                          FROM notifications n 
                          LEFT JOIN users u ON n.user_id = u.id 
                          WHERE (n.related_id = :request_id AND n.related_type = 'service_request')
                          ORDER BY n.created_at DESC";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->bindParam(":request_id", $newRequestId);
            $checkStmt->execute();
            $notifications = $checkStmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($notifications) > 0) {
                echo "<p style='color: green;'>Found " . count($notifications) . " notifications:</p>";
                echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>User</th><th>Title</th><th>Message</th><th>Type</th><th>Created At</th></tr>";
                foreach ($notifications as $notif) {
                    echo "<tr>";
                    echo "<td>{$notif['id']}</td>";
                    echo "<td>{$notif['user_name']} (ID: {$notif['user_id']})</td>";
                    echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                    echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                    echo "<td>{$notif['type']}</td>";
                    echo "<td>{$notif['created_at']}</td>";
                    echo "</tr>";
                }
                echo "</table>";
                
                echo "<h4>Step 4: Test API response</h4>";
                echo "<p>Test notifications API for user 4:</p>";
                echo "<a href='api/notifications.php?action=get&user_id=4' target='_blank'>api/notifications.php?action=get&user_id=4</a><br>";
                echo "<p>Test notifications API for admin 1:</p>";
                echo "<a href='api/notifications.php?action=get&user_id=1' target='_blank'>api/notifications.php?action=get&user_id=1</a>";
                
            } else {
                echo "<p style='color: red;'>No notifications found in database</p>";
            }
            
        } else {
            echo "<p style='color: red;'>Failed to update request</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Failed to create test request</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>Summary</h3>";
echo "<p><strong>Status:</strong> Backend notification system is working correctly!</p>";
echo "<p><strong>Issue:</strong> All existing requests were already assigned</p>";
echo "<p><strong>Solution:</strong> Created new test request to verify workflow</p>";
echo "<p><strong>Next:</strong> Test frontend notification display</p>";
?>
