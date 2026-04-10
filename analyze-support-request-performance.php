<?php
require_once 'config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>🔍 SUPPORT REQUEST PERFORMANCE ANALYSIS</h2>";
    
    // 1. Check support requests processing time
    echo "<h3>📊 1. Support Requests Processing Time</h3>";
    
    $support_query = "
        SELECT sr.id, sr.service_request_id, sr.status, sr.reason as support_reason,
               sr.created_at as support_created, sr.updated_at as support_updated,
               sreq.title as request_title, sreq.created_at as request_created,
               u1.full_name as staff_name, u2.full_name as admin_name,
               
               -- Calculate processing times
               TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at) as support_processing_minutes,
               TIMESTAMPDIFF(MINUTE, sreq.created_at, sr.created_at) as time_to_escalate_minutes,
               TIMESTAMPDIFF(MINUTE, sreq.created_at, sr.updated_at) as total_time_minutes
               
        FROM support_requests sr
        LEFT JOIN service_requests sreq ON sr.service_request_id = sreq.id
        LEFT JOIN users u1 ON sr.staff_id = u1.id
        LEFT JOIN users u2 ON sr.admin_id = u2.id
        ORDER BY sr.created_at DESC
    ";
    
    $support_requests = $db->query($support_query)->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($support_requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Request Title</th><th>Staff</th><th>Admin</th><th>Status</th><th>Time to Escalate</th><th>Processing Time</th><th>Total Time</th></tr>";
        
        $total_processing_time = 0;
        $slow_requests = [];
        
        foreach ($support_requests as $req) {
            $processing_time = $req['support_processing_minutes'];
            $escalate_time = $req['time_to_escalate_minutes'];
            $total_time = $req['total_time_minutes'];
            
            $total_processing_time += $processing_time;
            
            // Mark slow requests (more than 30 minutes)
            if ($processing_time > 30) {
                $slow_requests[] = $req;
            }
            
            $row_style = $processing_time > 30 ? 'background: #f8d7da;' : '';
            
            echo "<tr style='$row_style'>";
            echo "<td>{$req['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($req['request_title'], 0, 30)) . "...</td>";
            echo "<td>{$req['staff_name']}</td>";
            echo "<td>{$req['admin_name']}</td>";
            echo "<td>{$req['status']}</td>";
            echo "<td>" . ($escalate_time ? "{$escalate_time} min" : 'N/A') . "</td>";
            echo "<td><strong>" . ($processing_time ? "{$processing_time} min" : 'N/A') . "</strong></td>";
            echo "<td>" . ($total_time ? "{$total_time} min" : 'N/A') . "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Performance summary
        $avg_processing = count($support_requests) > 0 ? $total_processing_time / count($support_requests) : 0;
        
        echo "<h4>📈 Performance Summary:</h4>";
        echo "<ul>";
        echo "<li><strong>Total Support Requests:</strong> " . count($support_requests) . "</li>";
        echo "<li><strong>Average Processing Time:</strong> " . round($avg_processing, 2) . " minutes</li>";
        echo "<li><strong>Slow Requests (>30min):</strong> " . count($slow_requests) . "</li>";
        echo "</ul>";
        
        // Show slow requests details
        if (count($slow_requests) > 0) {
            echo "<h4>🐌 Slow Requests Analysis:</h4>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='background: #f8d7da;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Staff</th><th>Admin</th><th>Processing Time</th><th>Support Reason</th></tr>";
            
            foreach ($slow_requests as $req) {
                echo "<tr>";
                echo "<td>{$req['id']}</td>";
                echo "<td>" . htmlspecialchars(substr($req['request_title'], 0, 40)) . "...</td>";
                echo "<td>{$req['staff_name']}</td>";
                echo "<td>{$req['admin_name']}</td>";
                echo "<td><strong>{$req['support_processing_minutes']} minutes</strong></td>";
                echo "<td>" . htmlspecialchars(substr($req['support_reason'], 0, 50)) . "...</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
    } else {
        echo "<p>No support requests found</p>";
    }
    
    // 2. Check admin availability and workload
    echo "<h3>👥 2. Admin Workload Analysis</h3>";
    
    $admin_workload = $db->query("
        SELECT u.id, u.full_name, u.email,
               COUNT(sr.id) as support_requests_handled,
               AVG(TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at)) as avg_processing_time,
               MAX(sr.created_at) as last_support_date
        FROM users u
        LEFT JOIN support_requests sr ON u.id = sr.admin_id
        WHERE u.role = 'admin' AND u.status = 'active'
        GROUP BY u.id, u.full_name, u.email
        ORDER BY support_requests_handled DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Admin</th><th>Email</th><th>Support Requests</th><th>Avg Processing Time</th><th>Last Support Date</th></tr>";
    
    foreach ($admin_workload as $admin) {
        echo "<tr>";
        echo "<td>{$admin['full_name']}</td>";
        echo "<td>{$admin['email']}</td>";
        echo "<td>{$admin['support_requests_handled']}</td>";
        echo "<td>" . ($admin['avg_processing_time'] ? round($admin['avg_processing_time'], 2) . " min" : 'N/A') . "</td>";
        echo "<td>" . ($admin['last_support_date'] ?: 'Never') . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // 3. Check support request patterns
    echo "<h3>📋 3. Support Request Patterns</h3>";
    
    $patterns = $db->query("
        SELECT 
            DATE(sr.created_at) as support_date,
            COUNT(*) as daily_count,
            AVG(TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at)) as avg_daily_processing,
            COUNT(CASE WHEN sr.status = 'approved' THEN 1 END) as approved_count,
            COUNT(CASE WHEN sr.status = 'rejected' THEN 1 END) as rejected_count
        FROM support_requests sr
        WHERE sr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(sr.created_at)
        ORDER BY support_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($patterns) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Date</th><th>Requests</th><th>Avg Processing</th><th>Approved</th><th>Rejected</th></tr>";
        
        foreach ($patterns as $pattern) {
            echo "<tr>";
            echo "<td>{$pattern['support_date']}</td>";
            echo "<td>{$pattern['daily_count']}</td>";
            echo "<td>" . round($pattern['avg_daily_processing'], 2) . " min</td>";
            echo "<td>{$pattern['approved_count']}</td>";
            echo "<td>{$pattern['rejected_count']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No support requests in the last 7 days</p>";
    }
    
    // 4. Check system performance
    echo "<h3>⚡ 4. System Performance Check</h3>";
    
    // Check API response time simulation
    $start_time = microtime(true);
    
    // Simulate support request processing
    $test_query = "
        SELECT sr.*, sreq.title, u1.full_name as staff_name, u2.full_name as admin_name
        FROM support_requests sr
        LEFT JOIN service_requests sreq ON sr.service_request_id = sreq.id
        LEFT JOIN users u1 ON sr.staff_id = u1.id
        LEFT JOIN users u2 ON sr.admin_id = u2.id
        ORDER BY sr.created_at DESC
        LIMIT 10
    ";
    
    $db->query($test_query);
    $query_time = microtime(true) - $start_time;
    
    echo "<ul>";
    echo "<li><strong>Database Query Time:</strong> " . round($query_time * 1000, 2) . " ms</li>";
    echo "<li><strong>Query Performance:</strong> " . ($query_time < 0.1 ? '✅ Good' : '⚠️ Slow') . "</li>";
    echo "</ul>";
    
    // 5. Recommendations
    echo "<h3>💡 5. Performance Recommendations</h3>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    echo "<h4>🔧 Optimization Suggestions:</h4>";
    echo "<ul>";
    echo "<li><strong>Database Indexes:</strong> Add indexes on support_requests(admin_id, created_at)</li>";
    echo "<li><strong>Admin Notifications:</strong> Implement real-time alerts for new support requests</li>";
    echo "<li><strong>Auto-assignment:</strong> Consider auto-assigning support requests to available admins</li>";
    echo "<li><strong>SLA Monitoring:</strong> Set up alerts for requests taking >30 minutes</li>";
    echo "<li><strong>Batch Processing:</strong> Process support requests in batches during peak hours</li>";
    echo "</ul>";
    
    echo "<h4>📊 Monitoring Metrics:</h4>";
    echo "<ul>";
    echo "<li>Track average processing time per admin</li>";
    echo "<li>Monitor escalation patterns by time of day</li>";
    echo "<li>Measure staff-to-admin escalation frequency</li>";
    echo "<li>Analyze rejection vs approval ratios</li>";
    echo "</ul>";
    echo "</div>";
    
    // 6. Quick fix suggestions
    echo "<h3>🚀 6. Quick Performance Fixes</h3>";
    
    echo "<p><a href='optimize-support-requests.php'>Optimize Database Tables</a></p>";
    echo "<p><a href='test-support-notification-speed.php'>Test Notification Speed</a></p>";
    echo "<p><a href='create-admin-dashboard.php'>Create Admin Performance Dashboard</a></p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
