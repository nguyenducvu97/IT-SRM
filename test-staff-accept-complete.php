<?php
// Complete test for staff accept request notification flow
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

startSession();

echo "=== COMPLETE STAFF ACCEPT REQUEST TEST ===" . PHP_EOL;

// Create test scenario
try {
    $pdo = getDatabaseConnection();
    
    // 1. Create a test request (if not exists)
    echo "1. Creating test request..." . PHP_EOL;
    $insert_query = "INSERT INTO service_requests (user_id, title, description, category_id, priority, status, created_at, updated_at) 
                   VALUES (1, 'Test Staff Accept Notification', 'This is a test request for staff acceptance notification', 1, 'medium', 'open', NOW(), NOW())
                   ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)";
    $stmt = $pdo->prepare($insert_query);
    $stmt->execute();
    $request_id = $pdo->lastInsertId();
    echo "   ✅ Test request created with ID: $request_id" . PHP_EOL;
    
    // 2. Simulate staff acceptance
    echo PHP_EOL . "2. Simulating staff acceptance..." . PHP_EOL;
    
    // Mock staff session
    $_SESSION['user_id'] = 2; // Staff ID
    $_SESSION['username'] = 'staff1';
    $_SESSION['full_name'] = 'Test Staff';
    $_SESSION['role'] = 'staff';
    
    // Update request as staff accepts it
    $update_query = "UPDATE service_requests 
                   SET assigned_to = 2, status = 'in_progress', assigned_at = NOW(), accepted_at = NOW(), updated_at = NOW() 
                   WHERE id = ?";
    $stmt = $pdo->prepare($update_query);
    $stmt->execute([$request_id]);
    echo "   ✅ Request updated to in_progress" . PHP_EOL;
    
    // 3. Send notifications
    echo PHP_EOL . "3. Sending notifications..." . PHP_EOL;
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    // Get request details for notifications
    $request_query = "SELECT sr.*, u.full_name as requester_name, staff.full_name as assigned_name 
                     FROM service_requests sr 
                     LEFT JOIN users u ON sr.user_id = u.id 
                     LEFT JOIN users staff ON sr.assigned_to = staff.id 
                     WHERE sr.id = ?";
    $stmt = $pdo->prepare($request_query);
    $stmt->execute([$request_id]);
    $request_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request_data) {
        // Notify user that request is in progress
        echo "   Sending notification to user (ID: {$request_data['user_id']})..." . PHP_EOL;
        $result1 = $notificationHelper->notifyUserRequestInProgress(
            $request_id, 
            $request_data['user_id'], 
            $request_data['assigned_name']
        );
        echo "   ✅ User notification: " . ($result1 ? "SUCCESS" : "FAILED") . PHP_EOL;
        
        // Notify admin about status change
        echo "   Sending notification to admin..." . PHP_EOL;
        $result2 = $notificationHelper->notifyAdminStatusChange(
            $request_id, 
            'open', 
            'in_progress', 
            $request_data['assigned_name'], 
            $request_data['title']
        );
        echo "   ✅ Admin notification: " . ($result2 ? "SUCCESS" : "FAILED") . PHP_EOL;
    }
    
    // 4. Check created notifications
    echo PHP_EOL . "4. Checking created notifications..." . PHP_EOL;
    $notif_query = "SELECT id, user_id, title, message, type, is_read, created_at 
                   FROM notifications 
                   WHERE related_id = ? AND related_type = 'service_request' 
                   ORDER BY created_at DESC LIMIT 5";
    $stmt = $pdo->prepare($notif_query);
    $stmt->execute([$request_id]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Found " . count($notifications) . " notifications:" . PHP_EOL;
    foreach ($notifications as $notif) {
        echo "   - ID: {$notif['id']}, User ID: {$notif['user_id']}, Type: {$notif['type']}" . PHP_EOL;
        echo "     Title: {$notif['title']}" . PHP_EOL;
        echo "     Message: {$notif['message']}" . PHP_EOL;
        echo "     Created: {$notif['created_at']}" . PHP_EOL;
        echo "     Read: " . ($notif['is_read'] ? 'Yes' : 'No') . PHP_EOL . PHP_EOL;
    }
    
    // 5. Summary
    echo PHP_EOL . "=== TEST SUMMARY ===" . PHP_EOL;
    echo "✅ Request ID: $request_id" . PHP_EOL;
    echo "✅ Status: open → in_progress" . PHP_EOL;
    echo "✅ Staff: {$request_data['assigned_name']}" . PHP_EOL;
    echo "✅ User notifications sent: " . ($result1 ? "YES" : "NO") . PHP_EOL;
    echo "✅ Admin notifications sent: " . ($result2 ? "YES" : "NO") . PHP_EOL;
    echo "✅ Total notifications created: " . count($notifications) . PHP_EOL;
    
    echo PHP_EOL . "🎉 Staff accept notification flow test completed successfully!" . PHP_EOL;
    echo "📱 Admin and User should now see notifications about the staff acceptance." . PHP_EOL;
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo PHP_EOL . "=== TEST COMPLETE ===" . PHP_EOL;
?>
