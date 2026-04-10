<?php
require_once 'config/database.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Check Request #82</h2>";
    
    // Get request details
    $query = "SELECT id, title, status, assigned_to, created_at, assigned_at, resolved_at, updated_at
              FROM service_requests 
              WHERE id = 82";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($request) {
        echo "<h3>Request Details:</h3>";
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Field</th><th>Value</th></tr>";
        echo "<tr><td>ID</td><td>{$request['id']}</td></tr>";
        echo "<tr><td>Title</td><td>" . htmlspecialchars($request['title']) . "</td></tr>";
        echo "<tr><td>Status</td><td>{$request['status']}</td></tr>";
        echo "<tr><td>Assigned To</td><td>{$request['assigned_to']}</td></tr>";
        echo "<tr><td>Created At</td><td>{$request['created_at']}</td></tr>";
        echo "<tr><td>Assigned At</td><td>" . ($request['assigned_at'] ?: 'NULL') . "</td></tr>";
        echo "<tr><td>Resolved At</td><td>" . ($request['resolved_at'] ?: 'NULL') . "</td></tr>";
        echo "<tr><td>Updated At</td><td>{$request['updated_at']}</td></tr>";
        echo "</table>";
        
        // Check if assigned_at is NULL
        if (!$request['assigned_at']) {
            echo "<h3>Issue: assigned_at is NULL</h3>";
            echo "<p>This request was assigned before the assigned_at column was added or the assignment logic didn't set assigned_at.</p>";
            
            // Calculate reasonable assigned_at time
            $created_at = new DateTime($request['created_at']);
            $resolved_at = new DateTime($request['resolved_at']);
            
            echo "<h3>Suggested Fix:</h3>";
            echo "<p>Set assigned_at to a reasonable time between created and resolved:</p>";
            
            // Option 1: 30 minutes after created
            $option1 = clone $created_at;
            $option1->add(new DateInterval('PT30M'));
            
            // Option 2: Midpoint between created and resolved
            $interval = $created_at->diff($resolved_at);
            $half_interval_minutes = intval($interval->format('%i')) / 2;
            $option2 = clone $created_at;
            $option2->add(new DateInterval('PT' . $half_interval_minutes . 'M'));
            
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>Option</th><th>Assigned At</th><th>Action</th></tr>";
            echo "<tr>";
            echo "<td>30 min after created</td>";
            echo "<td>" . $option1->format('Y-m-d H:i:s') . "</td>";
            echo "<td><a href='fix-request-82-assigned-at.php?option=1'>Apply</a></td>";
            echo "</tr>";
            echo "<tr>";
            echo "<td>Midpoint</td>";
            echo "<td>" . $option2->format('Y-m-d H:i:s') . "</td>";
            echo "<td><a href='fix-request-82-assigned-at.php?option=2'>Apply</a></td>";
            echo "</tr>";
            echo "</table>";
            
        } else {
            echo "<h3>assigned_at is present</h3>";
            echo "<p>assigned_at: {$request['assigned_at']}</p>";
            echo "<p>The issue might be in the frontend display logic.</p>";
        }
        
        // Check if there are other requests with NULL assigned_at
        echo "<h3>Other Requests with NULL assigned_at:</h3>";
        
        $null_query = "SELECT id, title, status, assigned_to, created_at, resolved_at
                        FROM service_requests 
                        WHERE assigned_to IS NOT NULL 
                        AND assigned_at IS NULL
                        ORDER BY id DESC
                        LIMIT 10";
        
        $null_stmt = $db->prepare($null_query);
        $null_stmt->execute();
        $null_requests = $null_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($null_requests) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th><th>Created</th><th>Resolved</th></tr>";
            
            foreach ($null_requests as $req) {
                echo "<tr>";
                echo "<td>{$req['id']}</td>";
                echo "<td>" . htmlspecialchars(substr($req['title'], 0, 20)) . "...</td>";
                echo "<td>{$req['status']}</td>";
                echo "<td>{$req['assigned_to']}</td>";
                echo "<td>{$req['created_at']}</td>";
                echo "<td>" . ($req['resolved_at'] ?: 'NULL') . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            echo "<p><a href='fix-all-null-assigned-at.php'>Fix All NULL assigned_at</a></p>";
        } else {
            echo "<p>No other requests with NULL assigned_at found.</p>";
        }
        
    } else {
        echo "<p>Request #82 not found</p>";
    }
    
    echo "<h3>Troubleshooting:</h3>";
    echo "<ol>";
    echo "<li><strong>Check assigned_at column:</strong> Should contain timestamp when staff was assigned</li>";
    echo "<li><strong>Check assignment logic:</strong> Should set assigned_at = NOW() when assigning</li>";
    echo "<li><strong>Check frontend display:</strong> Should show assigned_at when present</li>";
    echo "<li><strong>Check API response:</strong> Should include assigned_at field</li>";
    echo "</ol>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
