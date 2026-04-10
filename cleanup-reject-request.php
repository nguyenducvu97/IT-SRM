<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    $reject_id = $_GET['id'] ?? 0;
    $auto_cleanup = $_GET['auto'] ?? false;
    
    echo "<h2>Cleanup Reject Request #$reject_id</h2>";
    
    if ($reject_id == 0) {
        echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
        echo "<h4>Invalid Request ID</h4>";
        echo "<p>Please provide a valid reject request ID</p>";
        echo "</div>";
        exit;
    }
    
    // Get reject request details
    $details_query = "SELECT rr.*, sr.title as service_request_title, sr.id as service_request_id,
                      requester.username as requester_name, rejecter.username as rejecter_name
                      FROM reject_requests rr 
                      LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
                      LEFT JOIN users requester ON sr.user_id = requester.id
                      LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id
                      WHERE rr.id = :id";
    
    $details_stmt = $db->prepare($details_query);
    $details_stmt->bindValue(':id', $reject_id, PDO::PARAM_INT);
    $details_stmt->execute();
    $details = $details_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$details) {
        echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
        echo "<h4>Reject Request #$reject_id NOT Found</h4>";
        echo "</div>";
        exit;
    }
    
    echo "<h3>Reject Request Details:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>Reject Request ID</td><td><strong>{$details['id']}</strong></td></tr>";
    echo "<tr><td>Service Request ID</td><td>#{$details['service_request_id']}</td></tr>";
    echo "<tr><td>Service Request Title</td><td>" . htmlspecialchars($details['service_request_title']) . "</td></tr>";
    echo "<tr><td>Requester</td><td>{$details['requester_name']}</td></tr>";
    echo "<tr><td>Rejecter</td><td>{$details['rejecter_name']}</td></tr>";
    echo "<tr><td>Reason</td><td>" . htmlspecialchars($details['reject_reason']) . "</td></tr>";
    echo "<tr><td>Details</td><td>" . htmlspecialchars($details['reject_details']) . "</td></tr>";
    echo "<tr><td>Status</td><td>{$details['status']}</td></tr>";
    echo "<tr><td>Created</td><td>{$details['created_at']}</td></tr>";
    echo "</table>";
    
    // Get attachments
    $attachment_query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at
                         FROM reject_request_attachments 
                         WHERE reject_request_id = :id 
                         ORDER BY id";
    
    $attachment_stmt = $db->prepare($attachment_query);
    $attachment_stmt->bindValue(':id', $reject_id, PDO::PARAM_INT);
    $attachment_stmt->execute();
    $attachments = $attachment_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Attachments for Request #$reject_id:</h3>";
    echo "<p><strong>Total Attachments:</strong> " . count($attachments) . "</p>";
    
    if (count($attachments) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Original Name</th><th>Filename</th><th>Size</th><th>MIME</th><th>Uploaded</th><th>Action</th></tr>";
        
        $files_to_keep = [];
        $files_to_delete = [];
        
        foreach ($attachments as $attachment) {
            $original_name = $attachment['original_name'];
            
            if (!isset($files_to_keep[$original_name])) {
                // First occurrence - keep this one
                $files_to_keep[$original_name] = $attachment;
                echo "<tr style='background: #d4edda;'>";
                echo "<td>{$attachment['id']}</td>";
                echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
                echo "<td><strong>{$attachment['filename']}</strong></td>";
                echo "<td>" . number_format($attachment['file_size']) . "</td>";
                echo "<td>{$attachment['mime_type']}</td>";
                echo "<td>{$attachment['uploaded_at']}</td>";
                echo "<td><strong>KEEP</strong></td>";
                echo "</tr>";
            } else {
                // Duplicate original name - mark for deletion
                $files_to_delete[] = $attachment;
                echo "<tr style='background: #f8d7da;'>";
                echo "<td>{$attachment['id']}</td>";
                echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
                echo "<td>{$attachment['filename']}</td>";
                echo "<td>" . number_format($attachment['file_size']) . "</td>";
                echo "<td>{$attachment['mime_type']}</td>";
                echo "<td>{$attachment['uploaded_at']}</td>";
                echo "<td><strong>DELETE</strong></td>";
                echo "</tr>";
            }
        }
        
        echo "</table>";
        
        echo "<h3>Summary:</h3>";
        echo "<p>Total attachments: " . count($attachments) . "</p>";
        echo "<p>Unique original names: " . count($files_to_keep) . "</p>";
        echo "<p>Duplicates to delete: " . count($files_to_delete) . "</p>";
        
        if (count($files_to_delete) > 0) {
            if ($auto_cleanup) {
                echo "<h3>Auto-Cleaning Duplicates...</h3>";
                
                $total_deleted = 0;
                foreach ($files_to_delete as $file_to_delete) {
                    $delete_query = "DELETE FROM reject_request_attachments WHERE id = :id";
                    $delete_stmt = $db->prepare($delete_query);
                    $delete_stmt->bindValue(':id', $file_to_delete['id']);
                    
                    $result = $delete_stmt->execute();
                    
                    if ($result) {
                        echo "<p>Deleted duplicate: ID {$file_to_delete['id']} - {$file_to_delete['original_name']}</p>";
                        $total_deleted++;
                    } else {
                        echo "<p style='color: red;'>Failed to delete: ID {$file_to_delete['id']} - {$file_to_delete['original_name']}</p>";
                    }
                }
                
                echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                echo "<strong>Auto-Cleanup Complete:</strong> Deleted $total_deleted duplicate attachments";
                echo "</div>";
                
                // Redirect back to verification
                echo "<p><a href='verify-cleanup.php?id=$reject_id'>Verify Cleanup Results</a></p>";
                
            } else {
                echo "<h3>Cleanup Options:</h3>";
                echo "<p><a href='cleanup-reject-request.php?id=$reject_id&auto=1'>Auto-Cleanup Request #$reject_id</a></p>";
                echo "<p><a href='find-reject-for-service-77.php'>Back to Service Request #77</a></p>";
            }
        } else {
            echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
            echo "<strong>No duplicates found</strong> - All original names are unique";
            echo "</div>";
        }
        
    } else {
        echo "<div class='info' style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0;'>";
        echo "<h4>No Attachments Found</h4>";
        echo "<p>Reject request #$reject_id has no attachments</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
