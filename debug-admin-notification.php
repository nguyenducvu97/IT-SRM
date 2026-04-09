<?php
echo "<h2>DEBUG ADMIN NOTIFICATION ISSUE</h2>";

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/lib/ServiceRequestNotificationHelper.php';

$db = getDatabaseConnection();
$notificationHelper = new ServiceRequestNotificationHelper();

echo "<h3>1. KIÊM TRA ADMIN USERS TRONG HÊ THÔNG</h3>";

try {
    $stmt = $db->prepare("SELECT id, username, full_name, role, status FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($adminUsers) > 0) {
        echo "<p style='color: green;'><strong>&#10004; Found " . count($adminUsers) . " admin users:</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Status</th>";
        echo "</tr>";
        
        foreach ($adminUsers as $admin) {
            echo "<tr>";
            echo "<td><strong>{$admin['id']}</strong></td>";
            echo "<td>{$admin['username']}</td>";
            echo "<td>" . htmlspecialchars($admin['full_name']) . "</td>";
            echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$admin['role']}</span></td>";
            echo "<td>" . ($admin['status'] ?? 'N/A') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'><strong>&#10027; No admin users found!</strong></p>";
        echo "<p>Không có admin trong database!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking admin users: " . $e->getMessage() . "</p>";
}

echo "<h3>2. KIÊM TRA NOTIFICATIONS CHO ADMIN</h3>";

try {
    // Kiêm tra notifications cho admin user
    $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                           LEFT JOIN users u ON n.user_id = u.id 
                           WHERE u.role = 'admin'
                           ORDER BY n.created_at DESC LIMIT 10");
    $stmt->execute();
    $adminNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($adminNotifications) > 0) {
        echo "<p style='color: green;'><strong>&#10004; Found " . count($adminNotifications) . " notifications for admin:</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>User</th><th>Title</th><th>Message</th><th>Type</th><th>Created</th>";
        echo "</tr>";
        
        foreach ($adminNotifications as $notif) {
            echo "<tr>";
            echo "<td><strong>{$notif['id']}</strong></td>";
            echo "<td>{$notif['username']}</td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
            echo "<td><span style='background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['type']}</span></td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'><strong>&#10027; No notifications found for admin!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking admin notifications: " . $e->getMessage() . "</p>";
}

echo "<h3>3. TEST TRUC TIÉP notifyAdminStatusChange FUNCTION</h3>";

// Test function
echo "<h4>Testing notifyAdminStatusChange()...</h4>";

try {
    $result = $notificationHelper->notifyAdminStatusChange(
        79, 
        'open', 
        'in_progress', 
        'John Smith', 
        'Test Request by Real User 2026-04-09 13:34:20'
    );
    
    echo "<p><strong>Function Result:</strong> " . ($result ? "SUCCESS" : "FAILED") . "</p>";
    
    if ($result) {
        echo "<p style='color: green;'>&#10004; Function executed successfully!</p>";
        
        // Check if notification was created
        $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                               LEFT JOIN users u ON n.user_id = u.id 
                               WHERE u.role = 'admin' AND n.title LIKE '%Thay%'
                               ORDER BY n.created_at DESC LIMIT 3");
        $stmt->execute();
        $newAdminNotifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($newAdminNotifications) > 0) {
            echo "<p style='color: green;'><strong>&#10004; Found " . count($newAdminNotifications) . " new admin notifications:</strong></p>";
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr style='background-color: #f0f0f0;'>";
            echo "<th>ID</th><th>User</th><th>Title</th><th>Message</th><th>Created</th>";
            echo "</tr>";
            
            foreach ($newAdminNotifications as $notif) {
                echo "<tr>";
                echo "<td><strong>{$notif['id']}</strong></td>";
                echo "<td>{$notif['username']}</td>";
                echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
                echo "<td>" . htmlspecialchars($notif['message']) . "</td>";
                echo "<td>{$notif['created_at']}</td>";
                echo "</tr>";
            }
            echo "</table>";
            
        } else {
            echo "<p style='color: red;'><strong>&#10027; No new admin notifications found!</strong></p>";
        }
    } else {
        echo "<p style='color: red;'>&#10027; Function failed!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error testing function: " . $e->getMessage() . "</p>";
}

echo "<h3>4. KIÊM TRA getUsersByRole FUNCTION</h3>";

echo "<h4>Testing getUsersByRole(['admin'])...</h4>";

try {
    $adminUsers = $notificationHelper->getUsersByRole(['admin']);
    
    if (count($adminUsers) > 0) {
        echo "<p style='color: green;'><strong>&#10004; Found " . count($adminUsers) . " admin users:</strong></p>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Username</th><th>Full Name</th></tr>";
        
        foreach ($adminUsers as $admin) {
            echo "<tr>";
            echo "<td>{$admin['id']}</td>";
            echo "<td>{$admin['username']}</td>";
            echo "<td>" . htmlspecialchars($admin['full_name']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'><strong>&#10027; getUsersByRole(['admin']) returned no users!</strong></p>";
        echo "<p>This is the problem - getUsersByRole function is not finding admin users!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error in getUsersByRole: " . $e->getMessage() . "</p>";
}

echo "<h3>5. KIÊM TRA REQUEST #79 NOTIFICATIONS</h3>";

try {
    $stmt = $db->prepare("SELECT n.*, u.username, u.role FROM notifications n 
                           LEFT JOIN users u ON n.user_id = u.id 
                           WHERE n.related_id = 79 AND n.related_type = 'service_request'
                           ORDER BY n.created_at DESC");
    $stmt->execute();
    $request79Notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($request79Notifications) > 0) {
        echo "<p style='color: green;'><strong>&#10004; Found " . count($request79Notifications) . " notifications for request #79:</strong></p>";
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background-color: #f0f0f0;'>";
        echo "<th>ID</th><th>User</th><th>Role</th><th>Title</th><th>Message</th><th>Type</th></tr>";
        
        foreach ($request79Notifications as $notif) {
            echo "<tr>";
            echo "<td><strong>{$notif['id']}</strong></td>";
            echo "<td>{$notif['username']}</td>";
            echo "<td><span style='background-color: #007bff; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['role']}</span></td>";
            echo "<td>" . htmlspecialchars($notif['title']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($notif['message'], 0, 50)) . "...</td>";
            echo "<td><span style='background-color: #28a745; color: white; padding: 2px 6px; border-radius: 3px;'>{$notif['type']}</span></td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Check specifically for admin notifications
        $adminNotifs = array_filter($request79Notifications, function($n) {
            return $n['role'] === 'admin';
        });
        
        if (count($adminNotifs) > 0) {
            echo "<p style='color: green;'><strong>&#10004; Found " . count($adminNotifs) . " admin notifications for request #79:</strong></p>";
            foreach ($adminNotifs as $notif) {
                echo "<p>- {$notif['title']}: " . htmlspecialchars($notif['message']) . "</p>";
            }
        } else {
            echo "<p style='color: red;'><strong>&#10027; No admin notifications found for request #79!</strong></p>";
            echo "<p>Admin không có notifications cho request #79!</p>";
        }
        
    } else {
        echo "<p style='color: red;'><strong>&#10027; No notifications found for request #79!</strong></p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error checking request #79 notifications: " . $e->getMessage() . "</p>";
}

echo "<h3>6. CONCLUSION</h3>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128204; ANALYSIS:</h4>";
echo "<p><strong>User received notifications:</strong> &#10004; Working</p>";
echo "<p><strong>Admin didn't receive notifications:</strong> &#10027; Issue identified</p>";
echo "<p><strong>Possible causes:</strong></p>";
echo "<ol>";
echo "<li>getUsersByRole(['admin']) không tìm admin users</li>";
echo "<li>notifyAdminStatusChange() không create notification</li>";
echo "<li>Database insert failed</li>";
echo "<li>Admin user ID không chính xác</li>";
echo "</ol>";
echo "</div>";

echo "<h3>7. SOLUTION</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128072; Steps to fix:</h4>";
echo "<ol>";
echo "<li><strong>1. Check getUsersByRole function:</strong> Ensure it finds admin users</li>";
echo "<li><strong>2. Check admin user data:</strong> Verify admin user exists and has correct role</li>";
echo "<li><strong>3. Test notification creation:</strong> Manually create admin notification</li>";
echo "<li><strong>4. Verify database insert:</strong> Check if notifications are saved</li>";
echo "<li><strong>5. Test frontend display:</strong> Check if admin can see notifications</li>";
echo "</ol>";
echo "</div>";
?>
