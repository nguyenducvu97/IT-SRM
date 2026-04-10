<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Debug Request #81 API Response</h2>";
    
    // Test API response
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
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    echo "<p><strong>API URL:</strong> $api_url</p>";
    echo "<p><strong>HTTP Code:</strong> $http_code</p>";
    
    if ($api_response) {
        echo "<h3>Raw API Response:</h3>";
        echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto;'>";
        echo htmlspecialchars($api_response);
        echo "</pre>";
        
        $api_data = json_decode($api_response, true);
        
        if ($api_data && $api_data['success']) {
            echo "<h3>Parsed API Data:</h3>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            
            $request = $api_data['data'];
            
            // Key fields to check
            $key_fields = ['id', 'title', 'status', 'assigned_to', 'assigned_name', 'created_at', 'assigned_at', 'resolved_at'];
            
            foreach ($key_fields as $field) {
                $value = isset($request[$field]) ? $request[$field] : 'NOT FOUND';
                echo "<tr>";
                echo "<td><strong>$field</strong></td>";
                echo "<td>" . htmlspecialchars($value) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Check assigned_at specifically
            if (isset($request['assigned_at'])) {
                echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                echo "<h4>assigned_at FOUND in API response: {$request['assigned_at']}</h4>";
                echo "</div>";
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                echo "<h4>assigned_at NOT FOUND in API response</h4>";
                echo "</div>";
            }
            
            // Check database directly
            echo "<h3>Database Direct Query:</h3>";
            $db_query = "SELECT id, title, status, assigned_to, created_at, assigned_at, resolved_at 
                          FROM service_requests 
                          WHERE id = 81";
            
            $db_stmt = $db->prepare($db_query);
            $db_stmt->execute();
            $db_result = $db_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($db_result) {
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>Field</th><th>Database Value</th></tr>";
                
                foreach ($key_fields as $field) {
                    $value = isset($db_result[$field]) ? $db_result[$field] : 'NULL';
                    echo "<tr>";
                    echo "<td><strong>$field</strong></td>";
                    echo "<td>" . htmlspecialchars($value) . "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                if ($db_result['assigned_at']) {
                    echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                    echo "<h4>assigned_at FOUND in database: {$db_result['assigned_at']}</h4>";
                    echo "</div>";
                } else {
                    echo "<div style='background: #fff3cd; color: #856404; padding: 10px; margin: 10px 0;'>";
                    echo "<h4>assigned_at is NULL in database</h4>";
                    echo "<p>Need to assign this request to staff first</p>";
                    echo "</div>";
                }
            }
            
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
            echo "<h4>API Response: ERROR</h4>";
            echo "<p>" . ($api_data['message'] ?? 'Unknown error') . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
        echo "<h4>API Response: FAILED</h4>";
        echo "<p>Could not connect to API</p>";
        echo "</div>";
    }
    
    echo "<h3>Frontend Debug:</h3>";
    echo "<p>Check browser console for JavaScript errors</p>";
    echo "<p>Look for formatDate function calls</p>";
    echo "<p>Verify request.assigned_at is passed to template</p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
