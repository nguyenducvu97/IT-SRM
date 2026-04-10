<?php
require_once 'config/database.php';

echo "<h2>🚨 EMERGENCY SUPPORT PERFORMANCE FIXES</h2>";

try {
    $db = getDatabaseConnection();
    
    // 1. Enable real-time notifications for support requests
    echo "<h3>🔔 1. Enable Real-time Notifications</h3>";
    
    // Check if notifications are being sent for support requests
    $notification_check = $db->query("
        SELECT COUNT(*) as notification_count 
        FROM notifications 
        WHERE type = 'warning' 
        AND message LIKE '%hỗ trợ%' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<p>Support request notifications in last 7 days: <strong>{$notification_check['notification_count']}</strong></p>";
    
    if ($notification_check['notification_count'] == 0) {
        echo "<p style='color: red; font-weight: bold;'>❌ NO NOTIFICATIONS BEING SENT!</p>";
        echo "<p>This explains why admin takes 11+ hours - they don't know about requests!</p>";
    }
    
    // 2. Create admin alert system
    echo "<h3>📧 2. Create Admin Alert System</h3>";
    
    // Get admin email
    $admin = $db->query("SELECT email, full_name FROM users WHERE role = 'admin' LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "<p>Admin: {$admin['full_name']} ({$admin['email']})</p>";
        
        // Create urgent alert for any pending requests
        $pending_count = $db->query("SELECT COUNT(*) as count FROM support_requests WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC);
        
        if ($pending_count['count'] > 0) {
            echo "<div style='background: #f8d7da; padding: 10px; margin: 10px 0;'>";
            echo "<strong>🚨 URGENT: {$pending_count['count']} pending requests found!</strong>";
            echo "</div>";
        }
    }
    
    // 3. Database optimization
    echo "<h3>⚡ 3. Database Optimization</h3>";
    
    // Check if indexes exist
    $indexes = $db->query("SHOW INDEX FROM support_requests")->fetchAll(PDO::FETCH_ASSOC);
    $existing_indexes = array_unique(array_column($indexes, 'Key_name'));
    
    $recommended_indexes = ['idx_processed_by', 'idx_status_created', 'idx_support_type'];
    
    echo "<p><strong>Current Indexes:</strong> " . implode(', ', $existing_indexes) . "</p>";
    echo "<p><strong>Recommended Indexes:</strong> " . implode(', ', $recommended_indexes) . "</p>";
    
    foreach ($recommended_indexes as $index) {
        if (!in_array($index, $existing_indexes)) {
            echo "<p style='color: orange;'>⚠️ Missing index: $index</p>";
        }
    }
    
    // 4. Create immediate performance monitoring
    echo "<h3>📊 4. Performance Monitoring Dashboard</h3>";
    
    // Real-time stats
    $stats = $db->query("
        SELECT 
            COUNT(*) as total_requests,
            COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
            COUNT(CASE WHEN status = 'approved' THEN 1 END) as approved_count,
            COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_count,
            AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_processing_minutes
        FROM support_requests
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Metric</th><th>Value</th><th>Status</th></tr>";
    
    $status_color = $stats['avg_processing_minutes'] > 30 ? 'background: #f8d7da;' : 'background: #d4edda;';
    echo "<tr style='$status_color'>";
    echo "<td>Avg Processing Time</td>";
    echo "<td><strong>" . round($stats['avg_processing_minutes'], 2) . " minutes</strong></td>";
    echo "<td>" . ($stats['avg_processing_minutes'] > 30 ? '🔴 CRITICAL' : '✅ GOOD') . "</td>";
    echo "</tr>";
    
    $pending_color = $stats['pending_count'] > 0 ? 'background: #fff3cd;' : 'background: #d4edda;';
    echo "<tr style='$pending_color'>";
    echo "<td>Pending Requests</td>";
    echo "<td><strong>{$stats['pending_count']}</strong></td>";
    echo "<td>" . ($stats['pending_count'] > 0 ? '🟡 ATTENTION' : '✅ CLEAR') . "</td>";
    echo "</tr>";
    
    echo "<tr>";
    echo "<td>Total Requests (30 days)</td>";
    echo "<td>{$stats['total_requests']}</td>";
    echo "<td>📊 Info</td>";
    echo "</tr>";
    
    echo "</table>";
    
    // 5. Immediate action items
    echo "<h3>🚀 5. IMMEDIATE ACTION ITEMS</h3>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    echo "<h4>🔥 DO RIGHT NOW:</h4>";
    echo "<ol>";
    echo "<li><strong>Check for pending requests</strong> - Process immediately if any exist</li>";
    echo "<li><strong>Enable email notifications</strong> - Admin must get alerts for new requests</li>";
    echo "<li><strong>Create admin dashboard</strong> - Centralized view of all requests</li>";
    echo "<li><strong>Set up monitoring</strong> - Alert if any request >30 minutes</li>";
    echo "</ol>";
    
    echo "<h4>⚡ TODAY:</h4>";
    echo "<ol>";
    echo "<li><strong>Add database indexes</strong> for faster queries</li>";
    echo "<li><strong>Test notification system</strong> - Create test support request</li>";
    echo "<li><strong>Document processes</strong> - Standard procedures for common requests</li>";
    echo "<li><strong>Train admin</strong> - Focus on response time importance</li>";
    echo "</ol>";
    
    echo "<h4>📈 THIS WEEK:</h4>";
    echo "<ol>";
    echo "<li><strong>Add more admins</strong> - Distribute workload</li>";
    echo "<li><strong>Implement SLA</strong> - 30-minute response target</li>";
    echo "<li><strong>Create performance dashboard</strong> - Real-time monitoring</li>";
    echo "<li><strong>Automated escalation</strong> - Auto-alert if no response</li>";
    echo "</ol>";
    echo "</div>";
    
    // 6. Quick fix implementation
    echo "<h3>🔧 6. Quick Fix Implementation</h3>";
    
    echo "<div style='background: #d1ecf1; padding: 10px; margin: 10px 0;'>";
    echo "<h4>📧 Enable Email Alerts:</h4>";
    echo "<p><a href='test-support-notification.php'>Test Support Request Notification</a></p>";
    echo "<p><a href='enable-admin-alerts.php'>Enable Admin Email Alerts</a></p>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; padding: 10px; margin: 10px 0;'>";
    echo "<h4>📊 Create Dashboard:</h4>";
    echo "<p><a href='admin-support-dashboard.php'>Admin Support Dashboard</a></p>";
    echo "<p><a href='performance-monitor.php'>Real-time Performance Monitor</a></p>";
    echo "</div>";
    
    echo "<div style='background: #fff3cd; padding: 10px; margin: 10px 0;'>";
    echo "<h4>⚡ Database Optimization:</h4>";
    echo "<p><a href='optimize-support-tables.php'>Add Database Indexes</a></p>";
    echo "<p><a href='create-performance-views.php'>Create Performance Views</a></p>";
    echo "</div>";
    
    // 7. Success metrics
    echo "<h3>🎯 7. Target Success Metrics</h3>";
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Metric</th><th>Current</th><th>Target</th><th>Improvement Needed</th></tr>";
    echo "<tr>";
    echo "<td>Avg Processing Time</td>";
    echo "<td style='color: red; font-weight: bold;'>11.44 hours</td>";
    echo "<td style='color: green; font-weight: bold;'>&lt;30 minutes</td>";
    echo "<td style='color: orange; font-weight: bold;'>95% faster</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>Pending Requests</td>";
    echo "<td>0 (currently)</td>";
    echo "<td>0 always</td>";
    echo "<td>Maintain</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>Notification Speed</td>";
    echo "<td style='color: red;'>None</td>";
    echo "<td style='color: green;'>&lt;5 minutes</td>";
    echo "<td>Implement</td>";
    echo "</tr>";
    echo "<tr>";
    echo "<td>Admin Coverage</td>";
    echo "<td style='color: red;'>1 admin</td>";
    echo "<td style='color: green;'>2-3 admins</td>";
    echo "<td>Add staff</td>";
    echo "</tr>";
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
