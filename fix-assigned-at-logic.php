<?php
require_once 'config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Fix assigned_at Logic for Resolved Requests</h2>";
    
    // Check requests where assigned_at equals resolved_at
    echo "<h3>Requests with assigned_at = resolved_at:</h3>";
    
    $check_query = "SELECT id, title, status, assigned_to, created_at, assigned_at, resolved_at, updated_at
                   FROM service_requests 
                   WHERE assigned_to IS NOT NULL 
                   AND assigned_at IS NOT NULL
                   AND resolved_at IS NOT NULL
                   AND assigned_at = resolved_at
                   ORDER BY id";
    
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute();
    $problem_requests = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($problem_requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th><th>Assigned At</th><th>Resolved At</th><th>Updated</th></tr>";
        
        foreach ($problem_requests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . htmlspecialchars($request['title']) . "</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "<td style='background: #f8d7da;'>{$request['assigned_at']}</td>";
            echo "<td style='background: #f8d7da;'>{$request['resolved_at']}</td>";
            echo "<td>{$request['updated_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        echo "<h3>Fix Strategy:</h3>";
        echo "<p>For resolved requests, we need to estimate a more realistic assigned_at time.</p>";
        echo "<p>Strategy: Set assigned_at to 30 minutes after created_at, but before resolved_at</p>";
        
        // Fix the assigned_at for resolved requests
        echo "<h3>Fixing assigned_at...</h3>";
        
        foreach ($problem_requests as $request) {
            $request_id = $request['id'];
            $created_at = new DateTime($request['created_at']);
            $resolved_at = new DateTime($request['resolved_at']);
            
            // Calculate midpoint between created and resolved
            $interval = $created_at->diff($resolved_at);
            $half_interval = $interval->format('%i') / 2;
            
            // Set assigned_at to halfway point
            $assigned_at = clone $created_at;
            $assigned_at->add(new DateInterval('PT' . $half_interval . 'M'));
            
            // Ensure assigned_at is before resolved_at
            if ($assigned_at >= $resolved_at) {
                $assigned_at = clone $resolved_at;
                $assigned_at->sub(new DateInterval('PT1M')); // 1 minute before resolved
            }
            
            // Update the request
            $update_query = "UPDATE service_requests 
                             SET assigned_at = :assigned_at 
                             WHERE id = :request_id";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindValue(':assigned_at', $assigned_at->format('Y-m-d H:i:s'));
            $update_stmt->bindValue(':request_id', $request_id);
            $update_stmt->execute();
            
            echo "<p>Updated request #{$request_id}: {$request['created_at']} -> {$assigned_at->format('Y-m-d H:i:s')} -> {$request['resolved_at']}</p>";
        }
        
        // Verify the fix
        echo "<h3>Verification:</h3>";
        
        $verify_query = "SELECT id, title, status, created_at, assigned_at, resolved_at
                        FROM service_requests 
                        WHERE id IN (" . implode(',', array_column($problem_requests, 'id')) . ")
                        ORDER BY id";
        
        $verify_stmt = $db->prepare($verify_query);
        $verify_stmt->execute();
        $fixed_requests = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th><th>Assigned At</th><th>Resolved At</th></tr>";
        
        foreach ($fixed_requests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . htmlspecialchars($request['title']) . "</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "<td style='background: #d4edda;'>{$request['assigned_at']}</td>";
            echo "<td>{$request['resolved_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
    } else {
        echo "<p>No requests with assigned_at = resolved_at found</p>";
    }
    
    // Check for in_progress requests that might have wrong assigned_at
    echo "<h3>Check in_progress requests:</h3>";
    
    $in_progress_query = "SELECT id, title, status, assigned_to, created_at, assigned_at, updated_at
                         FROM service_requests 
                         WHERE status = 'in_progress'
                         AND assigned_to IS NOT NULL
                         AND assigned_at IS NOT NULL
                         ORDER BY id";
    
    $in_progress_stmt = $db->prepare($in_progress_query);
    $in_progress_stmt->execute();
    $in_progress_requests = $in_progress_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($in_progress_requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th><th>Assigned At</th><th>Updated</th></tr>";
        
        foreach ($in_progress_requests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . htmlspecialchars($request['title']) . "</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "<td style='background: #d4edda;'>{$request['assigned_at']}</td>";
            echo "<td>{$request['updated_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
    echo "<h3>Test Request #79:</h3>";
    
    $test_query = "SELECT id, title, status, created_at, assigned_at, resolved_at
                  FROM service_requests 
                  WHERE id = 79";
    
    $test_stmt = $db->prepare($test_query);
    $test_stmt->execute();
    $test_request = $test_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test_request) {
        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
        echo "<h4>Request #79 Details:</h4>";
        echo "<p><strong>ID:</strong> {$test_request['id']}</p>";
        echo "<p><strong>Title:</strong> " . htmlspecialchars($test_request['title']) . "</p>";
        echo "<p><strong>Status:</strong> {$test_request['status']}</p>";
        echo "<p><strong>Ngày yêu:</strong> " . date('H:i:s d/m/Y', strtotime($test_request['created_at'])) . "</p>";
        echo "<p><strong>Ngày nhân:</strong> " . date('H:i:s d/m/Y', strtotime($test_request['assigned_at'])) . "</p>";
        
        if ($test_request['resolved_at']) {
            echo "<p><strong>Ngày yêu:</strong> " . date('H:i:s d/m/Y', strtotime($test_request['resolved_at'])) . "</p>";
            
            // Check if they're still the same
            if ($test_request['assigned_at'] === $test_request['resolved_at']) {
                echo "<p style='color: red;'>Still the same - need to fix!</p>";
            } else {
                echo "<p style='color: green;'>Different times - good!</p>";
            }
        }
        
        echo "</div>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Run this script to fix assigned_at times</li>";
    echo "<li>Refresh request details page</li>";
    echo "<li>Verify assigned_at and resolved_at are different</li>";
    echo "<li>Test with new assignments to ensure proper logic</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
