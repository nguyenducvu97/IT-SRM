<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>🔍 COMPLETE NOTIFICATION SYSTEM TEST</h2>";
    
    // Start session as admin for testing
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    
    echo "<div style='background: #e3f2fd; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Test Session:</strong> Admin logged in for testing";
    echo "</div>";
    
    // 1. Check Notification Tables
    echo "<h3>📊 1. Database Tables Check</h3>";
    
    $tables = ['notifications', 'service_requests', 'users', 'reject_requests', 'support_requests'];
    
    foreach ($tables as $table) {
        $table_check = $db->query("SHOW TABLES LIKE '$table'");
        if ($table_check->rowCount() > 0) {
            $count = $db->query("SELECT COUNT(*) as count FROM $table")->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<p>✅ Table '$table': $count records</p>";
        } else {
            echo "<p>❌ Table '$table': NOT FOUND</p>";
        }
    }
    
    // 2. Test Notification Helper Classes
    echo "<h3>🔧 2. Notification Helper Classes</h3>";
    
    $helper_files = [
        'lib/NotificationHelper.php' => 'NotificationHelper',
        'lib/ServiceRequestNotificationHelper.php' => 'ServiceRequestNotificationHelper'
    ];
    
    foreach ($helper_files as $file => $class) {
        if (file_exists($file)) {
            echo "<p>✅ File '$file': EXISTS</p>";
            try {
                require_once $file;
                if (class_exists($class)) {
                    echo "<p>✅ Class '$class': EXISTS</p>";
                    
                    // Check key methods
                    $methods = ['notifyUser', 'notifyAdmins', 'notifyStaff'];
                    foreach ($methods as $method) {
                        if (method_exists($class, $method)) {
                            echo "<p>✅ Method '$class::$method': EXISTS</p>";
                        } else {
                            echo "<p>❌ Method '$class::$method': NOT FOUND</p>";
                        }
                    }
                } else {
                    echo "<p>❌ Class '$class': NOT FOUND</p>";
                }
            } catch (Exception $e) {
                echo "<p>❌ Error loading '$file': " . $e->getMessage() . "</p>";
            }
        } else {
            echo "<p>❌ File '$file': NOT FOUND</p>";
        }
    }
    
    // 3. Check Recent Notifications
    echo "<h3>📬 3. Recent Notifications Analysis</h3>";
    
    $recent_notifications = $db->query("
        SELECT n.*, u.full_name as recipient_name, ur.username as recipient_username
        FROM notifications n
        LEFT JOIN users u ON n.user_id = u.id
        LEFT JOIN users ur ON n.recipient_id = ur.id
        ORDER BY n.created_at DESC
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recent_notifications) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Recipient</th><th>Title</th><th>Type</th><th>Request ID</th><th>Created</th></tr>";
        
        foreach ($recent_notifications as $notif) {
            echo "<tr>";
            echo "<td>{$notif['id']}</td>";
            echo "<td>" . ($notif['recipient_name'] ?: 'User ' . $notif['user_id']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($notif['title'], 0, 30)) . "...</td>";
            echo "<td>{$notif['type']}</td>";
            echo "<td>" . ($notif['service_request_id'] ?: 'N/A') . "</td>";
            echo "<td>{$notif['created_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No recent notifications found</p>";
    }
    
    // 4. Test Each Notification Scenario
    echo "<h3>🧪 4. Notification Scenarios Test</h3>";
    
    // 4.1 User Notifications
    echo "<h4>4.1 User Notifications</h4>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Scenario</th><th>Expected</th><th>Test</th></tr>";
    
    $user_scenarios = [
        'Request → In Progress' => 'User gets notification when staff accepts',
        'Request → Pending Approval' => 'User gets notification when admin needs to approve',
        'Request → Resolved' => 'User gets notification to check results and rate',
        'Request → Rejected' => 'User gets notification with rejection reason'
    ];
    
    foreach ($user_scenarios as $scenario => $description) {
        echo "<tr>";
        echo "<td>$scenario</td>";
        echo "<td>$description</td>";
        echo "<td><a href='test-user-notification.php?scenario=" . urlencode($scenario) . "'>Test</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4.2 Staff Notifications  
    echo "<h4>4.2 Staff Notifications</h4>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Scenario</th><th>Expected</th><th>Test</th></tr>";
    
    $staff_scenarios = [
        'New Request Created' => 'Staff gets notification of new work',
        'User Rates/Closes Request' => 'Staff gets rating and feedback',
        'Admin Approves Request' => 'Staff gets notification to start technical work',
        'Admin Rejects Request' => 'Staff gets notification to stop or explain'
    ];
    
    foreach ($staff_scenarios as $scenario => $description) {
        echo "<tr>";
        echo "<td>$scenario</td>";
        echo "<td>$description</td>";
        echo "<td><a href='test-staff-notification.php?scenario=" . urlencode($scenario) . "'>Test</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 4.3 Admin Notifications
    echo "<h4>4.3 Admin Notifications</h4>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Scenario</th><th>Expected</th><th>Test</th></tr>";
    
    $admin_scenarios = [
        'New Request Created' => 'Admin gets notification to monitor workload',
        'Staff Changes Status' => 'Admin gets notification to track progress',
        'Staff Escalates (Support Request)' => 'Admin gets notification to intervene',
        'Staff Rejects Request' => 'Admin gets notification for final approval'
    ];
    
    foreach ($admin_scenarios as $scenario => $description) {
        echo "<tr>";
        echo "<td>$scenario</td>";
        echo "<td>$description</td>";
        echo "<td><a href='test-admin-notification.php?scenario=" . urlencode($scenario) . "'>Test</a></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // 5. Check API Endpoints
    echo "<h3>🌐 5. API Endpoints Check</h3>";
    
    $api_endpoints = [
        'api/service_requests.php' => 'Main service requests API',
        'api/notifications.php' => 'Notifications API',
        'api/reject_requests.php' => 'Reject requests API',
        'api/support_requests.php' => 'Support requests API'
    ];
    
    foreach ($api_endpoints as $endpoint => $description) {
        if (file_exists($endpoint)) {
            echo "<p>✅ Endpoint '$endpoint': EXISTS - $description</p>";
        } else {
            echo "<p>❌ Endpoint '$endpoint': NOT FOUND</p>";
        }
    }
    
    // 6. Notification Settings
    echo "<h3>⚙️ 6. Notification Settings</h3>";
    
    // Check if users have notification preferences
    $notification_settings = $db->query("
        SELECT u.id, u.username, u.full_name, u.role,
               CASE WHEN u.email LIKE '%@%' THEN 'YES' ELSE 'NO' END as has_email
        FROM users u
        WHERE u.status = 'active'
        ORDER BY u.role, u.full_name
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>User</th><th>Role</th><th>Email</th><th>Can Receive</th></tr>";
    
    foreach ($notification_settings as $user) {
        echo "<tr>";
        echo "<td>{$user['full_name']}</td>";
        echo "<td>{$user['role']}</td>";
        echo "<td>{$user['has_email']}</td>";
        echo "<td>" . ($user['has_email'] === 'YES' ? '✅ Email + In-App' : '❌ No Email') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // 7. Summary and Recommendations
    echo "<h3>📋 7. System Status Summary</h3>";
    
    $total_notifications = $db->query("SELECT COUNT(*) as count FROM notifications")->fetch(PDO::FETCH_ASSOC)['count'];
    $total_users = $db->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch(PDO::FETCH_ASSOC)['count'];
    $total_requests = $db->query("SELECT COUNT(*) as count FROM service_requests")->fetch(PDO::FETCH_ASSOC)['count'];
    
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<h4>📊 System Metrics:</h4>";
    echo "<ul>";
    echo "<li><strong>Total Notifications:</strong> $total_notifications</li>";
    echo "<li><strong>Active Users:</strong> $total_users</li>";
    echo "<li><strong>Total Requests:</strong> $total_requests</li>";
    echo "</ul>";
    
    echo "<h4>🎯 Recommendations:</h4>";
    echo "<ul>";
    echo "<li>Test each notification scenario using the links above</li>";
    echo "<li>Verify email notifications are working</li>";
    echo "<li>Check real-time notification display in frontend</li>";
    echo "<li>Monitor notification delivery in production</li>";
    echo "</ul>";
    echo "</div>";
    
    echo "<h3>🚀 8. Quick Test Links</h3>";
    echo "<p><a href='create-test-notification.php'>Create Test Notification</a></p>";
    echo "<p><a href='test-notification-delivery.php'>Test Notification Delivery</a></p>";
    echo "<p><a href='check-notification-settings.php'>Check Notification Settings</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
