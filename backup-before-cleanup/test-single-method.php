<?php
echo "<h1>Test Single Failed Method</h1>";

require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

try {
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    echo "<h3>Testing notifyStaffNewRequest directly</h3>";
    
    $test_request_id = 777;
    $test_title = "Single Test Request";
    $test_requester = "Single Test User";
    $test_category = "Single Test Category";
    
    echo "<p>Parameters:</p>";
    echo "<ul>";
    echo "<li>request_id: {$test_request_id}</li>";
    echo "<li>title: {$test_title}</li>";
    echo "<li>requester: {$test_requester}</li>";
    echo "<li>category: {$test_category}</li>";
    echo "</ul>";
    
    // Check getUsersByRole first
    echo "<h4>Step 1: Check getUsersByRole</h4>";
    $staff_users = $notificationHelper->getUsersByRole(['staff']);
    echo "<p>Staff users count: " . count($staff_users) . "</p>";
    
    if (empty($staff_users)) {
        echo "<p style='color: red;'>❌ No staff users found!</p>";
    } else {
        echo "<p style='color: green;'>✅ Staff users found</p>";
        foreach ($staff_users as $staff) {
            echo "<pre>";
            echo "ID: {$staff['id']}\n";
            echo "Username: {$staff['username']}\n";
            echo "Full Name: {$staff['full_name']}\n";
            echo "Email: {$staff['email']}\n";
            echo "Role: {$staff['role']}\n";
            echo "</pre>";
        }
    }
    
    // Test the method
    echo "<h4>Step 2: Call notifyStaffNewRequest</h4>";
    
    $start_time = microtime(true);
    $result = $notificationHelper->notifyStaffNewRequest(
        $test_request_id,
        $test_title,
        $test_requester,
        $test_category
    );
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    echo "<p>Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . " ({$execution_time}ms)</p>";
    
    // Check notifications created
    echo "<h4>Step 3: Check notifications created</h4>";
    
    $db = (new Database())->getConnection();
    $notification_query = "SELECT COUNT(*) as total FROM notifications WHERE related_id = ? AND title LIKE '%Yêu cầu mới cần xử lý%'";
    $notification_stmt = $db->prepare($notification_query);
    $notification_stmt->execute([$test_request_id]);
    $notification_count = $notification_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Notifications created for request #{$test_request_id}: {$notification_count['total']}</p>";
    
    // Show recent notifications for staff
    if (!empty($staff_users)) {
        $staff_id = $staff_users[0]['id'];
        $recent_query = "SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT 5";
        $recent_stmt = $db->prepare($recent_query);
        $recent_stmt->execute([$staff_id]);
        $recent_notifications = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h5>Recent notifications for staff user:</h5>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
        echo "</tr>";
        
        foreach ($recent_notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
?>
