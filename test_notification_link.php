<?php
require_once 'config/database.php';

// Test notification creation and link generation
try {
    $pdo = new Database();
    $db = $pdo->getConnection();
    
    if ($db === null) {
        die("Database connection failed");
    }
    
    // Test data
    $service_request_id = 71;
    $title = "Yêu cầu hỗ trợ mới cho yêu cầu #71";
    $message = "System Administrator yêu cầu hỗ trợ cho: Lỗi mạng";
    $type = 'warning';
    $relatedId = $service_request_id;
    $relatedType = 'request';
    
    echo "=== TEST NOTIFICATION CREATION ===\n";
    echo "Service Request ID: $service_request_id\n";
    echo "Title: $title\n";
    echo "Message: $message\n";
    echo "Related ID: $relatedId\n";
    echo "Related Type: $relatedType\n\n";
    
    // Test NotificationHelper
    require_once 'lib/NotificationHelper.php';
    $notificationHelper = new NotificationHelper();
    
    // Create notification
    $result = $notificationHelper->createNotification($db, 1, $title, $message, $type, $relatedId, $relatedType);
    
    echo "=== NOTIFICATION CREATED ===\n";
    echo "Result: " . ($result ? "SUCCESS" : "FAILED") . "\n\n";
    
    // Test email generation
    echo "=== EMAIL LINK GENERATION ===\n";
    $emailSent = $notificationHelper->sendNotificationEmail(1, $title, $message, $type, $relatedId, $relatedType);
    
    echo "Email sent: " . ($emailSent ? "YES" : "NO") . "\n";
    
    // Check the notification in database
    echo "\n=== DATABASE CHECK ===\n";
    $stmt = $db->prepare("SELECT * FROM notifications WHERE title = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$title]);
    $notification = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($notification) {
        echo "Notification ID: " . $notification['id'] . "\n";
        echo "Related ID: " . $notification['related_id'] . "\n";
        echo "Related Type: " . $notification['related_type'] . "\n";
        echo "Created at: " . $notification['created_at'] . "\n";
    } else {
        echo "No notification found in database\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
?>
