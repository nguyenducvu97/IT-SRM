<?php
require_once 'config/database.php';
require_once 'lib/NotificationHelper.php';

echo "<h2>🧪 TEST CREATE NOTIFICATION</h2>";

try {
    $db = getDatabaseConnection();
    $helper = new NotificationHelper($db);
    
    echo "<h3>✅ Classes Loaded Successfully</h3>";
    
    // Test 1: Check createNotification method signature
    echo "<h3>🔍 Method Signature Check:</h3>";
    
    $reflection = new ReflectionMethod('NotificationHelper', 'createNotification');
    $parameters = $reflection->getParameters();
    
    echo "<p><strong>createNotification parameters:</strong></p>";
    echo "<ul>";
    foreach ($parameters as $param) {
        $type = $param->getType() ? $param->getType()->getName() : 'mixed';
        $default = $param->isDefaultValueAvailable() ? ' = ' . var_export($param->getDefaultValue(), true) : '';
        echo "<li>\${$param->getName()}: $type$default</li>";
    }
    echo "</ul>";
    
    // Test 2: Check database columns
    echo "<h3>🗄️ Database Columns Check:</h3>";
    
    $columns_result = $db->query("SHOW COLUMNS FROM notifications");
    $columns = $columns_result->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td><strong>{$column['Field']}</strong></td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>" . ($column['Default'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test 3: Try createNotification with minimal parameters
    echo "<h3>🧪 Test 1: Minimal Parameters</h3>";
    
    try {
        $result1 = $helper->createNotification(
            1, // user_id
            'Test Notification 1',
            'This is a test message',
            'info'
        );
        
        if ($result1) {
            echo "<p>✅ Test 1: SUCCESS - Notification created</p>";
            
            // Get the inserted ID
            $last_id = $db->lastInsertId();
            echo "<p>📄 Inserted ID: $last_id</p>";
            
        } else {
            echo "<p>❌ Test 1: FAILED - No result returned</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Test 1: EXCEPTION - " . $e->getMessage() . "</p>";
        echo "<p><strong>Stack trace:</strong></p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // Test 4: Try with all parameters
    echo "<h3>🧪 Test 2: All Parameters (no email)</h3>";
    
    try {
        $result2 = $helper->createNotification(
            1, // user_id
            'Test Notification 2',
            'This is a test with all parameters',
            'success',
            82, // related_id
            'service_request', // related_type
            false // sendEmail = false
        );
        
        if ($result2) {
            echo "<p>✅ Test 2: SUCCESS - Full notification created</p>";
            
            $last_id2 = $db->lastInsertId();
            echo "<p>📄 Inserted ID: $last_id2</p>";
            
        } else {
            echo "<p>❌ Test 2: FAILED - No result returned</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Test 2: EXCEPTION - " . $e->getMessage() . "</p>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    
    // Test 5: Check recent notifications
    echo "<h3>📬 Recent Notifications Check:</h3>";
    
    $recent = $db->query("
        SELECT * FROM notifications 
        WHERE title LIKE 'Test Notification%' 
        ORDER BY created_at DESC 
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recent) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Related ID</th><th>Related Type</th><th>Created</th></tr>";
        
        foreach ($recent as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>{$notif['user_id']}</td>";
            echo "<td>{$notif['title']}</td>";
            echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 30)) . "...</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>" . ($notif['related_id'] ?: 'NULL') . "</td>";
            echo "<td>{$notif['related_type']}</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No test notifications found</p>";
    }
    
    // Test 6: Test notifyRole method
    echo "<h3>🧪 Test 3: notifyRole Method</h3>";
    
    try {
        $result3 = $helper->notifyRole(
            'admin', // role
            'Admin Test Notification',
            'This is a test for all admins',
            'warning',
            82, // related_id
            'service_request', // related_type
            false // sendEmail = false
        );
        
        if ($result3) {
            echo "<p>✅ Test 3: SUCCESS - Role notification created</p>";
        } else {
            echo "<p>❌ Test 3: FAILED - No result returned</p>";
        }
        
    } catch (Exception $e) {
        echo "<p>❌ Test 3: EXCEPTION - " . $e->getMessage() . "</p>";
    }
    
    echo "<h3>📋 Summary:</h3>";
    echo "<p>✅ Classes load correctly</p>";
    echo "<p>✅ Database connection works</p>";
    echo "<p>✅ Methods exist and have correct signatures</p>";
    echo "<p>🔍 Testing actual notification creation...</p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Fatal Error:</strong> " . $e->getMessage();
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
?>
