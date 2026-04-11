<?php
// Test staff accept notification functionality
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

startSession();

echo "=== STAFF ACCEPT NOTIFICATION TEST ===" . PHP_EOL;

// Mock session as staff
$_SESSION['user_id'] = 2; // Assuming staff ID is 2
$_SESSION['username'] = 'staff1';
$_SESSION['full_name'] = 'Staff User 1';
$_SESSION['role'] = 'staff';

try {
    $notificationHelper = new ServiceRequestNotificationHelper();
    
    // Test 1: notifyUserRequestInProgress
    echo "Test 1: notifyUserRequestInProgress" . PHP_EOL;
    $result1 = $notificationHelper->notifyUserRequestInProgress(
        28, // Request ID
        1,  // User ID (admin who created request)
        'Staff User 1'
    );
    echo "Result: " . ($result1 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Test 2: notifyAdminStatusChange
    echo PHP_EOL . "Test 2: notifyAdminStatusChange" . PHP_EOL;
    $result2 = $notificationHelper->notifyAdminStatusChange(
        28,           // Request ID
        'open',       // Old status
        'in_progress', // New status
        'Staff User 1', // Staff name
        'Test Request Title' // Request title
    );
    echo "Result: " . ($result2 ? "SUCCESS" : "FAILED") . PHP_EOL;
    
    // Check created notifications
    echo PHP_EOL . "=== RECENT NOTIFICATIONS ===" . PHP_EOL;
    $pdo = getDatabaseConnection();
    $stmt = $pdo->query('SELECT id, user_id, title, message, is_read, created_at FROM notifications ORDER BY created_at DESC LIMIT 5');
    $notifications = $stmt->fetchAll();
    
    foreach ($notifications as $notif) {
        echo "- ID: {$notif['id']}, User ID: {$notif['user_id']}, Title: {$notif['title']}" . PHP_EOL;
        echo "  Message: {$notif['message']}" . PHP_EOL;
        echo "  Read: " . ($notif['is_read'] ? 'Yes' : 'No') . ", Created: {$notif['created_at']}" . PHP_EOL . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . PHP_EOL;
    echo "Stack trace: " . $e->getTraceAsString() . PHP_EOL;
}

echo "=== TEST COMPLETE ===" . PHP_EOL;
?>
