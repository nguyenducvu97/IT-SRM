<?php
// Ultra simple debug - step by step
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Debug Step 1: Database Connection</h2>";
try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    echo "<p>✅ Database connection: SUCCESS</p>";
} catch (Exception $e) {
    echo "<p>❌ Database connection: FAILED - " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>Debug Step 2: Admin Users</h2>";
try {
    $adminCheck = $db->prepare("SELECT id, username, full_name FROM users WHERE role = 'admin'");
    $adminCheck->execute();
    $admins = $adminCheck->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>✅ Found " . count($admins) . " admin users</p>";
    if (!empty($admins)) {
        foreach ($admins as $admin) {
            echo "<li>ID: {$admin['id']}, Name: {$admin['full_name']}</li>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Admin users query: FAILED - " . $e->getMessage() . "</p>";
}

echo "<h2>Debug Step 3: ServiceRequestNotificationHelper Class</h2>";
try {
    require_once 'lib/ServiceRequestNotificationHelper.php';
    $notificationHelper = new ServiceRequestNotificationHelper();
    echo "<p>✅ ServiceRequestNotificationHelper: LOADED</p>";
} catch (Exception $e) {
    echo "<p>❌ ServiceRequestNotificationHelper: FAILED - " . $e->getMessage() . "</p>";
    exit;
}

echo "<h2>Debug Step 4: getUsersByRole Method</h2>";
try {
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    echo "<p>✅ getUsersByRole: Found " . count($adminUsers) . " users</p>";
    if (!empty($adminUsers)) {
        echo "<pre>" . print_r($adminUsers, true) . "</pre>";
    }
} catch (Exception $e) {
    echo "<p>❌ getUsersByRole: FAILED - " . $e->getMessage() . "</p>";
}

echo "<h2>Debug Step 5: Service Requests</h2>";
try {
    $requestCheck = $db->prepare("SELECT id, title FROM service_requests ORDER BY id DESC LIMIT 1");
    $requestCheck->execute();
    $latestRequest = $requestCheck->fetch(PDO::FETCH_ASSOC);
    if ($latestRequest) {
        echo "<p>✅ Latest request: ID {$latestRequest['id']} - {$latestRequest['title']}</p>";
    } else {
        echo "<p>⚠️ No service requests found</p>";
        exit;
    }
} catch (Exception $e) {
    echo "<p>❌ Service requests query: FAILED - " . $e->getMessage() . "</p>";
}

echo "<h2>Debug Step 6: Test Notification</h2>";
try {
    $result = $notificationHelper->notifyAdminRejectionRequest(
        $latestRequest['id'], 
        "Test reason", 
        "Test Staff", 
        $latestRequest['title']
    );
    echo "<p>✅ notifyAdminRejectionRequest: " . ($result ? "SUCCESS" : "FAILED") . "</p>";
} catch (Exception $e) {
    echo "<p>❌ notifyAdminRejectionRequest: FAILED - " . $e->getMessage() . "</p>";
}

echo "<h2>Debug Step 7: Check Notifications Table</h2>";
try {
    $notifCheck = $db->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = 1");
    $notifCheck->execute();
    $count = $notifCheck->fetch(PDO::FETCH_ASSOC);
    echo "<p>✅ Admin notifications count: {$count['total']}</p>";
    
    $recentNotifs = $db->prepare("SELECT id, title, created_at FROM notifications WHERE user_id = 1 ORDER BY created_at DESC LIMIT 3");
    $recentNotifs->execute();
    $notifications = $recentNotifs->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Recent notifications:</p>";
    foreach ($notifications as $notif) {
        echo "<li>ID: {$notif['id']}, Title: {$notif['title']}, Created: {$notif['created_at']}</li>";
    }
} catch (Exception $e) {
    echo "<p>❌ Notifications check: FAILED - " . $e->getMessage() . "</p>";
}

echo "<h2>✅ Debug Complete!</h2>";
?>
