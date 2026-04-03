<?php
// Simple test with error reporting enabled
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Simple Test with Errors Enabled</h2>";

try {
    echo "<p>Loading database...</p>";
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    echo "<p>✅ Database loaded</p>";
    
    echo "<p>Loading NotificationHelper...</p>";
    require_once 'lib/NotificationHelper.php';
    echo "<p>✅ NotificationHelper loaded</p>";
    
    echo "<p>Loading ServiceRequestNotificationHelper...</p>";
    require_once 'lib/ServiceRequestNotificationHelper.php';
    echo "<p>✅ ServiceRequestNotificationHelper loaded</p>";
    
    echo "<p>Creating instance...</p>";
    $notificationHelper = new ServiceRequestNotificationHelper();
    echo "<p>✅ Instance created</p>";
    
    echo "<p>Testing getUsersByRole...</p>";
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    echo "<p>✅ getUsersByRole completed</p>";
    echo "<p>Found " . count($adminUsers) . " users</p>";
    
    if (!empty($adminUsers)) {
        echo "<h4>Results:</h4>";
        foreach ($adminUsers as $admin) {
            echo "<li>ID: {$admin['id']}, Name: {$admin['full_name']}</li>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ EXCEPTION: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
} catch (Error $e) {
    echo "<p style='color: red;'>❌ FATAL ERROR: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . " Line: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>✅ Test Complete</h2>";
?>
