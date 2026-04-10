<?php
// Debug reject request attachments
require_once 'config/database.php';
require_once 'config/session.php';

startSession();

// Mock admin session
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';

try {
    $db = getDatabaseConnection();
    
    // Get reject request with attachments
    $query = "SELECT rr.*, 
                  sr.title as service_request_title, sr.id as service_request_id,
                  requester.username as requester_name,
                  rejecter.username as rejecter_name,
                  processor.username as processor_name,
                  GROUP_CONCAT(DISTINCT CONCAT(attachment.original_name, '|', attachment.filename, '|', attachment.file_size, '|', attachment.mime_type) SEPARATOR '||') as attachments
                  FROM reject_requests rr 
                  LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
                  LEFT JOIN users requester ON sr.user_id = requester.id
                  LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id
                  LEFT JOIN users processor ON rr.processed_by = processor.id
                  LEFT JOIN reject_request_attachments attachment ON rr.id = attachment.reject_request_id
                  WHERE rr.id = 36
                  GROUP BY rr.id";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reject_request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($reject_request) {
        echo "<h2>Reject Request ID: 36</h2>";
        echo "<p><strong>Service Request:</strong> {$reject_request['service_request_title']}</p>";
        echo "<p><strong>Rejecter:</strong> {$reject_request['rejecter_name']}</p>";
        echo "<p><strong>Reason:</strong> {$reject_request['reject_reason']}</p>";
        
        // Process attachments
        $attachments = [];
        if (!empty($reject_request['attachments'])) {
            $attachment_strings = explode('||', $reject_request['attachments']);
            foreach ($attachment_strings as $attachment_string) {
                if (!empty($attachment_string)) {
                    $parts = explode('|', $attachment_string);
                    if (count($parts) >= 4) {
                        $attachments[] = [
                            'original_name' => $parts[0],
                            'filename' => $parts[1],
                            'file_size' => $parts[2],
                            'mime_type' => $parts[3]
                        ];
                    }
                }
            }
        }
        
        echo "<h3>Attachments (" . count($attachments) . ")</h3>";
        foreach ($attachments as $attachment) {
            $isImage = strpos($attachment['mime_type'], 'image/') === 0;
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 10px 0;'>";
            echo "<p><strong>Original Name:</strong> {$attachment['original_name']}</p>";
            echo "<p><strong>Filename:</strong> {$attachment['filename']}</p>";
            echo "<p><strong>Size:</strong> " . number_format($attachment['file_size']) . " bytes</p>";
            echo "<p><strong>MIME Type:</strong> {$attachment['mime_type']}</p>";
            echo "<p><strong>Is Image:</strong> " . ($isImage ? 'YES' : 'NO') . "</p>";
            
            if ($isImage) {
                echo "<img src='api/reject_request_attachment.php?file={$attachment['filename']}&action=view' 
                         alt='{$attachment['original_name']}' 
                         style='max-width: 200px; height: auto; border: 1px solid #ccc;' />";
            }
            
            echo "<p><a href='api/reject_request_attachment.php?file={$attachment['filename']}&action=download' 
                         target='_blank'>Download</a></p>";
            echo "</div>";
        }
    } else {
        echo "<p>Reject request not found</p>";
    }
    
} catch (Exception $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>
