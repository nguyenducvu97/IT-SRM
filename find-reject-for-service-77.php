<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Find Reject Request for Service Request #77</h2>";
    
    // Find reject request for service request #77
    $find_query = "SELECT rr.id, rr.reject_reason, rr.reject_details, rr.created_at, rr.status,
                          sr.title as service_request_title, sr.id as service_request_id,
                          requester.username as requester_name, rejecter.username as rejecter_name
                          FROM reject_requests rr 
                          LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
                          LEFT JOIN users requester ON sr.user_id = requester.id
                          LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id
                          WHERE sr.id = :id
                          ORDER BY rr.created_at DESC";
    
    $find_stmt = $db->prepare($find_query);
    $find_stmt->execute(['id' => 77]);
    $reject_requests = $find_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Reject Requests for Service Request #77:</h3>";
    
    if (count($reject_requests) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>Reject ID</th><th>Service ID</th><th>Service Title</th><th>Reason</th><th>Details</th><th>Status</th><th>Created</th><th>Action</th></tr>";
        
        foreach ($reject_requests as $request) {
            echo "<tr>";
            echo "<td><strong>{$request['id']}</strong></td>";
            echo "<td>#{$request['service_request_id']}</td>";
            echo "<td>" . htmlspecialchars($request['service_request_title']) . "</td>";
            echo "<td>" . htmlspecialchars($request['reject_reason']) . "</td>";
            echo "<td>" . htmlspecialchars($request['reject_details']) . "</td>";
            echo "<td>{$request['status']}</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "<td><a href='cleanup-reject-request.php?id={$request['id']}' target='_blank'>Cleanup</a></td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Get the latest reject request (most recent)
        $latest_request = $reject_requests[0];
        $reject_id = $latest_request['id'];
        
        echo "<h3>Check Attachments for Latest Reject Request #$reject_id:</h3>";
        
        // Get attachments
        $attachment_query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at
                              FROM reject_request_attachments 
                              WHERE reject_request_id = :id 
                              ORDER BY id";
        
        $attachment_stmt = $db->prepare($attachment_query);
        $attachment_stmt->bindValue(':id', $reject_id, PDO::PARAM_INT);
        $attachment_stmt->execute();
        $attachments = $attachment_stmt->fetchAll(PDO::FETCH_ASSOC);
        
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
                echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                echo "<h4>DUPLICATES FOUND!</h4>";
                echo "<p>Need to delete " . count($files_to_delete) . " duplicate attachments</p>";
                echo "<p><a href='cleanup-reject-request.php?id=$reject_id'>Run Cleanup for Request #$reject_id</a></p>";
                echo "</div>";
                
                // Auto-cleanup option
                echo "<h3>Auto-Cleanup:</h3>";
                echo "<p><a href='cleanup-reject-request.php?id=$reject_id&auto=1'>Auto-Cleanup Request #$reject_id</a></p>";
            } else {
                echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                echo "<h4>NO DUPLICATES</h4>";
                echo "<p>All " . count($attachments) . " attachments have unique original names</p>";
                echo "</div>";
            }
            
        } else {
            echo "<div class='info' style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0;'>";
            echo "<h4>No Attachments Found</h4>";
            echo "<p>Reject request #$reject_id has no attachments</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
        echo "<h4>No Reject Requests Found</h4>";
        echo "<p>No reject requests found for Service Request #77</p>";
        echo "</div>";
        
        // Check if service request #77 exists
        echo "<h3>Check if Service Request #77 Exists:</h3>";
        
        $service_query = "SELECT id, title, status, created_at FROM service_requests WHERE id = :id";
        $service_stmt = $db->prepare($service_query);
        $service_stmt->execute(['id' => 77]);
        $service_request = $service_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($service_request) {
            echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
            echo "<h4>Service Request #77 Found:</h4>";
            echo "<p><strong>Title:</strong> {$service_request['title']}</p>";
            echo "<p><strong>Status:</strong> {$service_request['status']}</p>";
            echo "<p><strong>Created:</strong> {$service_request['created_at']}</p>";
            echo "<p><strong>But no reject requests found for this service request</strong></p>";
            echo "</div>";
        } else {
            echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
            echo "<h4>Service Request #77 NOT Found</h4>";
            echo "</div>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
