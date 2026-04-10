<?php
require_once 'config/database.php';

echo "<h2>DEBUG REQUEST #88 - ACCEPTED TIME</h2>";

try {
    $pdo = getDatabaseConnection();
    
    // 1. Get request #88 details
    echo "<h3>1. Request #88 Details</h3>";
    
    $requestQuery = "SELECT sr.*, u.full_name as requester_name, assigned.full_name as assigned_name 
                   FROM service_requests sr 
                   LEFT JOIN users u ON sr.user_id = u.id 
                   LEFT JOIN users assigned ON sr.assigned_to = assigned.id 
                   WHERE sr.id = 88";
    $requestStmt = $pdo->prepare($requestQuery);
    $requestStmt->execute();
    $request = $requestStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>{$request['id']}</td></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($request['title']) . "</td></tr>";
        echo "<tr><td>Status</td><td>{$request['status']}</td></tr>";
        echo "<tr><td>Assigned To</td><td>{$request['assigned_to']} ({$request['assigned_name']})</td></tr>";
        echo "<tr><td>Created At</td><td>{$request['created_at']}</td></tr>";
        echo "<tr><td>Updated At</td><td>{$request['updated_at']}</td></tr>";
        echo "<tr><td>Assigned At</td><td>" . ($request['assigned_at'] ?? 'NULL') . "</td></tr>";
        echo "<tr><td>Accepted At</td><td>" . ($request['accepted_at'] ?? 'NULL') . "</td></tr>";
        echo "</table>";
        
        // Check if accepted_at exists and has value
        if (empty($request['accepted_at'])) {
            echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>ISSUE FOUND:</h4>";
            echo "<p><strong>accepted_at</strong> is NULL or empty</p>";
            echo "<p>This is why 'Thoi gian staff nhan' is not displaying</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>ACCEPTED AT FOUND:</h4>";
            echo "<p><strong>accepted_at:</strong> {$request['accepted_at']}</p>";
            echo "</div>";
        }
        
        // Check assigned_at
        if (empty($request['assigned_at'])) {
            echo "<div style='background: #fff3cd; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
            echo "<h4>ASSIGNED AT ISSUE:</h4>";
            echo "<p><strong>assigned_at</strong> is NULL or empty</p>";
            echo "</div>";
        }
        
    } else {
        echo "<p style='color: red;'>Request #88 not found</p>";
    }
    
    // 2. Check database schema
    echo "<h3>2. Database Schema Check</h3>";
    
    $schemaQuery = "DESCRIBE service_requests";
    $schemaStmt = $pdo->prepare($schemaQuery);
    $schemaStmt->execute();
    $columns = $schemaStmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
    
    $hasAcceptedAt = false;
    $hasAssignedAt = false;
    
    foreach ($columns as $column) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "<td>{$column['Default']}</td>";
        echo "<td>{$column['Extra']}</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'accepted_at') $hasAcceptedAt = true;
        if ($column['Field'] === 'assigned_at') $hasAssignedAt = true;
    }
    echo "</table>";
    
    if (!$hasAcceptedAt) {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>SCHEMA ISSUE:</h4>";
        echo "<p><strong>accepted_at</strong> column does not exist in database</p>";
        echo "<p>Need to add this column to store staff acceptance time</p>";
        echo "</div>";
    }
    
    if (!$hasAssignedAt) {
        echo "<div style='background: #f8d7da; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>SCHEMA ISSUE:</h4>";
        echo "<p><strong>assigned_at</strong> column does not exist in database</p>";
        echo "<p>Need to add this column to store assignment time</p>";
        echo "</div>";
    }
    
    // 3. Check API response
    echo "<h3>3. API Response Check</h3>";
    
    echo "<p>Testing API: <code>api/service_requests.php?action=get&id=88</code></p>";
    
    try {
        // Simulate API call
        $_GET['action'] = 'get';
        $_GET['id'] = '88';
        
        ob_start();
        include 'api/service_requests.php';
        $apiResponse = ob_get_clean();
        
        echo "<h5>API Response:</h5>";
        echo "<pre>" . htmlspecialchars($apiResponse) . "</pre>";
        
        $data = json_decode($apiResponse, true);
        if ($data && isset($data['success']) && $data['success']) {
            echo "<p style='color: green;'>API Response: Success</p>";
            
            if (isset($data['data']['accepted_at'])) {
                echo "<p><strong>accepted_at in API:</strong> " . ($data['data']['accepted_at'] ?? 'NULL') . "</p>";
            } else {
                echo "<p style='color: orange;'>accepted_at not found in API response</p>";
            }
            
            if (isset($data['data']['assigned_at'])) {
                echo "<p><strong>assigned_at in API:</strong> " . ($data['data']['assigned_at'] ?? 'NULL') . "</p>";
            } else {
                echo "<p style='color: orange;'>assigned_at not found in API response</p>";
            }
        } else {
            echo "<p style='color: red;'>API Response: Failed</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>API Error: " . $e->getMessage() . "</p>";
    }
    
    // 4. Fix accepted_at if needed
    echo "<h3>4. Fix Options</h3>";
    
    if ($request && empty($request['accepted_at'])) {
        echo "<div style='background: #d1ecf1; padding: 15px; margin: 10px 0; border-radius: 5px;'>";
        echo "<h4>SOLUTION:</h4>";
        echo "<p>Request #88 has status 'in_progress' but missing accepted_at</p>";
        echo "<p>Need to set accepted_at to when staff accepted the request</p>";
        echo "</div>";
        
        // Update accepted_at
        echo "<h4>Updating accepted_at...</h4>";
        
        $updateQuery = "UPDATE service_requests 
                       SET accepted_at = COALESCE(assigned_at, created_at, updated_at, NOW()) 
                       WHERE id = 88 AND (accepted_at IS NULL OR accepted_at = '0000-00-00 00:00:00')";
        $updateStmt = $pdo->prepare($updateQuery);
        
        if ($updateStmt->execute()) {
            echo "<p style='color: green;'>accepted_at updated successfully</p>";
            
            // Verify update
            $verifyQuery = "SELECT accepted_at, assigned_at, created_at, updated_at FROM service_requests WHERE id = 88";
            $verifyStmt = $pdo->prepare($verifyQuery);
            $verifyStmt->execute();
            $updated = $verifyStmt->fetch(PDO::FETCH_ASSOC);
            
            echo "<h5>Updated values:</h5>";
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>accepted_at</td><td>{$updated['accepted_at']}</td></tr>";
            echo "<tr><td>assigned_at</td><td>{$updated['assigned_at']}</td></tr>";
            echo "<tr><td>created_at</td><td>{$updated['created_at']}</td></tr>";
            echo "<tr><td>updated_at</td><td>{$updated['updated_at']}</td></tr>";
            echo "</table>";
            
        } else {
            echo "<p style='color: red;'>Failed to update accepted_at</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<h3>5. Next Steps</h3>";
echo "<ol>";
echo "<li><strong>Check accepted_at field:</strong> Verify it exists in database</li>";
echo "<li><strong>Update accepted_at:</strong> Set it when staff accepts request</li>";
echo "<li><strong>Update API:</strong> Ensure API returns accepted_at field</li>";
echo "<li><strong>Update frontend:</strong> Display 'Thoi gian staff nhan' from accepted_at</li>";
echo "<li><strong>Test workflow:</strong> Verify new requests show acceptance time</li>";
echo "</ol>";

echo "<p><a href='request-detail.html?id=88' target='_blank'>Test Request #88 Detail Page</a></p>";
?>
