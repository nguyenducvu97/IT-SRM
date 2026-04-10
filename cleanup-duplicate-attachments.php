<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Cleanup Duplicate Attachments</h2>";
    
    // Find all reject requests with duplicate attachments
    $duplicate_query = "SELECT reject_request_id, filename, COUNT(*) as count, MIN(id) as keep_id
                        FROM reject_request_attachments 
                        GROUP BY reject_request_id, filename 
                        HAVING COUNT(*) > 1";
    
    $stmt = $db->prepare($duplicate_query);
    $stmt->execute();
    $duplicates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Found " . count($duplicates) . " files with duplicates</h3>";
    
    if (count($duplicates) > 0) {
        $total_deleted = 0;
        
        foreach ($duplicates as $dup) {
            echo "<h4>Reject Request ID: {$dup['reject_request_id']} - File: {$dup['filename']} ({$dup['count']} copies)</h4>";
            
            // Get all duplicate records except the one to keep
            $delete_query = "DELETE FROM reject_request_attachments 
                            WHERE reject_request_id = :reject_request_id 
                            AND filename = :filename 
                            AND id != :keep_id";
            
            $delete_stmt = $db->prepare($delete_query);
            $delete_stmt->bindValue(':reject_request_id', $dup['reject_request_id']);
            $delete_stmt->bindValue(':filename', $dup['filename']);
            $delete_stmt->bindValue(':keep_id', $dup['keep_id']);
            
            $result = $delete_stmt->execute();
            $deleted_count = $delete_stmt->rowCount();
            
            echo "&nbsp;&nbsp;&nbsp;&nbsp;Deleted {$deleted_count} duplicate(s), keeping ID {$dup['keep_id']}<br>";
            
            $total_deleted += $deleted_count;
        }
        
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
        echo "<strong>Cleanup Complete:</strong> Deleted {$total_deleted} duplicate attachments";
        echo "</div>";
        
    } else {
        echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
        echo "<strong>No duplicates found</strong>";
        echo "</div>";
    }
    
    // Show final state
    echo "<h3>Final Attachment Count by Reject Request:</h3>";
    
    $final_query = "SELECT rr.id, rr.reject_reason, COUNT(rra.id) as attachment_count
                    FROM reject_requests rr
                    LEFT JOIN reject_request_attachments rra ON rr.id = rra.reject_request_id
                    GROUP BY rr.id
                    ORDER BY rr.id DESC
                    LIMIT 10";
    
    $final_stmt = $db->prepare($final_query);
    $final_stmt->execute();
    $final_results = $final_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Reject ID</th><th>Reason</th><th>Attachment Count</th></tr>";
    
    foreach ($final_results as $result) {
        echo "<tr>";
        echo "<td>{$result['id']}</td>";
        echo "<td>" . htmlspecialchars(substr($result['reject_reason'], 0, 50)) . "...</td>";
        echo "<td>{$result['attachment_count']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
