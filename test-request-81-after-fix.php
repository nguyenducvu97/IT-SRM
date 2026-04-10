<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Test Request #81 After Fix</h2>";
    
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
        $api_data = json_decode($api_response, true);
        
        if ($api_data && $api_data['success']) {
            $request = $api_data['data'];
            
            echo "<h3>API Response Key Fields:</h3>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            
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
            if (isset($request['assigned_at']) && $request['assigned_at']) {
                echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                echo "<h4>SUCCESS: assigned_at FOUND in API response</h4>";
                echo "<p>assigned_at: {$request['assigned_at']}</p>";
                echo "</div>";
                
                // Format for display
                echo "<h3>Expected Frontend Display:</h3>";
                echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
                echo "<p><strong>ID yêu yêu:</strong> #{$request['id']}</p>";
                echo "<p><strong>Tiêu yêu:</strong> " . htmlspecialchars($request['title']) . "</p>";
                echo "<p><strong>Ngày yêu:</strong> " . date('H:i:s d/m/Y', strtotime($request['created_at'])) . "</p>";
                
                if ($request['assigned_at']) {
                    echo "<p><strong>Ngày nhân:</strong> " . date('H:i:s d/m/Y', strtotime($request['assigned_at'])) . "</p>";
                }
                
                if ($request['resolved_at']) {
                    echo "<p><strong>Ngày yêu:</strong> " . date('H:i:s d/m/Y', strtotime($request['resolved_at'])) . "</p>";
                }
                
                echo "<p><strong>Trang thái:</strong> " . htmlspecialchars($request['status']) . "</p>";
                echo "<p><strong>Ngày nhân:</strong> " . htmlspecialchars($request['assigned_name']) . "</p>";
                echo "</div>";
                
                echo "<h3>Frontend Template Logic:</h3>";
                echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px;'>";
                echo "\${request.assigned_at ? \`";
                echo "<strong>Ngày nhân:</strong> \${formatDate(request.assigned_at)}";
                echo "\` : ''}";
                echo "</pre>";
                
                echo "<h3>Test Steps:</h3>";
                echo "<ol>";
                echo "<li><a href='index.html' target='_blank'>Open main application</a></li>";
                echo "<li>Navigate to request #81</li>";
                echo "<li>Look for 'Ngày nhân:' field in request details</li>";
                echo "<li>Verify the time matches: " . date('H:i:s d/m/Y', strtotime($request['assigned_at'])) . "</li>";
                echo "<li>Check browser console for any JavaScript errors</li>";
                echo "</ol>";
                
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                echo "<h4>ERROR: assigned_at still NULL or not found</h4>";
                echo "<p>assigned_at value: " . (isset($request['assigned_at']) ? var_export($request['assigned_at'], true) : 'NOT SET') . "</p>";
                echo "</div>";
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
    
    // Test a few more requests
    echo "<h3>Test Other Requests:</h3>";
    echo "<p>Quick test of other assigned requests:</p>";
    
    $test_requests = [79, 80, 78];
    
    foreach ($test_requests as $req_id) {
        echo "<h4>Request #$req_id:</h4>";
        
        $test_url = "http://localhost/it-service-request/api/service_requests.php?action=get&id=$req_id";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $test_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $test_response = curl_exec($ch);
        curl_close($ch);
        
        if ($test_response) {
            $test_data = json_decode($test_response, true);
            
            if ($test_data && $test_data['success']) {
                $test_request = $test_data['data'];
                
                echo "<p>";
                echo "<strong>Title:</strong> " . htmlspecialchars($test_request['title']) . "<br>";
                echo "<strong>assigned_at:</strong> " . ($test_request['assigned_at'] ?? 'NULL') . "<br>";
                
                if ($test_request['assigned_at']) {
                    echo "<strong>Formatted:</strong> " . date('H:i:s d/m/Y', strtotime($test_request['assigned_at'])) . " <span style='color: green;'>OK</span>";
                } else {
                    echo "<span style='color: red;'>MISSING</span>";
                }
                
                echo "</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
