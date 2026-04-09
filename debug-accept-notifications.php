<?php
echo "<h2>Debug Accept Request Notifications</h2>";

// Check if ServiceRequestNotificationHelper exists
echo "<h3>1. Check ServiceRequestNotificationHelper</h3>";
$helperPath = __DIR__ . '/lib/ServiceRequestNotificationHelper.php';
if (file_exists($helperPath)) {
    echo "<p style='color: green;'>&#10004; ServiceRequestNotificationHelper.php exists</p>";
    
    // Check if class exists
    require_once $helperPath;
    if (class_exists('ServiceRequestNotificationHelper')) {
        echo "<p style='color: green;'>&#10004; ServiceRequestNotificationHelper class exists</p>";
    } else {
        echo "<p style='color: red;'>&#10027; ServiceRequestNotificationHelper class NOT found</p>";
    }
} else {
    echo "<p style='color: red;'>&#10027; ServiceRequestNotificationHelper.php NOT found</p>";
}

// Check database connection
echo "<h3>2. Check Database Connection</h3>";
try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    if ($db) {
        echo "<p style='color: green;'>&#10004; Database connection successful</p>";
    } else {
        echo "<p style='color: red;'>&#10027; Database connection failed</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>&#10027; Database error: " . $e->getMessage() . "</p>";
}

// Check notification tables
echo "<h3>3. Check Notification Tables</h3>";
if ($db) {
    try {
        $tables = ['notifications', 'users', 'service_requests'];
        foreach ($tables as $table) {
            $stmt = $db->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() > 0) {
                echo "<p style='color: green;'>&#10004; Table '$table' exists</p>";
            } else {
                echo "<p style='color: red;'>&#10027; Table '$table' NOT found</p>";
            }
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>&#10027; Error checking tables: " . $e->getMessage() . "</p>";
    }
}

// Check recent notifications
echo "<h3>4. Check Recent Notifications</h3>";
if ($db) {
    try {
        $stmt = $db->prepare("SELECT * FROM notifications ORDER BY created_at DESC LIMIT 10");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($notifications) > 0) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>ID</th><th>User ID</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th></tr>";
            foreach ($notifications as $notif) {
                echo "<tr>";
                echo "<td>{$notif['id']}</td>";
                echo "<td>{$notif['user_id']}</td>";
                echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                echo "<td>{$notif['type']}</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p style='color: orange;'>&#9888; No notifications found in database</p>";
        }
    } catch (Exception $e) {
        echo "<p style='color: red;'>&#10027; Error checking notifications: " . $e->getMessage() . "</p>";
    }
}

// Check users
echo "<h3>5. Check Users in System</h3>";
if ($db) {
    try {
        $stmt = $db->prepare("SELECT id, username, full_name, role FROM users ORDER BY role, username");
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th></tr>";
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['username']}</td>";
            echo "<td>" . htmlspecialchars($user['full_name']) . "</td>";
            echo "<td><strong>{$user['role']}</strong></td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>&#10027; Error checking users: " . $e->getMessage() . "</p>";
    }
}

// Check recent service requests
echo "<h3>6. Check Recent Service Requests</h3>";
if ($db) {
    try {
        $stmt = $db->prepare("SELECT id, title, status, assigned_to, user_id FROM service_requests ORDER BY created_at DESC LIMIT 5");
        $stmt->execute();
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th><th>User ID</th></tr>";
        foreach ($requests as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>" . htmlspecialchars($req['title']) . "</td>";
            echo "<td><strong>{$req['status']}</strong></td>";
            echo "<td>{$req['assigned_to']}</td>";
            echo "<td>{$req['user_id']}</td>";
            echo "</tr>";
        }
        echo "</table>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>&#10027; Error checking requests: " . $e->getMessage() . "</p>";
    }
}

// Test notification creation directly
echo "<h3>7. Test Direct Notification Creation</h3>";
if ($db && class_exists('ServiceRequestNotificationHelper')) {
    try {
        $notificationHelper = new ServiceRequestNotificationHelper();
        
        // Test with a sample request
        $testRequestId = 1;
        $testUserId = 1;
        $testStaffName = "Test Staff";
        
        echo "<p>Testing notifyUserRequestInProgress...</p>";
        $result1 = $notificationHelper->notifyUserRequestInProgress($testRequestId, $testUserId, $testStaffName);
        echo "<p>Result: " . ($result1 ? "SUCCESS" : "FAILED") . "</p>";
        
        echo "<p>Testing notifyAdminStatusChange...</p>";
        $result2 = $notificationHelper->notifyAdminStatusChange($testRequestId, 'open', 'in_progress', $testStaffName, 'Test Request');
        echo "<p>Result: " . ($result2 ? "SUCCESS" : "FAILED") . "</p>";
        
        echo "<p>Testing notifyStaffAdminApproved...</p>";
        $result3 = $notificationHelper->notifyStaffAdminApproved($testRequestId, 'Test Request', $testStaffName);
        echo "<p>Result: " . ($result3 ? "SUCCESS" : "FAILED") . "</p>";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>&#10027; Error testing notifications: " . $e->getMessage() . "</p>";
    }
}

echo "<h3>8. Recommendations</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<ul>";
echo "<li>If notifications table is empty, check if NotificationHelper::createNotification() is working</li>";
echo "<li>If users table is empty, create test users first</li>";
echo "<li>If ServiceRequestNotificationHelper fails, check its dependencies</li>";
echo "<li>Check browser console for JavaScript errors when accepting requests</li>";
echo "<li>Check server error logs for PHP errors</li>";
echo "</ul>";
echo "</div>";
?>
