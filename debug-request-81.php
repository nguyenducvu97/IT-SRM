<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Debug Request #81</h2>";
    
    // First, check if reject request #81 exists
    echo "<h3>Check if Reject Request #81 Exists:</h3>";
    
    $check_request_query = "SELECT id, reject_reason, reject_details, created_at, status, service_request_id
                             FROM reject_requests 
                             WHERE id = 81";
    
    $check_request_stmt = $db->prepare($check_request_query);
    $check_request_stmt->execute();
    $reject_request = $check_request_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reject_request) {
        echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
        echo "<h4>Reject Request #81 Found:</h4>";
        echo "<p><strong>ID:</strong> {$reject_request['id']}</p>";
        echo "<p><strong>Service Request ID:</strong> {$reject_request['service_request_id']}</p>";
        echo "<p><strong>Reason:</strong> " . htmlspecialchars($reject_request['reject_reason']) . "</p>";
        echo "<p><strong>Details:</strong> " . htmlspecialchars($reject_request['reject_details']) . "</p>";
        echo "<p><strong>Status:</strong> {$reject_request['status']}</p>";
        echo "<p><strong>Created:</strong> {$reject_request['created_at']}</p>";
        echo "</div>";
        
        // Check attachments for this request
        echo "<h3>Check Attachments for Request #81:</h3>";
        
        $attachment_query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at
                             FROM reject_request_attachments 
                             WHERE reject_request_id = 81 
                             ORDER BY id";
        
        $attachment_stmt = $db->prepare($attachment_query);
        $attachment_stmt->execute();
        $attachments = $attachment_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total Attachments Found:</strong> " . count($attachments) . "</p>";
        
        if (count($attachments) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>Original Name</th><th>Filename</th><th>Size</th><th>MIME</th><th>Uploaded</th></tr>";
            
            foreach ($attachments as $attachment) {
                echo "<tr>";
                echo "<td>{$attachment['id']}</td>";
                echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
                echo "<td>{$attachment['filename']}</td>";
                echo "<td>" . number_format($attachment['file_size']) . "</td>";
                echo "<td>{$attachment['mime_type']}</td>";
                echo "<td>{$attachment['uploaded_at']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            // Check for duplicates by original name
            $original_names = array_column($attachments, 'original_name');
            $unique_original_names = array_unique($original_names);
            
            if (count($original_names) !== count($unique_original_names)) {
                echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                echo "<h4>DUPLICATES FOUND!</h4>";
                echo "<p>Found " . count($original_names) . " attachments but only " . count($unique_original_names) . " unique original names</p>";
                echo "<p><a href='cleanup-request-81.php'>Run Cleanup</a></p>";
                echo "</div>";
            } else {
                echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                echo "<h4>NO DUPLICATES</h4>";
                echo "<p>All " . count($attachments) . " attachments have unique original names</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='info' style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0;'>";
            echo "<h4>No Attachments Found</h4>";
            echo "<p>Reject request #81 has no attachments in the database</p>";
            echo "</div>";
        }
        
        // Test the API response
        echo "<h3>Test API Response for Request #81:</h3>";
        
        $api_query = "SELECT rr.*, 
                          sr.title as service_request_title, sr.id as service_request_id,
                          requester.username as requester_name,
                          rejecter.username as rejecter_name,
                          processor.username as processor_name,
                          GROUP_CONCAT(DISTINCT 
                            CASE 
                                WHEN attachment.original_name IS NOT NULL AND attachment.filename IS NOT NULL 
                                THEN CONCAT(attachment.original_name, '|', attachment.filename, '|', COALESCE(attachment.file_size, 0), '|', COALESCE(attachment.mime_type, 'application/octet-stream'))
                                ELSE NULL 
                            END 
                            ORDER BY attachment.id 
                            SEPARATOR '||'
                        ) as attachments
                          FROM reject_requests rr 
                          LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
                          LEFT JOIN users requester ON sr.user_id = requester.id
                          LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id
                          LEFT JOIN users processor ON rr.processed_by = processor.id
                          LEFT JOIN reject_request_attachments attachment ON rr.id = attachment.reject_request_id
                          WHERE rr.id = 81
                          GROUP BY rr.id";
        
        $api_stmt = $db->prepare($api_query);
        $api_stmt->execute();
        $api_result = $api_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($api_result) {
            echo "<p><strong>API Query Result:</strong></p>";
            echo "<p><strong>Attachments String:</strong> " . htmlspecialchars($api_result['attachments'] ?? 'NULL') . "</p>";
            
            // Process attachments like the API does
            $processed_attachments = [];
            if (!empty($api_result['attachments'])) {
                $attachment_strings = explode('||', $api_result['attachments']);
                foreach ($attachment_strings as $attachment_string) {
                    if (!empty($attachment_string) && trim($attachment_string) !== '') {
                        $parts = explode('|', $attachment_string);
                        if (count($parts) >= 4 && !empty($parts[0]) && !empty($parts[1])) {
                            $processed_attachments[] = [
                                'original_name' => trim($parts[0]),
                                'filename' => trim($parts[1]),
                                'file_size' => intval($parts[2]),
                                'mime_type' => trim($parts[3])
                            ];
                        }
                    }
                }
            }
            
            echo "<h4>API Processed Attachments (" . count($processed_attachments) . "):</h4>";
            foreach ($processed_attachments as $index => $attachment) {
                echo ($index + 1) . ". {$attachment['original_name']} ({$attachment['filename']}) - " . number_format($attachment['file_size']) . " bytes<br>";
            }
        } else {
            echo "<p><strong>API Query Result:</strong> No data found</p>";
        }
        
    } else {
        echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
        echo "<h4>Reject Request #81 NOT FOUND</h4>";
        echo "<p>This reject request does not exist in the database</p>";
        echo "</div>";
        
        // Check all reject requests to find the correct one
        echo "<h3>All Reject Requests:</h3>";
        
        $all_query = "SELECT id, reject_reason, created_at FROM reject_requests ORDER BY id DESC LIMIT 10";
        $all_stmt = $db->prepare($all_query);
        $all_stmt->execute();
        $all_requests = $all_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Reason</th><th>Created</th></tr>";
        
        foreach ($all_requests as $request) {
            echo "<tr>";
            echo "<td>{$request['id']}</td>";
            echo "<td>" . htmlspecialchars(substr($request['reject_reason'], 0, 50)) . "...</td>";
            echo "<td>{$request['created_at']}</td>";
            echo "</tr>";
        }
        
        echo "</table>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
