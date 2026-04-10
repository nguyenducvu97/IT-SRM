<?php
require_once 'config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>🔍 FINAL SUPPORT REQUEST PERFORMANCE ANALYSIS</h2>";
    
    // First, check users table structure
    echo "<h3>👥 Users Table Structure Check:</h3>";
    
    try {
        $users_columns = $db->query("DESCRIBE users")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Column</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
        
        $status_column_exists = false;
        foreach ($users_columns as $col) {
            echo "<tr>";
            echo "<td><strong>{$col['Field']}</strong></td>";
            echo "<td>{$col['Type']}</td>";
            echo "<td>{$col['Null']}</td>";
            echo "<td>{$col['Key']}</td>";
            echo "<td>" . ($col['Default'] ?: 'NULL') . "</td>";
            echo "</tr>";
            
            if ($col['Field'] === 'status') {
                $status_column_exists = true;
            }
        }
        echo "</table>";
        
        if (!$status_column_exists) {
            echo "<p style='color: orange;'>⚠️ Column 'status' not found in users table - will remove from query</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>Error checking users table: " . $e->getMessage() . "</p>";
    }
    
    // 1. Support requests processing time (already working)
    echo "<h3>📊 1. Support Requests Processing Time Analysis</h3>";
    
    $support_query = "
        SELECT sr.id, sr.service_request_id, sr.status, sr.support_type,
               sr.support_details, sr.support_reason, sr.admin_reason,
               sr.created_at as support_created, sr.updated_at as support_updated,
               sreq.title as request_title, sreq.created_at as request_created,
               u1.full_name as requester_name, u2.full_name as admin_name,
               
               -- Calculate processing times
               TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at) as support_processing_minutes,
               TIMESTAMPDIFF(MINUTE, sreq.created_at, sr.created_at) as time_to_escalate_minutes,
               TIMESTAMPDIFF(MINUTE, sreq.created_at, sr.updated_at) as total_time_minutes,
               
               -- Format for better readability
               CASE 
                   WHEN TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at) > 1440 THEN 
                       CONCAT(ROUND(TIMESTAMPDIFF(HOUR, sr.created_at, sr.updated_at), 1), ' hours')
                   ELSE 
                       CONCAT(TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at), ' minutes')
               END as processing_time_formatted
               
        FROM support_requests sr
        LEFT JOIN service_requests sreq ON sr.service_request_id = sreq.id
        LEFT JOIN users u1 ON sr.requester_id = u1.id
        LEFT JOIN users u2 ON sr.processed_by = u2.id
        ORDER BY sr.created_at DESC
    ";
    
    $support_requests = $db->query($support_query)->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($support_requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Request Title</th><th>Requester</th><th>Admin</th><th>Type</th><th>Status</th><th>Processing Time</th><th>Support Reason</th></tr>";
        
        $total_processing_time = 0;
        $slow_requests = [];
        $type_stats = [];
        $status_stats = [];
        
        foreach ($support_requests as $req) {
            $processing_time = $req['support_processing_minutes'];
            $support_type = $req['support_type'];
            $status = $req['status'];
            
            $total_processing_time += $processing_time;
            
            // Track stats by type
            if (!isset($type_stats[$support_type])) {
                $type_stats[$support_type] = ['count' => 0, 'total_time' => 0];
            }
            $type_stats[$support_type]['count']++;
            $type_stats[$support_type]['total_time'] += $processing_time;
            
            // Track stats by status
            if (!isset($status_stats[$status])) {
                $status_stats[$status] = ['count' => 0, 'total_time' => 0];
            }
            $status_stats[$status]['count']++;
            $status_stats[$status]['total_time'] += $processing_time;
            
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
            echo "<td><strong>{$req['processing_time_formatted']}</strong></td>";
            echo "<td>" . htmlspecialchars(substr($req['support_reason'], 0, 30)) . "...</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Performance summary
        $avg_processing = count($support_requests) > 0 ? $total_processing_time / count($support_requests) : 0;
        
        echo "<h4>📈 Performance Summary:</h4>";
        echo "<ul>";
        echo "<li><strong>Total Support Requests:</strong> " . count($support_requests) . "</li>";
        echo "<li><strong>Average Processing Time:</strong> " . round($avg_processing, 2) . " minutes (" . round($avg_processing/60, 2) . " hours)</li>";
        echo "<li><strong>Slow Requests (>30min):</strong> " . count($slow_requests) . " (" . round(count($slow_requests)/count($support_requests)*100, 1) . "%)</li>";
        echo "</ul>";
        
        // Type statistics
        echo "<h4>📊 Statistics by Support Type:</h4>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Type</th><th>Count</th><th>Avg Processing Time</th><th>Percentage</th></tr>";
        
        foreach ($type_stats as $type => $stats) {
            $avg_time = $stats['count'] > 0 ? $stats['total_time'] / $stats['count'] : 0;
            $percentage = count($support_requests) > 0 ? round($stats['count']/count($support_requests)*100, 1) : 0;
            
            echo "<tr>";
            echo "<td><strong>$type</strong></td>";
            echo "<td>{$stats['count']}</td>";
            echo "<td>" . round($avg_time, 2) . " min</td>";
            echo "<td>{$percentage}%</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Status statistics
        echo "<h4>📊 Statistics by Status:</h4>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Status</th><th>Count</th><th>Avg Processing Time</th><th>Percentage</th></tr>";
        
        foreach ($status_stats as $status => $stats) {
            $avg_time = $stats['count'] > 0 ? $stats['total_time'] / $stats['count'] : 0;
            $percentage = count($support_requests) > 0 ? round($stats['count']/count($support_requests)*100, 1) : 0;
            
            $status_style = $status === 'pending' ? 'background: #fff3cd;' : '';
            
            echo "<tr style='$status_style'>";
            echo "<td><strong>$status</strong></td>";
            echo "<td>{$stats['count']}</td>";
            echo "<td>" . round($avg_time, 2) . " min</td>";
            echo "<td>{$percentage}%</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Show slow requests details
        if (count($slow_requests) > 0) {
            echo "<h4>🐌 Slow Requests Analysis (Critical Issues):</h4>";
            echo "<table border='1' cellpadding='5' cellspacing='0' style='background: #f8d7da;'>";
            echo "<tr><th>ID</th><th>Title</th><th>Requester</th><th>Admin</th><th>Processing Time</th><th>Support Reason</th><th>Admin Reason</th></tr>";
            
            foreach ($slow_requests as $req) {
                echo "<tr>";
                echo "<td>{$req['id']}</td>";
                echo "<td>" . htmlspecialchars(substr($req['request_title'], 0, 40)) . "...</td>";
                echo "<td>{$req['requester_name']}</td>";
                echo "<td>{$req['admin_name']}</td>";
                echo "<td><strong>{$req['processing_time_formatted']}</strong></td>";
                echo "<td>" . htmlspecialchars(substr($req['support_reason'], 0, 50)) . "...</td>";
                echo "<td>" . htmlspecialchars(substr($req['admin_reason'], 0, 50)) . "...</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        }
        
    } else {
        echo "<p>No support requests found</p>";
    }
    
    // 2. Fixed admin workload analysis
    echo "<h3>👥 2. Admin Workload Analysis (Fixed)</h3>";
    
    // Remove status condition since column doesn't exist
    $admin_workload_query = "
        SELECT u.id, u.full_name, u.email, u.role,
               COUNT(sr.id) as support_requests_handled,
               AVG(TIMESTAMPDIFF(MINUTE, sr.created_at, sr.updated_at)) as avg_processing_time,
               MAX(sr.created_at) as last_support_date,
               MIN(sr.created_at) as first_support_date
        FROM users u
        LEFT JOIN support_requests sr ON u.id = sr.processed_by
        WHERE u.role = 'admin'
        GROUP BY u.id, u.full_name, u.email, u.role
        ORDER BY support_requests_handled DESC
    ";
    
    $admin_workload = $db->query($admin_workload_query)->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($admin_workload) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Admin</th><th>Email</th><th>Role</th><th>Support Requests</th><th>Avg Processing Time</th><th>Last Support Date</th><th>Experience Period</th></tr>";
        
        foreach ($admin_workload as $admin) {
            $experience_days = '';
            if ($admin['first_support_date'] && $admin['last_support_date']) {
                $days = round((strtotime($admin['last_support_date']) - strtotime($admin['first_support_date'])) / (60 * 60 * 24));
                $experience_days = $days . ' days';
            }
            
            echo "<tr>";
            echo "<td><strong>{$admin['full_name']}</strong></td>";
            echo "<td>{$admin['email']}</td>";
            echo "<td>{$admin['role']}</td>";
            echo "<td>{$admin['support_requests_handled']}</td>";
            echo "<td>" . ($admin['avg_processing_time'] ? round($admin['avg_processing_time'], 2) . " min" : 'N/A') . "</td>";
            echo "<td>" . ($admin['last_support_date'] ?: 'Never') . "</td>";
            echo "<td>$experience_days</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No admins found or no support requests processed</p>";
    }
    
    // 3. Current pending requests (CRITICAL)
    echo "<h3>⏰ 3. Current Pending Requests (URGENT)</h3>";
    
    $pending_requests = $db->query("
        SELECT sr.id, sr.service_request_id, sr.support_type, sr.support_reason,
               sreq.title as request_title, u1.full_name as requester_name,
               TIMESTAMPDIFF(MINUTE, sr.created_at, NOW()) as waiting_minutes,
               sr.created_at as request_time
        FROM support_requests sr
        LEFT JOIN service_requests sreq ON sr.service_request_id = sreq.id
        LEFT JOIN users u1 ON sr.requester_id = u1.id
        WHERE sr.status = 'pending'
        ORDER BY sr.created_at ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($pending_requests) > 0) {
        echo "<div style='background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0;'>";
        echo "<strong>⚠️ URGENT: " . count($pending_requests) . " pending requests need immediate attention!</strong>";
        echo "</div>";
        
        echo "<table border='1' cellpadding='5' cellspacing='0' style='background: #fff3cd;'>";
        echo "<tr><th>ID</th><th>Request Title</th><th>Requester</th><th>Type</th><th>Waiting Time</th><th>Request Time</th><th>Reason</th></tr>";
        
        foreach ($pending_requests as $req) {
            $waiting_style = $req['waiting_minutes'] > 60 ? 'background: #f8d7da; font-weight: bold;' : '';
            $urgency_indicator = $req['waiting_minutes'] > 60 ? '🔴 URGENT' : '🟡 Pending';
            
            echo "<tr style='$waiting_style'>";
            echo "<td>{$req['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($req['request_title'], 0, 30)) . "...</td>";
            echo "<td>{$req['requester_name']}</td>";
            echo "<td>{$req['support_type']}</td>";
            echo "<td><strong>{$urgency_indicator} - {$req['waiting_minutes']} minutes</strong></td>";
            echo "<td>{$req['request_time']}</td>";
            echo "<td>" . htmlspecialchars(substr($req['support_reason'], 0, 40)) . "...</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<div style='background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; margin: 10px 0;'>";
        echo "<strong>✅ GOOD: No pending support requests</strong>";
        echo "</div>";
    }
    
    // 4. Key Performance Insights
    echo "<h3>💡 4. Key Performance Insights & Recommendations</h3>";
    
    echo "<div style='background: #e3f2fd; padding: 15px; border-radius: 5px;'>";
    
    // Calculate critical metrics
    $total_requests = count($support_requests);
    $pending_count = count($pending_requests);
    $slow_count = count($slow_requests);
    $avg_processing = $total_requests > 0 ? $total_processing_time / $total_requests : 0;
    
    echo "<h4>🚨 CRITICAL ISSUES:</h4>";
    echo "<ul>";
    
    if ($pending_count > 0) {
        echo "<li><strong>URGENT:</strong> $pending_count pending requests waiting for admin action</li>";
        $urgent_pending = array_filter($pending_requests, function($req) { return $req['waiting_minutes'] > 60; });
        if (count($urgent_pending) > 0) {
            echo "<li><strong>CRITICAL:</strong> " . count($urgent_pending) . " requests pending >1 hour</li>";
        }
    }
    
    if ($slow_count > 0) {
        $slow_percentage = round($slow_count / $total_requests * 100, 1);
        echo "<li><strong>PERFORMANCE ISSUE:</strong> $slow_count requests ($slow_percentage%) took >30 minutes</li>";
    }
    
    if ($avg_processing > 60) {
        echo "<li><strong>SYSTEMIC ISSUE:</strong> Average processing time is " . round($avg_processing/60, 2) . " hours - too slow!</li>";
    }
    
    echo "</ul>";
    
    echo "<h4>🔧 IMMEDIATE ACTIONS REQUIRED:</h4>";
    echo "<ol>";
    if ($pending_count > 0) {
        echo "<li><strong>Process all pending requests</strong> - especially those waiting >1 hour</li>";
    }
    echo "<li><strong>Investigate slow processing patterns</strong> - why are requests taking so long?</li>";
    echo "<li><strong>Set up real-time notifications</strong> for new support requests</li>";
    echo "<li><strong>Implement SLA monitoring</strong> with alerts for >30min delays</li>";
    echo "</ol>";
    
    echo "<h4>📊 LONG-TERM IMPROVEMENTS:</h4>";
    echo "<ul>";
    echo "<li><strong>Database Optimization:</strong> Add indexes on (processed_by, created_at, status)</li>";
    echo "<li><strong>Admin Training:</strong> Focus on reducing processing time</li>";
    echo "<li><strong>Process Documentation:</strong> Create standard procedures for common request types</li>";
    echo "<li><strong>Performance Dashboard:</strong> Real-time monitoring for admins</li>";
    echo "<li><strong>Load Balancing:</strong> Distribute requests more evenly across admins</li>";
    echo "</ul>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
