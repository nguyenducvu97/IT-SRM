<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Test Assigned At Functionality</h2>";
    
    echo "<h3>Database Schema Check:</h3>";
    
    // Check if assigned_at column exists
    $schema_query = "DESCRIBE service_requests";
    $schema_stmt = $db->prepare($schema_query);
    $schema_stmt->execute();
    $columns = $schema_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_assigned_at = false;
    foreach ($columns as $column) {
        if ($column['Field'] === 'assigned_at') {
            $has_assigned_at = true;
            echo "<p>assigned_at column exists: {$column['Type']} ({$column['Null']})</p>";
            break;
        }
    }
    
    if (!$has_assigned_at) {
        echo "<p style='color: red;'>assigned_at column NOT found</p>";
        echo "<p><a href='add_assigned_at.php'>Run add_assigned_at.php</a></p>";
    } else {
        echo "<h3>Sample Requests with assigned_at:</h3>";
        
        $sample_query = "SELECT id, title, status, assigned_to, created_at, assigned_at, 
                          assigned.full_name as assigned_name,
                          TIMESTAMPDIFF(MINUTE, created_at, assigned_at) as response_minutes
                          FROM service_requests sr
                          LEFT JOIN users assigned ON sr.assigned_to = assigned.id
                          WHERE assigned_to IS NOT NULL AND assigned_at IS NOT NULL
                          ORDER BY id DESC
                          LIMIT 5";
        
        $sample_stmt = $db->prepare($sample_query);
        $sample_stmt->execute();
        $requests = $sample_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($requests) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th><th>Created</th><th>Assigned At</th><th>Response Time</th></tr>";
            
            foreach ($requests as $request) {
                echo "<tr>";
                echo "<td>{$request['id']}</td>";
                echo "<td>" . htmlspecialchars(substr($request['title'], 0, 30)) . "...</td>";
                echo "<td>{$request['status']}</td>";
                echo "<td>{$request['assigned_name']}</td>";
                echo "<td>{$request['created_at']}</td>";
                echo "<td><strong>{$request['assigned_at']}</strong></td>";
                echo "<td>" . ($request['response_minutes'] ? $request['response_minutes'] . ' min' : 'N/A') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
        } else {
            echo "<p>No requests with assigned_at found</p>";
        }
        
        echo "<h3>Test API Response:</h3>";
        
        session_start();
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = 'admin';
        $_SESSION['full_name'] = 'System Administrator';
        
        // Test API for a specific request
        $test_request_id = 79;
        $api_url = "http://localhost/it-service-request/api/service_requests.php?action=get&id=$test_request_id";
        
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
                echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                echo "<h4>API Response: SUCCESS</h4>";
                
                if (isset($api_data['data']['assigned_at'])) {
                    echo "<p><strong>assigned_at in API:</strong> {$api_data['data']['assigned_at']}</p>";
                } else {
                    echo "<p><strong>assigned_at:</strong> NOT FOUND in API response</p>";
                }
                
                echo "</div>";
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
    }
    
    echo "<h3>Expected Display in Frontend:</h3>";
    echo "<div style='background: #f8f9fa; padding: 15px; border: 1px solid #ddd; border-radius: 5px;'>";
    echo "<p><strong>Current display:</strong></p>";
    echo "<p>ID yêu yêu: #79</p>";
    echo "<p>Ngày yêu: 18:34:20 9/4/2026</p>";
    echo "<p>Ngày yêu: 10:38:40 10/4/2026</p>";
    echo "<br>";
    echo "<p><strong>With assigned_at:</strong></p>";
    echo "<p>ID yêu yêu: #79</p>";
    echo "<p>Ngày yêu: 18:34:20 9/4/2026</p>";
    echo "<p><strong>Ngày nhân:</strong> [assigned_at time]</p>";
    echo "<p>Ngày yêu: 10:38:40 10/4/2026</p>";
    echo "</div>";
    
    echo "<h3>Frontend Implementation:</h3>";
    echo "<p>request-detail.js already has the display logic:</p>";
    echo "<pre><code>\${request.assigned_at ? \`
    <strong>Ngày nhân:</strong> \${formatDate(request.assigned_at)}
\` : ''}</code></pre>";
    
    echo "<h3>Test Steps:</h3>";
    echo "<ol>";
    echo "<li>Run this test script to verify assigned_at in API</li>";
    echo "<li>Check request details in main application</li>";
    echo "<li>Look for 'Ngày nhân:' field in request details</li>";
    echo "<li>Verify assigned_at time is displayed correctly</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
