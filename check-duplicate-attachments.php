<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Check for Duplicate Attachments</h2>";
    
    // Check for reject request #69
    $reject_id = 69;
    
    echo "<h3>Reject Request ID: $reject_id</h3>";
    
    // Check attachments in database
    $query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at 
              FROM reject_request_attachments 
              WHERE reject_request_id = :id 
              ORDER BY id";
    
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $reject_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Attachments in Database (" . count($attachments) . "):</h4>";
    
    foreach ($attachments as $attachment) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
        echo "<strong>ID:</strong> {$attachment['id']}<br>";
        echo "<strong>Original Name:</strong> {$attachment['original_name']}<br>";
        echo "<strong>Filename:</strong> {$attachment['filename']}<br>";
        echo "<strong>Size:</strong> " . number_format($attachment['file_size']) . " bytes<br>";
        echo "<strong>MIME Type:</strong> {$attachment['mime_type']}<br>";
        echo "<strong>Uploaded At:</strong> {$attachment['uploaded_at']}<br>";
        echo "</div>";
    }
    
    // Check for duplicates by filename
    echo "<h3>Check for Duplicates by Filename:</h3>";
    
    $duplicate_query = "SELECT filename, COUNT(*) as count 
                        FROM reject_request_attachments 
                        WHERE reject_request_id = :id 
                        GROUP BY filename 
                        HAVING COUNT(*) > 1";
    
    $dup_stmt = $db->prepare($duplicate_query);
    $dup_stmt->bindValue(':id', $reject_id, PDO::PARAM_INT);
    $dup_stmt->execute();
    
    $duplicates = $dup_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($duplicates) > 0) {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 10px;'>";
        echo "<strong>Found Duplicates:</strong><br>";
        foreach ($duplicates as $dup) {
            echo "- {$dup['filename']}: {$dup['count']} copies<br>";
        }
        echo "</div>";
        
        // Show details of duplicates
        echo "<h4>Duplicate Details:</h4>";
        foreach ($duplicates as $dup) {
            echo "<h5>File: {$dup['filename']}</h5>";
            $detail_query = "SELECT id, original_name, uploaded_at 
                            FROM reject_request_attachments 
                            WHERE reject_request_id = :id AND filename = :filename";
            $detail_stmt = $db->prepare($detail_query);
            $detail_stmt->bindValue(':id', $reject_id, PDO::PARAM_INT);
            $detail_stmt->bindValue(':filename', $dup['filename']);
            $detail_stmt->execute();
            $details = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($details as $detail) {
                echo "&nbsp;&nbsp;&nbsp;&nbsp;ID: {$detail['id']} - {$detail['original_name']} - {$detail['uploaded_at']}<br>";
            }
        }
    } else {
        echo "<div style='background: #d4edda; color: #155724; padding: 10px;'>";
        echo "<strong>No duplicates found in database</strong>";
        echo "</div>";
    }
    
    // Test the actual API response
    echo "<h3>Test API Response:</h3>";
    
    $api_query = "SELECT rr.*, 
                      sr.title as service_request_title, sr.id as service_request_id,
                      requester.username as requester_name,
                      rejecter.username as rejecter_name,
                      processor.username as processor_name,
                      GROUP_CONCAT(DISTINCT CONCAT(attachment.original_name, '|', attachment.filename) SEPARATOR '||') as attachments
                      FROM reject_requests rr 
                      LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
                      LEFT JOIN users requester ON sr.user_id = requester.id
                      LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id
                      LEFT JOIN users processor ON rr.processed_by = processor.id
                      LEFT JOIN reject_request_attachments attachment ON rr.id = attachment.reject_request_id
                      WHERE rr.id = :id
                      GROUP BY rr.id";
    
    $api_stmt = $db->prepare($api_query);
    $api_stmt->bindValue(':id', $reject_id, PDO::PARAM_INT);
    $api_stmt->execute();
    
    $api_result = $api_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($api_result) {
        echo "<h4>API Query Result:</h4>";
        echo "<strong>Attachments String:</strong> " . htmlspecialchars($api_result['attachments']) . "<br>";
        
        // Process the attachments string
        $processed_attachments = [];
        if (!empty($api_result['attachments'])) {
            $attachment_strings = explode('||', $api_result['attachments']);
            foreach ($attachment_strings as $attachment_string) {
                if (!empty($attachment_string)) {
                    $parts = explode('|', $attachment_string);
                    if (count($parts) >= 2) {
                        $processed_attachments[] = [
                            'original_name' => $parts[0],
                            'filename' => $parts[1]
                        ];
                    }
                }
            }
        }
        
        echo "<h4>Processed Attachments (" . count($processed_attachments) . "):</h4>";
        foreach ($processed_attachments as $index => $attachment) {
            echo ($index + 1) . ". {$attachment['original_name']} ({$attachment['filename']})<br>";
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
