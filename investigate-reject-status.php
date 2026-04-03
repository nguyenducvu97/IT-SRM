<?php
// Investigate why requests were set to rejected status
require_once 'config/database.php';

echo "<h2>Điều tra nguyên nhân status 'rejected' không hợp lệ</h2>";

try {
    $db = getDatabaseConnection();
    
    $requestsToInvestigate = [18, 22, 27, 28];
    
    echo "<h3>Phân tích chi tiết từng request:</h3>";
    
    foreach ($requestsToInvestigate as $requestId) {
        echo "<h4>Request #{$requestId}:</h4>";
        
        // Get full request details
        $stmt = $db->prepare("SELECT * FROM service_requests WHERE id = ?");
        $stmt->execute([$requestId]);
        $request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($request) {
            echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            foreach ($request as $key => $value) {
                echo "<tr><td>{$key}</td><td>" . ($value ?: 'NULL') . "</td></tr>";
            }
            echo "</table>";
            
            // Check status history (if there's a status log table)
            echo "<h5>Check status history:</h5>";
            
            // Check if there are any status-related logs
            $tables = ['status_logs', 'request_logs', 'activity_logs', 'audit_logs'];
            $foundLogs = false;
            
            foreach ($tables as $table) {
                try {
                    $checkTable = $db->prepare("SHOW TABLES LIKE ?");
                    $checkTable->execute([$table]);
                    if ($checkTable->fetch()) {
                        echo "<p style='color: blue;'>Found table: {$table}</p>";
                        
                        $logStmt = $db->prepare("SELECT * FROM {$table} WHERE request_id = ? OR service_request_id = ? ORDER BY created_at DESC LIMIT 5");
                        $logStmt->execute([$requestId, $requestId]);
                        $logs = $logStmt->fetchAll(PDO::FETCH_ASSOC);
                        
                        if (!empty($logs)) {
                            echo "<table border='1' style='border-collapse: collapse;'>";
                            echo "<tr>";
                            foreach (array_keys($logs[0]) as $key) {
                                echo "<th>{$key}</th>";
                            }
                            echo "</tr>";
                            
                            foreach ($logs as $log) {
                                echo "<tr>";
                                foreach ($log as $key => $value) {
                                    echo "<td>" . ($value ?: '-') . "</td>";
                                }
                                echo "</tr>";
                            }
                            echo "</table>";
                            $foundLogs = true;
                        }
                    }
                } catch (Exception $e) {
                    // Table doesn't exist, continue
                }
            }
            
            if (!$foundLogs) {
                echo "<p style='color: orange;'>Không tìm thấy status logs</p>";
            }
            
            // Check for any direct database updates
            echo "<h5>Check manual updates:</h5>";
            
            // Look for patterns in updated_at vs created_at
            $created = new DateTime($request['created_at']);
            $updated = new DateTime($request['updated_at']);
            $interval = $created->diff($updated);
            
            echo "<p>Created: {$request['created_at']}</p>";
            echo "<p>Updated: {$request['updated_at']}</p>";
            echo "<p>Time difference: {$interval->h}h {$interval->i}m {$interval->s}s</p>";
            
            // Check if update was immediate (possible direct update)
            if ($interval->h == 0 && $interval->i < 5) {
                echo "<p style='color: red;'>⚠️ Cập nhật ngay lập tức - có thể là direct database update</p>";
            } elseif ($interval->h < 1) {
                echo "<p style='color: orange;'>⚠️ Cập nhật nhanh - cần điều tra</p>";
            } else {
                echo "<p style='color: green;'>✅ Cập nhật bình thường</p>";
            }
            
            // Check for any API calls or scripts that might update status
            echo "<h5>Possible causes:</h5>";
            echo "<ul>";
            echo "<li><strong>Direct database update:</strong> Someone ran SQL UPDATE directly</li>";
            echo "<li><strong>Bug in code:</strong> API endpoint incorrectly setting status</li>";
            echo "<li><strong>Import script:</strong> Data import with wrong status</li>";
            echo "<li><strong>Manual edit:</strong> Admin panel with direct status update</li>";
            echo "<li><strong>Migration script:</strong> Database migration with wrong data</li>";
            echo "</ul>";
            
        } else {
            echo "<p style='color: red;'>Request #{$requestId} không tồn tại</p>";
        }
        
        echo "<hr>";
    }
    
    // Check for any recent database changes
    echo "<h3>Check recent database activity:</h3>";
    
    // Check if there are any recent direct updates
    echo "<h4>Check for patterns in rejected status:</h4>";
    $stmt = $db->prepare("SELECT id, status, created_at, updated_at FROM service_requests WHERE status = 'rejected' ORDER BY updated_at DESC LIMIT 10");
    $stmt->execute();
    $rejectedRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($rejectedRequests)) {
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Status</th><th>Created</th><th>Updated</th><th>Time Diff</th><th>Has Reject Request</th></tr>";
        
        foreach ($rejectedRequests as $req) {
            $created = new DateTime($req['created_at']);
            $updated = new DateTime($req['updated_at']);
            $interval = $created->diff($updated);
            
            $rejectStmt = $db->prepare("SELECT id FROM reject_requests WHERE service_request_id = ?");
            $rejectStmt->execute([$req['id']]);
            $hasReject = $rejectStmt->fetch() ? 'Yes' : 'No';
            
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>{$req['status']}</td>";
            echo "<td>{$req['created_at']}</td>";
            echo "<td>{$req['updated_at']}</td>";
            echo "<td>{$interval->h}h {$interval->i}m</td>";
            echo "<td style='color: " . ($hasReject == 'Yes' ? 'green' : 'red') . ";'>{$hasReject}</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    echo "<h3>🔍 Recommendations:</h3>";
    echo "<ol>";
    echo "<li><strong>Check server logs:</strong> Look for any suspicious API calls around the update times</li>";
    echo "<li><strong>Review code:</strong> Check for any code that directly updates status without reject request</li>";
    echo "<li><strong>Database audit:</strong> Check MySQL binary logs if available</li>";
    echo "<li><strong>Admin access:</strong> Check who had database access during those times</li>";
    echo "<li><strong>Add validation:</strong> Add database trigger to prevent status 'rejected' without reject request</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
