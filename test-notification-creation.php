<?php
// Test script to simulate creating a new request and check notifications
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "<h1>Test Notification Creation</h1>";

try {
    // Start session for testing
    startSession();
    
    // Simulate user session (user with ID 2)
    $_SESSION['user_id'] = 2;
    $_SESSION['username'] = 'testuser';
    $_SESSION['full_name'] = 'Test User';
    $_SESSION['role'] = 'user';
    
    echo "<h2>1. Session Info</h2>";
    echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
    echo "<p>Role: " . $_SESSION['role'] . "</p>";
    
    // Get database connection
    $db = getDatabaseConnection();
    
    echo "<h2>2. Creating Test Request</h2>";
    
    // Insert a test request
    $stmt = $db->prepare("
        INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $result = $stmt->execute([
        $_SESSION['user_id'],
        'Test Notification Request ' . date('H:i:s'),
        'This is a test request to check notification creation',
        1, // Network category
        'medium',
        'open'
    ]);
    
    if ($result) {
        $request_id = $db->lastInsertId();
        echo "<p>Test request created with ID: <strong>$request_id</strong></p>";
        
        echo "<h2>3. Creating Notifications</h2>";
        
        // Create notifications
        $notificationHelper = new ServiceRequestNotificationHelper();
        
        // Get request details
        $requestDetails = $notificationHelper->getRequestDetails($request_id);
        
        echo "<p>Request details loaded:</p>";
        echo "<ul>";
        echo "<li>Title: " . $requestDetails['title'] . "</li>";
        echo "<li>Requester: " . $requestDetails['requester_name'] . "</li>";
        echo "<li>Category: " . $requestDetails['category_name'] . "</li>";
        echo "</ul>";
        
        // Test staff notification
        $staffResult = $notificationHelper->notifyStaffNewRequest(
            $request_id,
            $requestDetails['title'],
            $requestDetails['requester_name'],
            $requestDetails['category_name']
        );
        echo "<p>Staff notification result: <strong>" . ($staffResult ? 'SUCCESS' : 'FAILED') . "</strong></p>";
        
        // Test admin notification
        $adminResult = $notificationHelper->notifyAdminNewRequest(
            $request_id,
            $requestDetails['title'],
            $requestDetails['requester_name'],
            $requestDetails['category_name']
        );
        echo "<p>Admin notification result: <strong>" . ($adminResult ? 'SUCCESS' : 'FAILED') . "</strong></p>";
        
        echo "<h2>4. Checking Created Notifications</h2>";
        
        // Check staff notifications (user_id 3 = staff)
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = 3 AND related_id = ?");
        $stmt->execute([$request_id]);
        $staffCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Staff notifications created: <strong>$staffCount</strong></p>";
        
        // Check admin notifications (user_id 1 = admin)
        $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = 1 AND related_id = ?");
        $stmt->execute([$request_id]);
        $adminCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
        echo "<p>Admin notifications created: <strong>$adminCount</strong></p>";
        
        // Show the actual notifications
        echo "<h2>5. Recent Notifications</h2>";
        $stmt = $db->prepare("
            SELECT n.*, u.username 
            FROM notifications n 
            LEFT JOIN users u ON n.user_id = u.id 
            WHERE n.related_id = ? 
            ORDER BY n.created_at DESC
        ");
        $stmt->execute([$request_id]);
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1'>";
        echo "<tr><th>User</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
        foreach ($notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['username']} ({$notif['user_id']})</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>{$notif['message']}</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        echo "<h2>6. Test API Endpoints</h2>";
        echo "<p><a href='api/notifications.php?action=list' target='_blank'>Check Staff Notifications (user_id=3)</a></p>";
        echo "<p><a href='api/notifications.php?action=count' target='_blank'>Check Notification Count</a></p>";
        
    } else {
        echo "<p style='color: red;'>Failed to create test request</p>";
    }
    
} catch (Exception $e) {
    echo "<h2>Error</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
