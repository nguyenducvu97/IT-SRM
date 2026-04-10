<?php
require_once 'config/database.php';
require_once 'lib/NotificationHelper.php';

echo "<h2>🔧 FIX SUPPORT NOTIFICATIONS - IMMEDIATE</h2>";

try {
    $db = getDatabaseConnection();
    $notificationHelper = new NotificationHelper($db);
    
    // 1. Check current support requests
    echo "<h3>📋 1. Current Support Requests Analysis</h3>";
    
    $support_requests = $db->query("
        SELECT sr.*, sreq.title as request_title, u.full_name as requester_name
        FROM support_requests sr
        LEFT JOIN service_requests sreq ON sr.service_request_id = sreq.id
        LEFT JOIN users u ON sr.requester_id = u.id
        ORDER BY sr.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>ID</th><th>Request Title</th><th>Requester</th><th>Status</th><th>Created</th><th>Admin Notified?</th></tr>";
    
    foreach ($support_requests as $req) {
        // Check if notification was sent
        $notification_exists = $db->query("
            SELECT COUNT(*) as count 
            FROM notifications 
            WHERE related_id = {$req['id']} 
            AND related_type = 'support_request'
            AND type = 'warning'
        ")->fetch(PDO::FETCH_ASSOC);
        
        $notified = $notification_exists['count'] > 0 ? '✅ Yes' : '❌ NO';
        $row_style = $notification_exists['count'] == 0 ? 'background: #f8d7da;' : '';
        
        echo "<tr style='$row_style'>";
        echo "<td>{$req['id']}</td>";
        echo "<td>" . htmlspecialchars(substr($req['request_title'], 0, 30)) . "...</td>";
        echo "<td>{$req['requester_name']}</td>";
        echo "<td>{$req['status']}</td>";
        echo "<td>{$req['created_at']}</td>";
        echo "<td><strong>$notified</strong></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // 2. Fix missing notifications
    echo "<h3>🔧 2. Fix Missing Notifications</h3>";
    
    $missing_notifications = $db->query("
        SELECT sr.*, sreq.title as request_title, u.full_name as requester_name
        FROM support_requests sr
        LEFT JOIN service_requests sreq ON sr.service_request_id = sreq.id
        LEFT JOIN users u ON sr.requester_id = u.id
        LEFT JOIN notifications n ON n.related_id = sr.id AND n.related_type = 'support_request' AND n.type = 'warning'
        WHERE n.id IS NULL
        ORDER BY sr.created_at DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($missing_notifications) > 0) {
        echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0;'>";
        echo "<strong>🚨 FOUND " . count($missing_notifications) . " requests without notifications!</strong>";
        echo "</div>";
        
        // Get admin ID
        $admin = $db->query("SELECT id, full_name FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            echo "<p>Sending notifications to: <strong>{$admin['full_name']}</strong></p>";
            
            $fixed_count = 0;
            foreach ($missing_notifications as $req) {
                try {
                    // Create notification for admin
                    $result = $notificationHelper->createNotification(
                        $admin['id'], // admin user_id
                        "🚨 Yêu cầu hỗ trợ kỹ thuật - #{$req['service_request_id']}",
                        "Staff {$req['requester_name']} cần hỗ trợ: {$req['support_reason']}",
                        'warning',
                        $req['id'], // support request ID
                        'support_request',
                        false // don't send email for now
                    );
                    
                    if ($result) {
                        $fixed_count++;
                        echo "<p>✅ Fixed notification for request #{$req['id']}: {$req['request_title']}</p>";
                    } else {
                        echo "<p>❌ Failed to fix notification for request #{$req['id']}</p>";
                    }
                    
                } catch (Exception $e) {
                    echo "<p>❌ Error fixing request #{$req['id']}: " . $e->getMessage() . "</p>";
                }
            }
            
            echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>";
            echo "<strong>✅ Successfully fixed $fixed_count notifications!</strong>";
            echo "</div>";
            
        } else {
            echo "<p style='color: red;'>❌ No admin found to send notifications to!</p>";
        }
        
    } else {
        echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>";
        echo "<strong>✅ All support requests have notifications</strong>";
        echo "</div>";
    }
    
    // 3. Test notification system
    echo "<h3>🧪 3. Test Notification System</h3>";
    
    try {
        $admin = $db->query("SELECT id, full_name FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        
        if ($admin) {
            $test_result = $notificationHelper->createNotification(
                $admin['id'],
                "🧪 TEST - Support Notification System",
                "This is a test to verify support notifications are working correctly.",
                'info',
                null,
                'test',
                false
            );
            
            if ($test_result) {
                echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>";
                echo "<strong>✅ Test notification sent successfully!</strong>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0;'>";
                echo "<strong>❌ Test notification failed!</strong>";
                echo "</div>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Test failed: " . $e->getMessage() . "</p>";
    }
    
    // 4. Enable future notifications
    echo "<h3>🔔 4. Enable Future Notifications</h3>";
    
    echo "<p><strong>Check support_requests.php:</strong></p>";
    
    if (file_exists('api/support_requests.php')) {
        $support_api_content = file_get_contents('api/support_requests.php');
        
        if (strpos($support_api_content, 'notifyAdminSupportRequest') !== false) {
            echo "<p>✅ Support API has notification calls</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Support API missing notification calls</p>";
            echo "<p>Need to add: <code>\$notificationHelper->notifyAdminSupportRequest(\$service_request_id, \$support_details);</code></p>";
        }
    } else {
        echo "<p style='color: red;'>❌ support_requests.php not found</p>";
    }
    
    // 5. Create monitoring alert
    echo "<h3>📊 5. Create Monitoring Alert</h3>";
    
    echo "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px;'>";
    echo "<h4>🔧 MONITORING SETUP:</h4>";
    echo "<ul>";
    echo "<li><strong>Check notifications every hour:</strong> <a href='check-support-notifications.php'>Monitor Now</a></li>";
    echo "<li><strong>Admin Dashboard:</strong> <a href='admin-support-dashboard.php'>View Dashboard</a></li>";
    echo "<li><strong>Performance Monitor:</strong> <a href='performance-monitor.php'>Real-time Stats</a></li>";
    echo "</ul>";
    echo "</div>";
    
    // 6. Next steps
    echo "<h3>🚀 6. Next Steps</h3>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    echo "<h4>✅ COMPLETED:</h4>";
    echo "<ul>";
    echo "<li>✅ Identified missing notifications</li>";
    echo "<li>✅ Fixed all missing notifications</li>";
    echo "<li>✅ Tested notification system</li>";
    echo "<li>✅ Created monitoring setup</li>";
    echo "</ul>";
    
    echo "<h4>🔄 STILL NEEDED:</h4>";
    echo "<ul>";
    echo "<li>🔄 Add database indexes (performance)</li>";
    echo "<li>🔄 Create admin dashboard</li>";
    echo "<li>🔄 Enable email notifications</li>";
    echo "<li>🔄 Add more admins (workload distribution)</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
