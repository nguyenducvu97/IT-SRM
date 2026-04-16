<?php
require_once 'config/database.php';
require_once 'config/session.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

echo "<h1>Test Reject Request Notification</h1>";

try {
    // Mock session
    $_SESSION['user_id'] = 2; // Staff user
    $_SESSION['role'] = 'staff';
    $_SESSION['full_name'] = 'Test Staff';
    $_SESSION['username'] = 'staff';
    
    $notificationHelper = new ServiceRequestNotificationHelper();
    echo "✅ ServiceRequestNotificationHelper loaded<br>";
    
    // Test with a real request ID (change this to a real request ID in your database)
    $testRequestId = 1;
    $rejectReason = "Test rejection reason - violates policy";
    $staffName = "Test Staff";
    $requestTitle = "Test Request Title";
    
    echo "📝 Testing with:<br>";
    echo "- Request ID: {$testRequestId}<br>";
    echo "- Reject Reason: {$rejectReason}<br>";
    echo "- Staff Name: {$staffName}<br>";
    echo "- Request Title: {$requestTitle}<br><br>";
    
    // Test getting admin users
    echo "🔍 Checking admin users...<br>";
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    echo "✅ Found " . count($adminUsers) . " admin users:<br>";
    foreach ($adminUsers as $admin) {
        echo "  - ID: {$admin['id']}, Name: {$admin['full_name']}<br>";
    }
    echo "<br>";
    
    // Test the actual notification
    echo "📢 Creating reject request notification...<br>";
    $result = $notificationHelper->notifyAdminRejectionRequest(
        $testRequestId, 
        $rejectReason, 
        $staffName, 
        $requestTitle
    );
    
    if ($result) {
        echo "✅ Reject request notification created successfully!<br>";
        
        // Verify in database
        $db = getDatabaseConnection();
        $stmt = $db->prepare("
            SELECT * FROM notifications 
            WHERE title = 'Yêu cầu từ chối cần xác nhận' 
            ORDER BY created_at DESC 
            LIMIT 1
        ");
        $stmt->execute();
        $notification = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($notification) {
            echo "✅ Found notification in database:<br>";
            echo "  - ID: {$notification['id']}<br>";
            echo "  - User ID: {$notification['user_id']}<br>";
            echo "  - Title: {$notification['title']}<br>";
            echo "  - Message: " . substr($notification['message'], 0, 100) . "...<br>";
            echo "  - Created: {$notification['created_at']}<br>";
        } else {
            echo "❌ Notification not found in database<br>";
        }
    } else {
        echo "❌ Failed to create reject request notification<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
