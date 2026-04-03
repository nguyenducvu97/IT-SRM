<?php
// Micro debug for getUsersByRole method
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Micro Debug: getUsersByRole</h2>";

try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    echo "<p>✅ Database connected</p>";
    
    require_once 'lib/ServiceRequestNotificationHelper.php';
    $notificationHelper = new ServiceRequestNotificationHelper();
    echo "<p>✅ NotificationHelper loaded</p>";
    
    echo "<h3>Testing getUsersByRole directly...</h3>";
    
    // Test the method
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    echo "<p>Method executed</p>";
    
    echo "<p>Result count: " . count($adminUsers) . "</p>";
    
    if (!empty($adminUsers)) {
        echo "<h4>Results:</h4>";
        echo "<pre>";
        var_dump($adminUsers);
        echo "</pre>";
    } else {
        echo "<p style='color: orange;'>⚠️ Empty result</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ ERROR: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h3>Direct SQL Test</h3>";
try {
    $sql = "SELECT id, username, full_name FROM users WHERE role IN (?)";
    echo "<p>SQL: $sql</p>";
    
    $stmt = $db->prepare($sql);
    echo "<p>✅ SQL prepared</p>";
    
    $stmt->execute(['admin']);
    echo "<p>✅ SQL executed</p>";
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<p>Direct query result count: " . count($users) . "</p>";
    
    if (!empty($users)) {
        echo "<pre>";
        var_dump($users);
        echo "</pre>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Direct SQL ERROR: " . $e->getMessage() . "</p>";
}

echo "<h2>✅ Micro Debug Complete</h2>";
?>
