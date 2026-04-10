<?php
require_once 'config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>🔍 FIXED SUPPORT REQUEST PERFORMANCE ANALYSIS</h2>";
    
    // 1. Check support requests processing time with CORRECT columns
    echo "<h3>📊 1. Support Requests Processing Time (Corrected)</h3>";
    
    $support_query = "
        SELECT sr.id, sr.service_request_id, sr.status, sr.support_type,
               sr.support_details, sr.support_reason, sr.admin_reason,
               sr.created_at as support_created, sr.updated_at as support_updated,
               sreq.title as request_title, sreq.created_at as request_created,
               u1.full_name as requester_name, u2.full_name as admin_name,
               
               -- Calculate processing times
               TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at) as support_processing_minutes,
               TIMESTAMPDIFF(MINUTE, sreq.created_at, sr.created_at) as time_to_escalate_minutes,
               TIMESTAMPDIFF(MINUTE, sreq.created_at, sr.updated_at) as total_time_minutes
               
        FROM support_requests sr
        LEFT JOIN service_requests sreq ON sr.service_request_id = sreq.id
        LEFT JOIN users u1 ON sr.requester_id = u1.id  -- requester_id, not staff_id
        LEFT JOIN users u2 ON sr.processed_by = u2.id  -- processed_by, not admin_id
        ORDER BY sr.created_at DESC
    ";
    
    $support_requests = $db->query($support_query)->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($support_requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Request Title</th><th>Requester</th><th>Admin</th><th>Type</th><th>Status</th><th>Time to Escalate</th><th>Processing Time</th><th>Total Time</th></tr>";
        
        $total_processing_time = 0;
        $slow_requests = [];
        $type_stats = [];
        
        foreach ($support_requests as $req) {
            $processing_time = $req['support_processing_minutes'];
            $escalate_time = $req['time_to_escalate_minutes'];
            $total_time = $req['total_time_minutes'];
            $support_type = $req['support_type'];
            
            $total_processing_time += $processing_time;
            
            // Track stats by type
            if (!isset($type_stats[$support_type])) {
                $type_stats[$support_type] = ['count' => 0, 'total_time' => 0];
            }
            $type_stats[$support_type]['count']++;
            $type_stats[$support_type]['total_time'] += $processing_time;
            
            // Mark slow requests (more than 30 minutes)
            if ($processing_time > 30) {
                $slow_requests[] = $req;
            }
            
            $row_style = $processing_time > 30 ? 'background: #f8d7da;' : '';
            
            echo "<tr style='$row_style'>";
            echo "<td>{$req['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($req['request_title'], 0, 30)) . "...</td>";
            echo "<td>{$req['requester_name']}</td>";
            echo "<td>{$req['admin_name']}</td>";
            echo "<td>{$req['support_type']}</td>";
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
        
        // Type statistics
        echo "<h4>📊 Statistics by Support Type:</h4>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Type</th><th>Count</th><th>Avg Processing Time</th></tr>";
        
        foreach ($type_stats as $type => $stats) {
            $avg_time = $stats['count'] > 0 ? $stats['total_time'] / $stats['count'] : 0;
            echo "<tr>";
            echo "<td><strong>$type</strong></td>";
            echo "<td>{$stats['count']}</td>";
            echo "<td>" . round($avg_time, 2) . " min</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Show slow requests details
        if (count($slow_requests) > 0) {
            echo "<h4>🐌 Slow Requests Analysis:</h4>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='background: #f8d7da;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Requester</th><th>Admin</th><th>Type</th><th>Processing Time</th><th>Support Reason</th></tr>";
            
            foreach ($slow_requests as $req) {
                echo "<tr>";
                echo "<td>{$req['id']}</td>";
                echo "<td>" . htmlspecialchars(substr($req['request_title'], 0, 40)) . "...</td>";
                echo "<td>{$req['requester_name']}</td>";
                echo "<td>{$req['admin_name']}</td>";
                echo "<td>{$req['support_type']}</td>";
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
    echo "<h3>👥 2. Admin Workload Analysis (Corrected)</h3>";
    
    $admin_workload = $db->query("
        SELECT u.id, u.full_name, u.email,
               COUNT(sr.id) as support_requests_handled,
               AVG(TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at)) as avg_processing_time,
               MAX(sr.created_at) as last_support_date
        FROM users u
        LEFT JOIN support_requests sr ON u.id = sr.processed_by
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
    echo "<h3>📋 3. Support Request Patterns (Corrected)</h3>";
    
    $patterns = $db->query("
        SELECT 
            DATE(sr.created_at) as support_date,
            COUNT(*) as daily_count,
            AVG(TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at)) as avg_daily_processing,
            COUNT(CASE WHEN sr.status = 'approved' THEN 1 END) as approved_count,
            COUNT(CASE WHEN sr.status = 'rejected' THEN 1 END) as rejected_count,
            COUNT(CASE WHEN sr.status = 'pending' THEN 1 END) as pending_count,
            GROUP_CONCAT(DISTINCT sr.support_type) as types_today
        FROM support_requests sr
        WHERE sr.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        GROUP BY DATE(sr.created_at)
        ORDER BY support_date DESC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($patterns) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Date</th><th>Requests</th><th>Avg Processing</th><th>Approved</th><th>Rejected</th><th>Pending</th><th>Types</th></tr>";
        
        foreach ($patterns as $pattern) {
            echo "<tr>";
            echo "<td>{$pattern['support_date']}</td>";
            echo "<td>{$pattern['daily_count']}</td>";
            echo "<td>" . round($pattern['avg_daily_processing'], 2) . " min</td>";
            echo "<td>{$pattern['approved_count']}</td>";
            echo "<td>{$pattern['rejected_count']}</td>";
            echo "<td>{$pattern['pending_count']}</td>";
            echo "<td>{$pattern['types_today']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No support requests in the last 7 days</p>";
    }
    
    // 4. Current pending requests
    echo "<h3>⏰ 4. Current Pending Requests</h3>";
    
    $pending_requests = $db->query("
        SELECT sr.id, sr.service_request_id, sr.support_type, sr.support_reason,
               sreq.title as request_title, u1.full_name as requester_name,
               TIMESTAMPDIFF(MINUTE, sr.created_at, NOW()) as waiting_minutes
        FROM support_requests sr
        LEFT JOIN service_requests sreq ON sr.service_request_id = sreq.id
        LEFT JOIN users u1 ON sr.requester_id = u1.id
        WHERE sr.status = 'pending'
        ORDER BY sr.created_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pending_requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0' style='background: #fff3cd;'>";
        echo "<tr><th>ID</th><th>Request Title</th><th>Requester</th><th>Type</th><th>Waiting Time</th><th>Reason</th></tr>";
        
        foreach ($pending_requests as $req) {
            $waiting_style = $req['waiting_minutes'] > 60 ? 'background: #f8d7da;' : '';
            
            echo "<tr style='$waiting_style'>";
            echo "<td>{$req['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($req['request_title'], 0, 30)) . "...</td>";
            echo "<td>{$req['requester_name']}</td>";
            echo "<td>{$req['support_type']}</td>";
            echo "<td><strong>{$req['waiting_minutes']} minutes</strong></td>";
            echo "<td>" . htmlspecialchars(substr($req['support_reason'], 0, 40)) . "...</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<p><strong>Total Pending:</strong> " . count($pending_requests) . " requests</p>";
    } else {
        echo "<p>✅ No pending support requests</p>";
    }
    
    // 5. Recommendations based on actual data
    echo "<h3>💡 5. Performance Recommendations (Data-Driven)</h3>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    
    // Calculate recommendations based on data
    $total_requests = count($support_requests);
    $pending_count = count($pending_requests);
    $slow_count = count($slow_requests);
    
    echo "<h4>🔧 Optimization Suggestions:</h4>";
    echo "<ul>";
    
    if ($pending_count > 0) {
        echo "<li><strong>Urgent:</strong> $pending_count pending requests need immediate attention</li>";
    }
    
    if ($slow_count > 0) {
        echo "<li><strong>Performance:</strong> $slow_count requests took >30 minutes - investigate bottlenecks</li>";
    }
    
    if ($avg_processing > 20) {
        echo "<li><strong>Training:</strong> Average processing time is " . round($avg_processing, 2) . " minutes - consider admin training</li>";
    }
    
    echo "<li><strong>Database:</strong> Add indexes on (processed_by, created_at) for faster queries</li>";
    echo "<li><strong>Notifications:</strong> Implement real-time alerts for new support requests</li>";
    echo "<li><strong>SLA:</strong> Set up alerts for requests pending >1 hour</li>";
    echo "</ul>";
    
    echo "<h4>📊 Monitoring Metrics:</h4>";
    echo "<ul>";
    echo "<li>Track processing time by support type (equipment/person/department)</li>";
    echo "<li>Monitor escalation patterns by requester</li>";
    echo "<li>Measure approval vs rejection ratios</li>";
    echo "<li>Analyze peak hours for support requests</li>";
    echo "</ul>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
