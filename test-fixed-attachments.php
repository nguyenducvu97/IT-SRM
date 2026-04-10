<!DOCTYPE html>
<html>
<head>
    <title>Test Fixed Attachments</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .attachment-item { margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .duplicate { background: #fff3cd; border-color: #ffeaa7; }
        img { max-width: 150px; height: auto; margin: 5px; border: 1px solid #ddd; }
    </style>
</head>
<body>
    <h1>Test Fixed Reject Request Attachments</h1>
    
    <div class="test-section info">
        <h3>What was fixed:</h3>
        <ul>
            <li>Added DISTINCT and ORDER BY to GROUP_CONCAT to prevent duplicates</li>
            <li>Enhanced query to include file_size and mime_type</li>
            <li>Updated attachment processing to handle all fields</li>
            <li>Created cleanup script for existing duplicates</li>
        </ul>
    </div>

    <?php
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';

    require_once 'config/database.php';
    require_once 'config/session.php';

    try {
        $db = getDatabaseConnection();
        
        // Test the fixed query
        echo "<div class='test-section info'>";
        echo "<h3>Testing Fixed Query for Reject Request #69</h3>";
        
        $query = "SELECT rr.*, 
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
                      WHERE rr.id = 69
                      GROUP BY rr.id";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $reject_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reject_request) {
            echo "<p><strong>Service Request:</strong> {$reject_request['service_request_title']}</p>";
            echo "<p><strong>Rejecter:</strong> {$reject_request['requester_name']}</p>";
            echo "<p><strong>Attachments String:</strong> " . htmlspecialchars($reject_request['attachments']) . "</p>";
            
            // Process attachments with new logic
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
            
            echo "<h4>Processed Attachments (" . count($attachments) . "):</h4>";
            
            $seen_files = [];
            foreach ($attachments as $index => $attachment) {
                $is_duplicate = in_array($attachment['filename'], $seen_files);
                $seen_files[] = $attachment['filename'];
                
                $isImage = strpos($attachment['mime_type'], 'image/') === 0;
                
                echo "<div class='attachment-item" . ($is_duplicate ? ' duplicate' : '') . "'>";
                echo "<p><strong>" . ($index + 1) . ". {$attachment['original_name']}</strong></p>";
                echo "<p>Filename: {$attachment['filename']}</p>";
                echo "<p>Size: " . number_format($attachment['file_size']) . " bytes</p>";
                echo "<p>MIME: {$attachment['mime_type']}</p>";
                echo "<p>Is Image: " . ($isImage ? 'YES' : 'NO') . "</p>";
                
                if ($is_duplicate) {
                    echo "<p style='color: #856404;'><strong>DUPLICATE DETECTED!</strong></p>";
                }
                
                if ($isImage) {
                    echo "<img src='api/reject_request_attachment.php?file={$attachment['filename']}&action=view' 
                         alt='{$attachment['original_name']}' 
                         style='max-width: 100px;'>
                    <br>";
                }
                
                echo "<a href='api/reject_request_attachment.php?file={$attachment['filename']}&action=download' 
                     target='_blank' class='btn btn-sm btn-secondary'>Download</a>";
                
                echo "</div>";
            }
            
            // Check for duplicates
            $unique_files = array_unique($seen_files);
            if (count($seen_files) !== count($unique_files)) {
                echo "<div class='test-section error'>";
                echo "<h4>DUPLICATES STILL DETECTED!</h4>";
                echo "<p>Found " . count($seen_files) . " files but only " . count($unique_files) . " unique files.</p>";
                echo "<p><a href='cleanup-duplicate-attachments.php'>Run Cleanup Script</a></p>";
                echo "</div>";
            } else {
                echo "<div class='test-section success'>";
                echo "<h4>NO DUPLICATES - FIX SUCCESSFUL!</h4>";
                echo "<p>All " . count($attachments) . " attachments are unique.</p>";
                echo "</div>";
            }
            
        } else {
            echo "<p>Reject request #69 not found</p>";
        }
        
        echo "</div>";
        
        // Test API response
        echo "<div class='test-section info'>";
        echo "<h3>Test API Response</h3>";
        
        // Mock session for API
        session_start();
        $_SESSION['user_id'] = 1;
        $_SESSION['role'] = 'admin';
        
        // Call the actual API
        $api_url = "http://localhost/it-service-request/api/reject_requests.php?action=get&id=69";
        $context = stream_context_create([
            'http' => [
                'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] ?? ""
            ]
        ]);
        
        $api_response = file_get_contents($api_url, false, $context);
        
        if ($api_response) {
            $api_data = json_decode($api_response, true);
            
            if ($api_data && $api_data['success']) {
                echo "<p><strong>API Response:</strong> SUCCESS</p>";
                echo "<p><strong>Attachments in API:</strong> " . count($api_data['data']['attachments']) . "</p>";
                
                foreach ($api_data['data']['attachments'] as $index => $attachment) {
                    echo "<p>" . ($index + 1) . ". {$attachment['original_name']} ({$attachment['filename']})</p>";
                }
            } else {
                echo "<p><strong>API Response:</strong> ERROR - " . ($api_data['message'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p><strong>API Response:</strong> FAILED to connect</p>";
        }
        
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='test-section error'>";
        echo "<h2>Error:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    ?>

    <div class="test-section info">
        <h3>Next Steps:</h3>
        <ol>
            <li><a href="cleanup-duplicate-attachments.php">Run cleanup script</a> to remove existing duplicates</li>
            <li>Clear browser cache (Ctrl+F5)</li>
            <li>Test in main application</li>
            <li>Verify reject request #69 shows only 2 unique attachments</li>
        </ol>
    </div>

</body>
</html>
