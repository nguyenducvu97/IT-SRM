<?php
require_once 'config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Fix assigned_at for Existing Assigned Requests</h2>";
    
    // Check requests that have assigned_to but NULL assigned_at
    $check_query = "SELECT id, title, assigned_to, created_at, updated_at 
                   FROM service_requests 
                   WHERE assigned_to IS NOT NULL 
                   AND assigned_at IS NULL 
                   ORDER BY id";
    
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute();
    $requests = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Requests with assigned_to but NULL assigned_at:</h3>";
    
    if (count($requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Assigned To</th><th>Created</th><th>Updated</th></tr>";
        
        foreach ($requests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . htmlspecialchars($request['title']) . "</td>";
            echo "<td>{$request['assigned_to']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "<td>{$request['updated_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Fix assigned_at for these requests
        echo "<h3>Fixing assigned_at...</h3>";
        
        // Strategy: Use updated_at as assigned_at (when request was likely assigned)
        $fix_query = "UPDATE service_requests 
                      SET assigned_at = updated_at 
                      WHERE assigned_to IS NOT NULL 
                      AND assigned_at IS NULL";
        
        $fix_stmt = $db->prepare($fix_query);
        $result = $fix_stmt->execute();
        
        if ($result) {
            $affected_rows = $fix_stmt->rowCount();
            echo "<p style='color: green;'>Updated $affected_rows requests with assigned_at</p>";
            
            // Verify the fix
            echo "<h3>Verification:</h3>";
            
            $verify_query = "SELECT id, title, assigned_to, created_at, assigned_at, updated_at
                           FROM service_requests 
                           WHERE id IN (" . implode(',', array_column($requests, 'id')) . ")
                           ORDER BY id";
            
            $verify_stmt = $db->prepare($verify_query);
            $verify_stmt->execute();
            $updated_requests = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>Title</th><th>Assigned To</th><th>Created</th><th>Assigned At</th><th>Updated</th></tr>";
            
            foreach ($updated_requests as $request) {
                echo "<tr>";
                echo "<td>{$request['id']}</td>";
                echo "<td>" . htmlspecialchars($request['title']) . "</td>";
                echo "<td>{$request['assigned_to']}</td>";
                echo "<td>{$request['created_at']}</td>";
                echo "<td style='background: #d4edda;'><strong>{$request['assigned_at']}</strong></td>";
                echo "<td>{$request['updated_at']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Test API response for request #81
            echo "<h3>Test API for Request #81:</h3>";
            
            session_start();
            $_SESSION['user_id'] = 1;
            $_SESSION['role'] = 'admin';
            $_SESSION['username'] = 'admin';
            $_SESSION['full_name'] = 'System Administrator';
            
            $api_url = "http://localhost/it-service-request/api/service_requests.php?action=get&id=81";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            
            $api_response = curl_exec($ch);
            curl_close($ch);
            
            if ($api_response) {
                $api_data = json_decode($api_response, true);
                
                if ($api_data && $api_data['success']) {
                    $request_data = $api_data['data'];
                    
                    if (isset($request_data['assigned_at']) && $request_data['assigned_at']) {
                        echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                        echo "<h4>SUCCESS: assigned_at found in API response</h4>";
                        echo "<p>assigned_at: {$request_data['assigned_at']}</p>";
                        echo "</div>";
                        
                        echo "<h4>Expected Frontend Display:</h4>";
                        echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
                        echo "<p><strong>ID yêu yêu:</strong> #81</p>";
                        echo "<p><strong>Ngày yêu:</strong> 19:03:27 9/4/2026</p>";
                        echo "<p><strong>Ngày nhân:</strong> " . date('H:i:s d/m/Y', strtotime($request_data['assigned_at'])) . "</p>";
                        echo "</div>";
                        
                    } else {
                        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                        echo "<h4>ERROR: assigned_at still not found in API response</h4>";
                        echo "</div>";
                    }
                }
            }
            
        } else {
            echo "<p style='color: red;'>Failed to update assigned_at</p>";
        }
        
    } else {
        echo "<p>No requests need fixing</p>";
    }
    
    echo "<h3>Next Steps:</h3>";
    echo "<ol>";
    echo "<li>Refresh request details page for #81</li>";
    echo "<li>Check if 'Ngày nhân:' is displayed</li>";
    echo "<li>Test with other assigned requests</li>";
    echo "<li>Verify new assignments work correctly</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
