<?php
require_once 'config/database.php';

echo "<h1>Database Tables Check for Notifications</h1>";

try {
    $db = getDatabaseConnection();
    echo "✅ Database connection successful<br><br>";
    
    // Check notifications table
    echo "<h2>1. Notifications Table</h2>";
    $stmt = $db->prepare("DESCRIBE notifications");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Check count of notifications
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM notifications");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Total notifications: {$count}</p>";
    
    // Check users table for roles
    echo "<h2>2. Users Table (Roles)</h2>";
    $stmt = $db->prepare("SELECT id, username, full_name, role FROM users ORDER BY role");
    $stmt->execute();
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th></tr>";
    foreach ($users as $user) {
        echo "<tr>";
        echo "<td>{$user['id']}</td>";
        echo "<td>{$user['username']}</td>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Count by role
    $stmt = $db->prepare("SELECT role, COUNT(*) as count FROM users GROUP BY role");
    $stmt->execute();
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Role Summary:</h3>";
    foreach ($roles as $role) {
        echo "- {$role['role']}: {$role['count']} users<br>";
    }
    
    // Check service_requests table
    echo "<h2>3. Service Requests Table</h2>";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM service_requests");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Total service requests: {$count}</p>";
    
    if ($count > 0) {
        $stmt = $db->prepare("SELECT id, title, user_id, status FROM service_requests LIMIT 5");
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h3>Sample Requests:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>User ID</th><th>Status</th></tr>";
        foreach ($requests as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($req['title'], 0, 50)) . "</td>";
            echo "<td>{$req['user_id']}</td>";
            echo "<td>{$req['status']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Check reject_requests table
    echo "<h2>4. Reject Requests Table</h2>";
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM reject_requests");
    $stmt->execute();
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "<p>Total reject requests: {$count}</p>";
    
    // Test notification creation directly
    echo "<h2>5. Test Direct Notification Creation</h2>";
    require_once 'lib/NotificationHelper.php';
    $notificationHelper = new NotificationHelper();
    
    $result = $notificationHelper->createNotification(
        1, // Admin user ID
        "Test Direct Notification",
        "This is a test notification created directly",
        "info",
        1, // Test request ID
        "service_request"
    );
    
    if ($result) {
        echo "✅ Direct notification creation successful<br>";
        
        // Verify
        $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 1");
        $stmt->execute();
        $notif = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "✅ Found notification: {$notif['title']} (ID: {$notif['id']})<br>";
    } else {
        echo "❌ Direct notification creation failed<br>";
    }
    
    echo "<h2>✅ Database Check Complete!</h2>";
    
} catch (Exception $e) {
    echo "❌ Database Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: " . $e->getTraceAsString() . "<br>";
}
?>
