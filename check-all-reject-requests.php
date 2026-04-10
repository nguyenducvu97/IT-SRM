<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Check All Reject Requests and Attachments</h2>";
    
    // Get all reject requests with attachment counts
    $query = "SELECT rr.id, rr.reject_reason, rr.created_at,
                      COUNT(rra.id) as attachment_count,
                      GROUP_CONCAT(DISTINCT rra.filename ORDER BY rra.id) as filenames
              FROM reject_requests rr
              LEFT JOIN reject_request_attachments rra ON rr.id = rra.reject_request_id
              GROUP BY rr.id
              ORDER BY rr.id DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reject_requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>All Reject Requests (" . count($reject_requests) . "):</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0' width='100%'>";
    echo "<tr><th>ID</th><th>Reason</th><th>Created</th><th>Attachments</th><th>Filenames</th><th>Action</th></tr>";
    
    foreach ($reject_requests as $request) {
        echo "<tr>";
        echo "<td><strong>{$request['id']}</strong></td>";
        echo "<td>" . htmlspecialchars(substr($request['reject_reason'], 0, 50)) . "...</td>";
        echo "<td>{$request['created_at']}</td>";
        echo "<td>{$request['attachment_count']}</td>";
        echo "<td>" . htmlspecialchars($request['filenames']) . "</td>";
        echo "<td><a href='test-reject-request-detail.php?id={$request['id']}' target='_blank'>Test</a></td>";
        echo "</tr>";
        
        // Check for duplicates in filenames
        if ($request['filenames']) {
            $filenames = explode(',', $request['filenames']);
            $unique_filenames = array_unique($filenames);
            if (count($filenames) !== count($unique_filenames)) {
                echo "<tr><td colspan='6' style='background: #fff3cd;'>";
                echo "<strong>DUPLICATES FOUND in Request #{$request['id']}!</strong><br>";
                echo "Files: " . htmlspecialchars($request['filenames']);
                echo "</td></tr>";
            }
        }
    }
    
    echo "</table>";
    
    // Check specific request #66 (which has 4 attachments)
    echo "<h3>Detailed Check - Request #66:</h3>";
    
    $detail_query = "SELECT rra.id, rra.original_name, rra.filename, rra.file_size, rra.mime_type, rra.uploaded_at
                     FROM reject_request_attachments rra
                     WHERE rra.reject_request_id = 66
                     ORDER BY rra.id";
    
    $detail_stmt = $db->prepare($detail_query);
    $detail_stmt->execute();
    $attachments = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
    
    // Test the fixed API query for request #66
    echo "<h3>Test Fixed API Query for Request #66:</h3>";
    
    $api_query = "SELECT rr.*, 
                      sr.title as service_request_title, sr.id as service_request_id,
                      requester.username as requester_name,
                      rejecter.username as rejecter_name,
                      processor.username as processor_name,
                      GROUP_CONCAT(DISTINCT CONCAT(attachment.original_name, '|', attachment.filename, '|', attachment.file_size, '|', attachment.mime_type) ORDER BY attachment.id SEPARATOR '||') as attachments
                      FROM reject_requests rr 
                      LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
                      LEFT JOIN users requester ON sr.user_id = requester.id
                      LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id
                      LEFT JOIN users processor ON rr.processed_by = processor.id
                      LEFT JOIN reject_request_attachments attachment ON rr.id = attachment.reject_request_id
                      WHERE rr.id = 66
                      GROUP BY rr.id";
    
    $api_stmt = $db->prepare($api_query);
    $api_stmt->execute();
    $api_result = $api_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($api_result) {
        echo "<p><strong>API Query Result:</strong></p>";
        echo "<p>Attachments String: " . htmlspecialchars($api_result['attachments']) . "</p>";
        
        // Process attachments
        $processed_attachments = [];
        if (!empty($api_result['attachments'])) {
            $attachment_strings = explode('||', $api_result['attachments']);
            foreach ($attachment_strings as $attachment_string) {
                if (!empty($attachment_string)) {
                    $parts = explode('|', $attachment_string);
                    if (count($parts) >= 4) {
                        $processed_attachments[] = [
                            'original_name' => $parts[0],
                            'filename' => $parts[1],
                            'file_size' => $parts[2],
                            'mime_type' => $parts[3]
                        ];
                    }
                }
            }
        }
        
        echo "<h4>Processed Attachments (" . count($processed_attachments) . "):</h4>";
        foreach ($processed_attachments as $index => $attachment) {
            echo ($index + 1) . ". {$attachment['original_name']} ({$attachment['filename']}) - " . 
                 number_format($attachment['file_size']) . " bytes - {$attachment['mime_type']}<br>";
        }
        
        // Check for duplicates in processed result
        $filenames = array_column($processed_attachments, 'filename');
        $unique_filenames = array_unique($filenames);
        
        if (count($filenames) !== count($unique_filenames)) {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
            echo "<strong>STILL HAS DUPLICATES in processed result!</strong><br>";
            echo "Processed: " . count($filenames) . ", Unique: " . count($unique_filenames);
            echo "</div>";
        } else {
            echo "<div style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
            echo "<strong>NO DUPLICATES in processed result - FIX WORKING!</strong><br>";
            echo "All " . count($processed_attachments) . " attachments are unique.";
            echo "</div>";
        }
        
    } else {
        echo "<p>Request #66 not found in API query</p>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
