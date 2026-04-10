<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Debug Resolve API</h2>";
    
    // Find a test request
    echo "<h3>Available Test Requests:</h3>";
    
    $test_query = "SELECT sr.id, sr.title, sr.status, sr.assigned_to, u.full_name as assigned_name
                   FROM service_requests sr
                   LEFT JOIN users u ON sr.assigned_to = u.id
                   WHERE sr.status = 'in_progress' 
                   AND sr.assigned_to IS NOT NULL 
                   ORDER BY sr.id DESC 
                   LIMIT 5";
    
    $test_stmt = $db->prepare($test_query);
    $test_stmt->execute();
    $test_requests = $test_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($test_requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th><th>Action</th></tr>";
        
        foreach ($test_requests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . htmlspecialchars($request['title']) . "</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['assigned_name']}</td>";
            echo "<td><a href='?test_id={$request['id']}'>Test Resolve</a></td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Test specific request if requested
        if (isset($_GET['test_id'])) {
            $test_id = (int)$_GET['test_id'];
            
            echo "<h3>Testing Resolve for Request #$test_id</h3>";
            
            // Start session as staff
            session_start();
            $_SESSION['user_id'] = 2; // John Smith (staff)
            $_SESSION['role'] = 'staff';
            $_SESSION['username'] = 'johnsmith';
            $_SESSION['full_name'] = 'John Smith';
            
            // Test resolve API call
            $api_url = "http://localhost/it-service-request/api/service_requests.php";
            
            // Create FormData like frontend does
            $post_data = [
                'action' => 'resolve',
                'id' => $test_id,
                'error_description' => 'Test error description',
                'error_type' => 'Test error type',
                'replacement_materials' => 'Test replacement materials',
                'solution_method' => 'Test solution method'
            ];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $api_url);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_HEADER, true);
            
            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $headers = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            
            curl_close($ch);
            
            echo "<h4>API Request Details:</h4>";
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>Parameter</th><th>Value</th></tr>";
            foreach ($post_data as $key => $value) {
                echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
            }
            echo "</table>";
            
            echo "<h4>HTTP Response:</h4>";
            echo "<p><strong>HTTP Code:</strong> $http_code</p>";
            echo "<p><strong>Headers:</strong></p>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; max-height: 200px; overflow-y: auto;'>";
            echo htmlspecialchars($headers);
            echo "</pre>";
            
            echo "<p><strong>Response Body:</strong></p>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto;'>";
            echo htmlspecialchars($body);
            echo "</pre>";
            
            // Parse JSON response
            $response_data = json_decode($body, true);
            
            if ($response_data) {
                echo "<h4>Parsed Response:</h4>";
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                
                foreach ($response_data as $key => $value) {
                    if (is_array($value)) {
                        echo "<tr><td>$key</td><td><pre>" . htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT)) . "</pre></td></tr>";
                    } else {
                        echo "<tr><td>$key</td><td>" . htmlspecialchars($value) . "</td></tr>";
                    }
                }
                
                echo "</table>";
                
                if ($response_data['success']) {
                    echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                    echo "<h4>SUCCESS: Resolve API working correctly</h4>";
                    echo "<p>Message: {$response_data['message']}</p>";
                    echo "</div>";
                } else {
                    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                    echo "<h4>ERROR: Resolve API failed</h4>";
                    echo "<p>Message: {$response_data['message']}</p>";
                    echo "</div>";
                }
            } else {
                echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                echo "<h4>ERROR: Invalid JSON response</h4>";
                echo "<p>Response is not valid JSON</p>";
                echo "</div>";
            }
            
            // Check request status after test
            echo "<h4>Request Status After Test:</h4>";
            
            $status_query = "SELECT id, title, status, assigned_to, resolved_at, error_description, solution_method 
                            FROM service_requests 
                            WHERE id = :test_id";
            
            $status_stmt = $db->prepare($status_query);
            $status_stmt->bindValue(':test_id', $test_id);
            $status_stmt->execute();
            $status_result = $status_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($status_result) {
                echo "<table border='1' cellpadding='5' cellspacing='0'>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                echo "<tr><td>ID</td><td>{$status_result['id']}</td></tr>";
                echo "<tr><td>Title</td><td>" . htmlspecialchars($status_result['title']) . "</td></tr>";
                echo "<tr><td>Status</td><td>{$status_result['status']}</td></tr>";
                echo "<tr><td>Assigned To</td><td>{$status_result['assigned_to']}</td></tr>";
                echo "<tr><td>Resolved At</td><td>{$status_result['resolved_at']}</td></tr>";
                echo "<tr><td>Error Description</td><td>" . htmlspecialchars($status_result['error_description']) . "</td></tr>";
                echo "<tr><td>Solution Method</td><td>" . htmlspecialchars($status_result['solution_method']) . "</td></tr>";
                echo "</table>";
                
                if ($status_result['status'] === 'resolved') {
                    echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                    echo "<h4>Database Updated Successfully</h4>";
                    echo "<p>Request status changed to 'resolved'</p>";
                    echo "</div>";
                }
            }
        }
    } else {
        echo "<p>No in_progress requests found for testing</p>";
    }
    
    echo "<h3>Troubleshooting Guide:</h3>";
    echo "<ol>";
    echo "<li><strong>HTTP Code 200:</strong> Server responded successfully</li>";
    echo "<li><strong>JSON Response:</strong> Check if response is valid JSON</li>";
    echo "<li><strong>Success Field:</strong> Should be true for successful resolve</li>";
    echo "<li><strong>Database Update:</strong> Verify status changed to 'resolved'</li>";
    echo "<li><strong>Frontend Error:</strong> If API works but frontend shows error, check JavaScript console</li>";
    echo "</ol>";
    
    echo "<h3>Common Issues:</h3>";
    echo "<ul>";
    echo "<li><strong>Session Issue:</strong> User not logged in as staff</li>";
    echo "<li><strong>Permission Issue:</strong> User doesn't have staff role</li>";
    echo "<li><strong>Request Assignment:</strong> Request not assigned to current user</li>";
    echo "<li><strong>Missing Fields:</strong> Required fields not provided</li>";
    echo "<li><strong>Response Format:</strong> API returns non-JSON response</li>";
    echo "</ul>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
