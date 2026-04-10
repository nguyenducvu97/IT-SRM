<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Test New Request Assignment Logic</h2>";
    
    // Check if there are any new requests that need assignment
    echo "<h3>Unassigned Requests:</h3>";
    
    $unassigned_query = "SELECT id, title, status, created_at, updated_at
                          FROM service_requests 
                          WHERE status = 'open' 
                          AND assigned_to IS NULL
                          ORDER BY id DESC
                          LIMIT 5";
    
    $unassigned_stmt = $db->prepare($unassigned_query);
    $unassigned_stmt->execute();
    $unassigned_requests = $unassigned_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($unassigned_requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th><th>Action</th></tr>";
        
        foreach ($unassigned_requests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . htmlspecialchars($request['title']) . "</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "<td><a href='assign-request.php?id={$request['id']}'>Assign to Staff</a></td>";
            echo "</tr>";
        }
        
        echo "</table>";
    } else {
        echo "<p>No unassigned requests found</p>";
    }
    
    // Check recently assigned requests (last 24 hours)
    echo "<h3>Recently Assigned Requests (Last 24 Hours):</h3>";
    
    $recent_query = "SELECT sr.id, sr.title, sr.status, sr.assigned_to, sr.created_at, sr.assigned_at, sr.updated_at,
                        u.full_name as assigned_name
                        FROM service_requests sr
                        LEFT JOIN users u ON sr.assigned_to = u.id
                        WHERE sr.assigned_to IS NOT NULL
                        AND sr.created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                        ORDER BY sr.id DESC
                        LIMIT 10";
    
    $recent_stmt = $db->prepare($recent_query);
    $recent_stmt->execute();
    $recent_requests = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($recent_requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th><th>Created</th><th>Assigned At</th><th>Status</th></tr>";
        
        foreach ($recent_requests as $request) {
            $assigned_at_status = $request['assigned_at'] ? 'OK' : 'MISSING';
            $row_style = $request['assigned_at'] ? 'background: #d4edda;' : 'background: #f8d7da;';
            
            echo "<tr style='$row_style'>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($request['title'], 0, 30)) . "...</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['assigned_name']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "<td>" . ($request['assigned_at'] ?: 'NULL') . "</td>";
            echo "<td>$assigned_at_status</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Count how many have assigned_at vs NULL
        $with_assigned_at = 0;
        $without_assigned_at = 0;
        
        foreach ($recent_requests as $request) {
            if ($request['assigned_at']) {
                $with_assigned_at++;
            } else {
                $without_assigned_at++;
            }
        }
        
        echo "<h3>Assignment Status Summary:</h3>";
        echo "<ul>";
        echo "<li><strong>With assigned_at:</strong> $with_assigned_at requests</li>";
        echo "<li><strong>Without assigned_at:</strong> $without_assigned_at requests</li>";
        echo "<li><strong>Total:</strong> " . count($recent_requests) . " requests</li>";
        echo "</ul>";
        
        if ($without_assigned_at > 0) {
            echo "<div style='background: #fff3cd; color: #856404; padding: 10px; margin: 10px 0;'>";
            echo "<h4>ISSUE FOUND: $without_assigned_at requests missing assigned_at</h4>";
            echo "<p>These requests were assigned but assigned_at was not set.</p>";
            echo "<p><a href='fix-recent-assignments.php'>Fix assigned_at for recent requests</a></p>";
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
            echo "<h4>GOOD: All recent requests have assigned_at</h4>";
            echo "<p>New assignment logic is working correctly.</p>";
            echo "</div>";
        }
        
    } else {
        echo "<p>No assigned requests found in the last 24 hours</p>";
    }
    
    // Test assignment logic
    echo "<h3>Test Assignment Logic:</h3>";
    
    // Create a test request
    echo "<p><a href='create-test-request.php'>Create Test Request</a></p>";
    
    // Check assignment logic in API
    echo "<h3>Assignment Logic Check:</h3>";
    echo "<p>The assignment logic should set assigned_at = NOW() when:</p>";
    echo "<ul>";
    echo "<li>Admin assigns request to staff via PUT method</li>";
    echo "<li>Staff accepts request via POST method</li>";
    echo "<li>Any status change with assigned_to</li>";
    echo "</ul>";
    
    // Show the relevant API code locations
    echo "<h3>API Code Locations:</h3>";
    echo "<ul>";
    echo "<li><strong>POST method:</strong> api/service_requests.php line ~3690</li>";
    echo "<li><strong>PUT method:</strong> api/service_requests.php line ~7467</li>";
    echo "<li><strong>Frontend:</strong> assets/js/app.js assignment functions</li>";
    echo "</ul>";
    
    echo "<h3>Expected Behavior:</h3>";
    echo "<ol>";
    echo "<li>User creates request: status = 'open', assigned_to = NULL, assigned_at = NULL</li>";
    echo "<li>Admin assigns to staff: status = 'in_progress', assigned_to = staff_id, assigned_at = NOW()</li>";
    echo "<li>Frontend shows: 'Ngày nhân: [timestamp]'</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
