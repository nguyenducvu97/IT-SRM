<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Test Resolve Notifications Fix</h2>";
    
    echo "<h3>Problem Identified:</h3>";
    echo "<p><strong>Issue:</strong> When staff resolves a request (in_progress -> resolved), admin and user don't receive notifications</p>";
    echo "<p><strong>Root Cause:</strong> handleResolveRequest function was missing notification logic</p>";
    
    echo "<h3>Fix Applied:</h3>";
    echo "<p><strong>Solution:</strong> Added notification logic to handleResolveRequest function</p>";
    echo "<ul>";
    echo "<li>Notify user that their request has been resolved</li>";
    echo "<li>Notify all admins about the resolution</li>";
    echo "<li>Include staff name and request title in notifications</li>";
    echo "</ul>";
    
    echo "<h3>Notification Flow:</h3>";
    echo "<ol>";
    echo "<li>Staff clicks 'Giã Quyãt' button</li>";
    echo "<li>handleResolveRequest function processes the resolution</li>";
    echo "<li>Database updates: status = 'resolved', resolution record inserted</li>";
    echo "<li>NEW: Notifications sent to user and admins</li>";
    echo "<li>User receives: 'Yêu yêu #XX yêu yêu yêu yêu'</li>";
    echo "<li>Admin receives: 'Yêu yêu #XX yêu yêu yêu yêu'</li>";
    echo "</ol>";
    
    echo "<h3>Expected Notification Messages:</h3>";
    echo "<div style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0;'>";
    echo "<p><strong>User Notification:</strong></p>";
    echo "<p>Title: Yêu yêu #XX yêu yêu yêu yêu</p>";
    echo "<p>Message: [Staff Name] yêu yêu yêu yêu: [Request Title]</p>";
    echo "<p>Type: success</p>";
    echo "</div>";
    
    echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
    echo "<p><strong>Admin Notification:</strong></p>";
    echo "<p>Title: Yêu yêu #XX yêu yêu yêu yêu</p>";
    echo "<p>Message: [Staff Name] yêu yêu yêu yêu: [Request Title]</p>";
    echo "<p>Type: success</p>";
    echo "</div>";
    
    echo "<h3>Code Changes:</h3>";
    echo "<p><strong>File:</strong> api/service_requests.php</p>";
    echo "<p><strong>Function:</strong> handleResolveRequest</p>";
    echo "<p><strong>Lines:</strong> 8091-8167 (notification logic added)</p>";
    
    echo "<h3>Test Steps:</h3>";
    echo "<ol>";
    echo "<li>Find a request with status 'in_progress' assigned to staff</li>";
    echo "<li>Staff clicks 'Giã Quyãt' button</li>";
    echo "<li>Fill in resolution form (error description, error type, solution method)</li>";
    echo "<li>Submit the form</li>";
    echo "<li>Check notifications:</li>";
    echo "<ul>";
    echo "<li>User should receive notification about resolution</li>";
    echo "<li>All admins should receive notification about resolution</li>";
    echo "<li>Check logs for 'RESOLVE NOTIFICATIONS SENT' message</li>";
    echo "</ul>";
    echo "</ol>";
    
    // Check for existing requests that can be tested
    echo "<h3>Available Test Requests:</h3>";
    
    $test_query = "SELECT sr.id, sr.title, sr.status, sr.assigned_to, u.full_name as assigned_name
                  FROM service_requests sr
                  LEFT JOIN users u ON sr.assigned_to = u.id
                  WHERE sr.status = 'in_progress' AND sr.assigned_to IS NOT NULL
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
            echo "<td><a href='index.html' target='_blank'>Test Resolution</a></td>";
            echo "</tr>";
        }
        
        echo "</table>";
        echo "<p><strong>Note:</strong> Login as the assigned staff member to test resolution</p>";
    } else {
        echo "<p>No requests with 'in_progress' status found for testing</p>";
        echo "<p>Create a new request and assign it to staff, then change status to 'in_progress' to test</p>";
    }
    
    echo "<h3>Debug Information:</h3>";
    echo "<p>Check logs for these messages:</p>";
    echo "<ul>";
    echo "<li><code>RESOLVE NOTIFICATIONS SENT - User: [user_id], Admins notified</code></li>";
    echo "<li><code>RESOLVE NOTIFICATION ERROR: [error_message]</code> (if any issues)</li>";
    echo "</ul>";
    
    echo "<p>Log file location: logs/api_errors.log</p>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
