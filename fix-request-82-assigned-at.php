<?php
require_once 'config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Fix Request #82 assigned_at</h2>";
    
    // Get current request details
    $query = "SELECT id, title, status, assigned_to, created_at, assigned_at, resolved_at
              FROM service_requests 
              WHERE id = 82";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<h3>Current Request Details:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Field</th><th>Current Value</th></tr>";
        echo "<tr><td>ID</td><td>{$request['id']}</td></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($request['title']) . "</td></tr>";
        echo "<tr><td>Status</td><td>{$request['status']}</td></tr>";
        echo "<tr><td>Assigned To</td><td>{$request['assigned_to']}</td></tr>";
        echo "<tr><td>Created At</td><td>{$request['created_at']}</td></tr>";
        echo "<tr><td>Assigned At</td><td>" . ($request['assigned_at'] ?: 'NULL') . "</td></tr>";
        echo "<tr><td>Resolved At</td><td>{$request['resolved_at']}</td></tr>";
        echo "</table>";
        
        // Determine which fix to apply
        $option = isset($_GET['option']) ? (int)$_GET['option'] : 1;
        
        $created_at = new DateTime($request['created_at']);
        $resolved_at = new DateTime($request['resolved_at']);
        
        if ($option == 1) {
            // 30 minutes after created
            $assigned_at = clone $created_at;
            $assigned_at->add(new DateInterval('PT30M'));
            $fix_type = "30 minutes after created";
        } else {
            // Midpoint between created and resolved
            $interval = $created_at->diff($resolved_at);
            $half_interval_minutes = intval($interval->format('%i')) / 2;
            $assigned_at = clone $created_at;
            $assigned_at->add(new DateInterval('PT' . $half_interval_minutes . 'M'));
            $fix_type = "midpoint between created and resolved";
        }
        
        echo "<h3>Applying Fix:</h3>";
        echo "<p><strong>Fix Type:</strong> $fix_type</p>";
        echo "<p><strong>New assigned_at:</strong> " . $assigned_at->format('Y-m-d H:i:s') . "</p>";
        
        // Apply the fix
        $update_query = "UPDATE service_requests 
                         SET assigned_at = :assigned_at 
                         WHERE id = 82";
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindValue(':assigned_at', $assigned_at->format('Y-m-d H:i:s'));
        $result = $update_stmt->execute();
        
        if ($result) {
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
            echo "<h4>SUCCESS: assigned_at updated</h4>";
            echo "</div>";
            
            // Verify the fix
            $verify_query = "SELECT id, title, created_at, assigned_at, resolved_at
                             FROM service_requests 
                             WHERE id = 82";
            
            $verify_stmt = $db->prepare($verify_query);
            $verify_stmt->execute();
            $updated_request = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($updated_request) {
                echo "<h3>Updated Request Details:</h3>";
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                echo "<tr><td>Created At</td><td>" . date('H:i:s d/m/Y', strtotime($updated_request['created_at'])) . "</td></tr>";
                echo "<tr><td>Assigned At</td><td style='background: #d4edda;'><strong>" . date('H:i:s d/m/Y', strtotime($updated_request['assigned_at'])) . "</strong></td></tr>";
                echo "<tr><td>Resolved At</td><td>" . date('H:i:s d/m/Y', strtotime($updated_request['resolved_at'])) . "</td></tr>";
                echo "</table>";
                
                // Calculate time differences
                $created = new DateTime($updated_request['created_at']);
                $assigned = new DateTime($updated_request['assigned_at']);
                $resolved = new DateTime($updated_request['resolved_at']);
                
                $response_time = $created->diff($assigned);
                $resolution_time = $assigned->diff($resolved);
                
                echo "<h3>Timeline Analysis:</h3>";
                echo "<ul>";
                echo "<li><strong>Response Time:</strong> " . $response_time->format('%h') . "h " . $response_time->format('%i') . "m</li>";
                echo "<li><strong>Resolution Time:</strong> " . $resolution_time->format('%h') . "h " . $resolution_time->format('%i') . "m</li>";
                echo "<li><strong>Total Time:</strong> " . $created->diff($resolved)->format('%h') . "h " . $created->diff($resolved)->format('%i') . "m</li>";
                echo "</ul>";
                
                echo "<h3>Expected Frontend Display:</h3>";
                echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
                echo "<p><strong>ID yêu yêu:</strong> #82</p>";
                echo "<p><strong>Tiêu yêu:</strong> " . htmlspecialchars($updated_request['title']) . "</p>";
                echo "<p><strong>Ngày yêu:</strong> " . date('H:i:s d/m/Y', strtotime($updated_request['created_at'])) . "</p>";
                echo "<p><strong>Ngày nhân:</strong> " . date('H:i:s d/m/Y', strtotime($updated_request['assigned_at'])) . "</p>";
                echo "<p><strong>Ngày yêu:</strong> " . date('H:i:s d/m/Y', strtotime($updated_request['resolved_at'])) . "</p>";
                echo "</div>";
                
                echo "<h3>Next Steps:</h3>";
                echo "<ol>";
                echo "<li><a href='index.html' target='_blank'>Open main application</a></li>";
                echo "<li>Navigate to request #82</li>";
                echo "<li>Check if 'Ngày nhân:' is displayed</li>";
                echo "<li>Verify the time matches: " . date('H:i:s d/m/Y', strtotime($updated_request['assigned_at'])) . "</li>";
                echo "</ol>";
            }
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
            echo "<h4>ERROR: Failed to update assigned_at</h4>";
            echo "</div>";
        }
        
    } else {
        echo "<p>Request #82 not found</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
