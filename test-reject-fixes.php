<!DOCTYPE html>
<html>
<head>
    <title>Test Reject Request Fixes</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        img { max-width: 200px; height: auto; margin: 10px; border: 1px solid #ccc; }
        .attachment-item { margin: 10px 0; padding: 10px; border: 1px solid #ccc; }
    </style>
</head>
<body>
    <h1>Test Reject Request Attachment Fixes</h1>
    
    <div class="test-section info">
        <h3>Testing Fixes:</h3>
        <ol>
            <li><strong>Image Display:</strong> Test if images display correctly in reject request details</li>
            <li><strong>Error Handling:</strong> Test if corrupted images show error message</li>
            <li><strong>Anti-Duplication:</strong> Test if attachments are duplicated in modal</li>
        </ol>
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
        
        // Get a reject request with attachments
        $query = "SELECT rr.*, 
                      sr.title as service_request_title,
                      requester.username as requester_name,
                      GROUP_CONCAT(DISTINCT CONCAT(attachment.original_name, '|', attachment.filename, '|', attachment.file_size, '|', attachment.mime_type) SEPARATOR '||') as attachments
                      FROM reject_requests rr 
                      LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
                      LEFT JOIN users requester ON sr.user_id = requester.id
                      LEFT JOIN reject_request_attachments attachment ON rr.id = attachment.reject_request_id
                      WHERE rr.id IN (SELECT id FROM reject_requests WHERE id IS NOT NULL LIMIT 1)
                      GROUP BY rr.id";
        
        $stmt = $db->prepare($query);
        $stmt->execute();
        $reject_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reject_request) {
            echo "<div class='test-section success'>";
            echo "<h2>Found Reject Request: {$reject_request['service_request_title']}</h2>";
            echo "<p><strong>Rejecter:</strong> {$reject_request['requester_name']}</p>";
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
            
            foreach ($attachments as $index => $attachment) {
                $isImage = strpos($attachment['mime_type'], 'image/') === 0;
                
                echo "<div class='attachment-item'>";
                echo "<p><strong>" . ($index + 1) . ". {$attachment['original_name']}</strong></p>";
                echo "<p>Size: " . number_format($attachment['file_size']) . " bytes | MIME: {$attachment['mime_type']}</p>";
                echo "<p>Is Image: " . ($isImage ? 'YES' : 'NO') . "</p>";
                
                if ($isImage) {
                    echo "<h4>Image Test:</h4>";
                    echo "<img src='api/reject_request_attachment.php?file={$attachment['filename']}&action=view' 
                         alt='{$attachment['original_name']}' 
                         onerror=\"this.style.display='none'; this.nextElementSibling.style.display='block';\">
                    <div class='error' style='display: none; padding: 10px; text-align: center;'>
                        <i class='fas fa-exclamation-triangle'></i> Không hi
                        </div>";
                    
                    echo "<p><a href='api/reject_request_attachment.php?file={$attachment['filename']}&action=download' 
                         target='_blank'>Download File</a></p>";
                } else {
                    echo "<p><a href='api/reject_request_attachment.php?file={$attachment['filename']}&action=download' 
                         target='_blank'>Download File</a></p>";
                }
                
                echo "</div>";
            }
            
            echo "</div>";
            
            // Test API endpoint directly
            echo "<div class='test-section info'>";
            echo "<h3>API Endpoint Test:</h3>";
            $test_file = $attachments[0]['filename'] ?? '';
            if ($test_file) {
                $api_url = "api/reject_request_attachment.php?file={$test_file}&action=view";
                echo "<p>Testing API: <code>{$api_url}</code></p>";
                
                // Check headers
                $headers = get_headers("http://localhost/it-service-request/{$api_url}");
                if ($headers) {
                    echo "<p>HTTP Status: " . $headers[0] . "</p>";
                    foreach ($headers as $header) {
                        if (strpos($header, 'Content-Type:') !== false) {
                            echo "<p>Content-Type: {$header}</p>";
                        }
                    }
                }
            }
            echo "</div>";
            
        } else {
            echo "<div class='test-section error'>";
            echo "<h2>No Reject Request Found</h2>";
            echo "<p>Please create a reject request with attachments first.</p>";
            echo "</div>";
        }
        
    } catch (Exception $e) {
        echo "<div class='test-section error'>";
        echo "<h2>Error:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    ?>

    <div class="test-section info">
        <h3>Manual Testing Instructions:</h3>
        <ol>
            <li>Go to <a href="index.html">main application</a></li>
            <li>Login as admin/staff</li>
            <li>Click "Yêu câu tù ch
            <li>Click on any reject request card to view details</li>
            <li>Check if images display correctly</li>
            <li>Click "X lý" button to open admin modal</li>
            <li>Check if attachments are duplicated</li>
            <li>Check error handling for corrupted images</li>
        </ol>
    </div>

</body>
</html>
