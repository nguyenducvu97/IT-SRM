<!DOCTYPE html>
<html>
<head>
    <title>Test Reject Request Detail</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .attachment-item { margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .duplicate { background: #fff3cd; border-color: #ffeaa7; }
        img { max-width: 150px; height: auto; margin: 5px; border: 1px solid #ddd; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Test Reject Request Detail - ID: <?php echo $_GET['id'] ?? '66'; ?></h1>
    
    <?php
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';

    require_once 'config/database.php';
    require_once 'config/session.php';

    $reject_id = $_GET['id'] ?? 66;

    try {
        $db = getDatabaseConnection();
        
        // Test the fixed API query
        echo "<div class='test-section info'>";
        echo "<h3>Testing Fixed Query for Reject Request #$reject_id</h3>";
        
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
                      WHERE rr.id = :id
                      GROUP BY rr.id";
        
        $stmt = $db->prepare($query);
        $stmt->bindValue(':id', $reject_id, PDO::PARAM_INT);
        $stmt->execute();
        $reject_request = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reject_request) {
            echo "<table>";
            echo "<tr><th>Field</th><th>Value</th></tr>";
            echo "<tr><td>Service Request</td><td>{$reject_request['service_request_title']}</td></tr>";
            echo "<tr><td>Service Request ID</td><td>{$reject_request['service_request_id']}</td></tr>";
            echo "<tr><td>Rejecter</td><td>{$reject_request['requester_name']}</td></tr>";
            echo "<tr><td>Reason</td><td>" . htmlspecialchars($reject_request['reject_reason']) . "</td></tr>";
            echo "<tr><td>Details</td><td>" . htmlspecialchars($reject_request['reject_details'] ?? '') . "</td></tr>";
            echo "<tr><td>Status</td><td>{$reject_request['status']}</td></tr>";
            echo "<tr><td>Created</td><td>{$reject_request['created_at']}</td></tr>";
            echo "<tr><td>Attachments String</td><td>" . htmlspecialchars($reject_request['attachments']) . "</td></tr>";
            echo "</table>";
            
            // Process attachments with new logic
            $attachments = [];
            if (!empty($reject_request['attachments'])) {
                $attachment_strings = explode('||', $reject_request['attachments']);
                echo "<h4>Raw Attachment Strings (" . count($attachment_strings) . "):</h4>";
                foreach ($attachment_strings as $index => $attachment_string) {
                    echo ($index + 1) . ". " . htmlspecialchars($attachment_string) . "<br>";
                }
                
                foreach ($attachment_strings as $attachment_string) {
                    if (!empty($attachment_string)) {
                        $parts = explode('|', $attachment_string);
                        echo "<h5>Parts count: " . count($parts) . "</h5>";
                        if (count($parts) >= 4) {
                            $attachments[] = [
                                'original_name' => $parts[0],
                                'filename' => $parts[1],
                                'file_size' => $parts[2],
                                'mime_type' => $parts[3]
                            ];
                        } else {
                            echo "<p style='color: red;'>Insufficient parts in attachment string</p>";
                        }
                    }
                }
            }
            
            echo "<h4>Processed Attachments (" . count($attachments) . "):</h4>";
            
            $seen_files = [];
            foreach ($attachments as $index => $attachment) {
                $is_duplicate = in_array($attachment['filename'], $seen_files);
                if (!$is_duplicate) {
                    $seen_files[] = $attachment['filename'];
                }
                
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
                         style='max-width: 100px;'
                         onerror=\"this.style.display='none'; this.nextElementSibling.style.display='block';\">
                    <div class='error' style='display: none; padding: 5px; background: #f8d7da; color: #721c24; text-align: center; font-size: 12px;'>
                        Cannot display image
                    </div>
                    <br>";
                }
                
                echo "<a href='api/reject_request_attachment.php?file={$attachment['filename']}&action=download' 
                     target='_blank' style='background: #007bff; color: white; padding: 5px 10px; text-decoration: none; border-radius: 3px;'>Download</a>";
                
                echo "</div>";
            }
            
            // Check for duplicates
            $unique_files = array_unique($seen_files);
            if (count($seen_files) !== count($unique_files)) {
                echo "<div class='test-section error'>";
                echo "<h4>DUPLICATES STILL DETECTED!</h4>";
                echo "<p>Found " . count($seen_files) . " files but only " . count($unique_files) . " unique files.</p>";
                echo "<p>Duplicates need to be cleaned up.</p>";
                echo "</div>";
            } else {
                echo "<div class='test-section success'>";
                echo "<h4>NO DUPLICATES - FIX SUCCESSFUL!</h4>";
                echo "<p>All " . count($attachments) . " attachments are unique.</p>";
                echo "</div>";
            }
            
        } else {
            echo "<div class='test-section error'>";
            echo "<h4>Reject Request #$reject_id not found</h4>";
            echo "<p>Please check if this reject request exists in the database.</p>";
            echo "</div>";
        }
        
        echo "</div>";
        
        // Test actual API response
        echo "<div class='test-section info'>";
        echo "<h3>Test Actual API Response</h3>";
        
        $api_url = "http://localhost/it-service-request/api/reject_requests.php?action=get&id=$reject_id";
        
        // Use curl for better testing
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $api_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p><strong>API URL:</strong> $api_url</p>";
        echo "<p><strong>HTTP Code:</strong> $http_code</p>";
        
        if ($api_response) {
            echo "<p><strong>Raw Response:</strong></p>";
            echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 4px; max-height: 300px; overflow-y: auto;'>";
            echo htmlspecialchars($api_response);
            echo "</pre>";
            
            $api_data = json_decode($api_response, true);
            
            if ($api_data && $api_data['success']) {
                echo "<div class='test-section success'>";
                echo "<h4>API Response: SUCCESS</h4>";
                echo "<p><strong>Attachments in API:</strong> " . count($api_data['data']['attachments']) . "</p>";
                
                foreach ($api_data['data']['attachments'] as $index => $attachment) {
                    echo "<p>" . ($index + 1) . ". {$attachment['original_name']} ({$attachment['filename']})</p>";
                }
                echo "</div>";
            } else {
                echo "<div class='test-section error'>";
                echo "<h4>API Response: ERROR</h4>";
                echo "<p>" . ($api_data['message'] ?? 'Unknown error') . "</p>";
                echo "</div>";
            }
        } else {
            echo "<div class='test-section error'>";
            echo "<h4>API Response: FAILED</h4>";
            echo "<p>Could not connect to API</p>";
            echo "</div>";
        }
        
        echo "</div>";
        
    } catch (Exception $e) {
        echo "<div class='test-section error'>";
        echo "<h2>Error:</h2>";
        echo "<p>" . $e->getMessage() . "</p>";
        echo "</div>";
    }
    ?>

    <div class='test-section info'>
        <h3>Actions:</h3>
        <ol>
            <li><a href="check-all-reject-requests.php">Check all reject requests</a></li>
            <li><a href="cleanup-duplicate-attachments.php">Run cleanup script</a></li>
            <li><a href="index.html">Test in main application</a></li>
            <li>Clear browser cache (Ctrl+F5) after fixes</li>
        </ol>
    </div>

</body>
</html>
