<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Cleanup Request #81</h2>";
    
    $reject_id = 81;
    
    // Get all attachments for this request
    $check_query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at
                   FROM reject_request_attachments 
                   WHERE reject_request_id = :id 
                   ORDER BY id";
    
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindValue(':id', $reject_id, PDO::PARAM_INT);
    $check_stmt->execute();
    $attachments = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Current Attachments for Request #$reject_id:</h3>";
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
        echo "<h3>Deleting Duplicates...</h3>";
        
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
        echo "<strong>Cleanup Complete:</strong> Deleted $total_deleted duplicate attachments";
        echo "</div>";
        
        // Test the fixed query
        echo "<h3>Test Fixed Query After Cleanup:</h3>";
        
        $test_query = "SELECT GROUP_CONCAT(DISTINCT 
            CASE 
                WHEN attachment.original_name IS NOT NULL AND attachment.filename IS NOT NULL 
                THEN CONCAT(attachment.original_name, '|', attachment.filename, '|', COALESCE(attachment.file_size, 0), '|', COALESCE(attachment.mime_type, 'application/octet-stream'))
                ELSE NULL 
            END 
            ORDER BY attachment.id 
            SEPARATOR '||'
        ) as attachments
                         FROM reject_requests rr 
                         LEFT JOIN reject_request_attachments attachment ON rr.id = attachment.reject_request_id
                         WHERE rr.id = :id
                         GROUP BY rr.id";
        
        $test_stmt = $db->prepare($test_query);
        $test_stmt->bindValue(':id', $reject_id, PDO::PARAM_INT);
        $test_stmt->execute();
        $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_result) {
            echo "<p><strong>Attachments String:</strong> " . htmlspecialchars($test_result['attachments']) . "</p>";
            
            // Process to verify no duplicates
            $processed_attachments = [];
            if (!empty($test_result['attachments'])) {
                $attachment_strings = explode('||', $test_result['attachments']);
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
            
            echo "<h4>Processed Attachments (" . count($processed_attachments) . "):</h4>";
            foreach ($processed_attachments as $index => $attachment) {
                echo ($index + 1) . ". {$attachment['original_name']} ({$attachment['filename']}) - " . number_format($attachment['file_size']) . " bytes<br>";
            }
            
            // Final verification
            $original_names = array_column($processed_attachments, 'original_name');
            $unique_original_names = array_unique($original_names);
            
            if (count($original_names) === count($unique_original_names)) {
                echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                echo "<strong>VERIFICATION SUCCESSFUL:</strong> No duplicate original names!";
                echo "</div>";
            } else {
                echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                echo "<strong>STILL HAS ISSUES:</strong> Duplicate original names remain";
                echo "</div>";
            }
        }
        
    } else {
        echo "<div class='info' style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0;'>";
        echo "<strong>No duplicates found</strong> - All original names are unique";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
