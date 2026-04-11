<?php
require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "=== TEST FINAL SCENARIO ===" . PHP_EOL;

try {
    $pdo = getDatabaseConnection();
    
    // Clear existing notifications
    $clear_stmt = $pdo->prepare("DELETE FROM notifications WHERE message LIKE '%Final Scenario%'");
    $clear_stmt->execute();
    
    // Use existing users
    $regular_user_id = 4; // ndvu
    $staff_user_id = 2;   // staff1
    $admin_user_id = 1;   // admin
    
    echo "Test scenario:" . PHP_EOL;
    echo "- Regular User ID: {$regular_user_id}" . PHP_EOL;
    echo "- Staff User ID: {$staff_user_id}" . PHP_EOL;
    echo "- Admin User ID: {$admin_user_id}" . PHP_EOL;
    echo PHP_EOL;
    
    // Create a test request by regular user
    $create_stmt = $pdo->prepare("
        INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at, updated_at) 
        VALUES (?, 'Final Scenario Test', 'Test request for final scenario', 1, 'medium', 'open', NOW(), NOW())
    ");
    $create_stmt->execute([$regular_user_id]);
    $request_id = $pdo->lastInsertId();
    
    echo "Created request #{$request_id} by regular user (user_id = {$regular_user_id})" . PHP_EOL;
    
    // Update request to in_progress and assign to staff
    $update_stmt = $pdo->prepare("
        UPDATE service_requests 
        SET assigned_to = ?, status = 'in_progress', assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
        WHERE id = ?
    ");
    $update_stmt->execute([$staff_user_id, $request_id]);
    
    echo "Staff (user_id = {$staff_user_id}) accepted request #{$request_id}" . PHP_EOL;
    
    // Get request details
    $request_query = "SELECT sr.*, u.full_name as requester_name, staff.full_name as assigned_name 
                     FROM service_requests sr 
                     LEFT JOIN users u ON sr.user_id = u.id 
                     LEFT JOIN users staff ON sr.assigned_to = staff.id 
                     WHERE sr.id = ?";
    $request_stmt = $pdo->prepare($request_query);
    $request_stmt->execute([$request_id]);
    $request_data = $request_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Request details:" . PHP_EOL;
    echo "- Requester user_id: {$request_data['user_id']}" . PHP_EOL;
    echo "- Assigned staff user_id: {$request_data['assigned_to']}" . PHP_EOL;
    echo "- Requester name: {$request_data['requester_name']}" . PHP_EOL;
    echo "- Assigned name: {$request_data['assigned_name']}" . PHP_EOL;
    echo PHP_EOL;
    
    // Send notifications with detailed logging
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    echo "=== SENDING NOTIFICATIONS ===" . PHP_EOL;
    
    // 1. Notify user
    echo "1. Sending user notification to user_id: {$request_data['user_id']}" . PHP_EOL;
    $result1 = $notificationHelper->notifyUserRequestInProgress(
        $request_id,
        $request_data['user_id'],
        $request_data['assigned_name']
    );
    echo "   User notification result: " . ($result1 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // 2. Notify admin
    echo "2. Sending admin notification" . PHP_EOL;
    $result2 = $notificationHelper->notifyAdminStatusChange(
        $request_id,
        'open',
        'in_progress',
        $request_data['assigned_name'],
        $request_data['title']
    );
    echo "   Admin notification result: " . ($result2 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    echo PHP_EOL;
    
    // Check created notifications
    $notif_stmt = $pdo->prepare("
        SELECT id, user_id, title, message, type, created_at 
        FROM notifications 
        WHERE (message LIKE '%Final Scenario Test%' OR message LIKE '%Yêu câu #{$request_id}%') 
        ORDER BY created_at DESC
    ");
    $notif_stmt->execute();
    $notifications = $notif_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "=== NOTIFICATIONS CREATED ===" . PHP_EOL;
    echo "Total notifications: " . count($notifications) . PHP_EOL;
    echo PHP_EOL;
    
    foreach ($notifications as $notif) {
        $user_type = "Unknown";
        if ($notif['user_id'] == $regular_user_id) {
            $user_type = "Regular User";
        } elseif ($notif['user_id'] == $admin_user_id) {
            $user_type = "Admin";
        } elseif ($notif['user_id'] == $staff_user_id) {
            $user_type = "Staff";
        }
        
        echo "Notification ID: {$notif['id']}" . PHP_EOL;
        echo "User ID: {$notif['user_id']} ({$user_type})" . PHP_EOL;
        echo "Title: {$notif['title']}" . PHP_EOL;
        echo "Message: " . $notif['message'] . PHP_EOL;
        echo "---" . PHP_EOL;
    }
    
    echo PHP_EOL;
    
    // Analysis
    $user_notifications = array_filter($notifications, function($notif) use ($regular_user_id) {
        return $notif['user_id'] == $regular_user_id;
    });
    
    $admin_notifications = array_filter($notifications, function($notif) use ($admin_user_id) {
        return $notif['user_id'] == $admin_user_id;
    });
    
    echo "=== ANALYSIS ===" . PHP_EOL;
    echo "User notifications: " . count($user_notifications) . PHP_EOL;
    echo "Admin notifications: " . count($admin_notifications) . PHP_EOL;
    
    if (count($user_notifications) == 1 && count($admin_notifications) == 1) {
        echo "SUCCESS: Both user and admin received notifications!" . PHP_EOL;
    } else {
        echo "ISSUE: Expected 1 notification each, got User: " . count($user_notifications) . ", Admin: " . count($admin_notifications) . PHP_EOL;
        
        if (count($user_notifications) == 0) {
            echo "  - User notification was not created despite SUCCESS result" . PHP_EOL;
        }
        if (count($admin_notifications) == 0) {
            echo "  - Admin notification was not created despite SUCCESS result" . PHP_EOL;
        }
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
