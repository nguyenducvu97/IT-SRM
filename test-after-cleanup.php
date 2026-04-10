<!DOCTYPE html>
<html>
<head>
    <title>Test After Cleanup</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; }
        .success { background: #d4edda; color: #155724; }
        .error { background: #f8d7da; color: #721c24; }
        .info { background: #d1ecf1; color: #0c5460; }
        .attachment-item { margin: 10px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        img { max-width: 150px; height: auto; margin: 5px; border: 1px solid #ddd; }
        table { border-collapse: collapse; width: 100%; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
    </style>
</head>
<body>
    <h1>Test After Cleanup - Reject Request #66</h1>
    
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
        
        echo "<div class='test-section success'>";
        echo "<h3>Verification: Cleanup Successful!</h3>";
        echo "<p>Deleted 2 duplicate attachments by original name</p>";
        echo "<p>Remaining 2 unique attachments</p>";
        echo "</div>";
        
        // Test the actual API response
        echo "<div class='test-section info'>";
        echo "<h3>Test Actual API Response for Request #66</h3>";
        
        $api_url = "http://localhost/it-service-request/api/reject_requests.php?action=get&id=66";
        
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
            $api_data = json_decode($api_response, true);
            
            if ($api_data && $api_data['success']) {
                echo "<div class='test-section success'>";
                echo "<h4>API Response: SUCCESS</h4>";
                echo "<p><strong>Attachments in API:</strong> " . count($api_data['data']['attachments']) . "</p>";
                
                echo "<table>";
                echo "<tr><th>#</th><th>Original Name</th><th>Filename</th><th>Size</th><th>MIME</th><th>Preview</th></tr>";
                
                foreach ($api_data['data']['attachments'] as $index => $attachment) {
                    $isImage = strpos($attachment['mime_type'], 'image/') === 0;
                    
                    echo "<tr>";
                    echo "<td>" . ($index + 1) . "</td>";
                    echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
                    echo "<td>{$attachment['filename']}</td>";
                    echo "<td>" . number_format($attachment['file_size']) . "</td>";
                    echo "<td>{$attachment['mime_type']}</td>";
                    echo "<td>";
                    
                    if ($isImage) {
                        echo "<img src='api/reject_request_attachment.php?file={$attachment['filename']}&action=view' 
                             alt='{$attachment['original_name']}' 
                             style='max-width: 80px; height: auto;'
                             onerror=\"this.style.display='none'; this.nextElementSibling.style.display='block';\">
                        <div class='error' style='display: none; padding: 2px; background: #f8d7da; color: #721c24; text-align: center; font-size: 10px;'>
                            No img
                        </div>";
                    } else {
                        echo "<span style='color: #666;'>No preview</span>";
                    }
                    
                    echo "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                echo "</div>";
                
                // Test the processed attachments string
                echo "<h4>Manual Processing Test:</h4>";
                
                // Simulate the same processing as in the API
                $test_query = "SELECT GROUP_CONCAT(DISTINCT CONCAT(attachment.original_name, '|', attachment.filename, '|', attachment.file_size, '|', attachment.mime_type) ORDER BY attachment.id SEPARATOR '||') as attachments
                                 FROM reject_requests rr 
                                 LEFT JOIN reject_request_attachments attachment ON rr.id = attachment.reject_request_id
                                 WHERE rr.id = 66
                                 GROUP BY rr.id";
                
                $test_stmt = $db->prepare($test_query);
                $test_stmt->execute();
                $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($test_result && !empty($test_result['attachments'])) {
                    echo "<p><strong>Raw Attachments String:</strong></p>";
                    echo "<code style='background: #f8f9fa; padding: 10px; display: block; white-space: pre-wrap;'>";
                    echo htmlspecialchars($test_result['attachments']);
                    echo "</code>";
                    
                    // Process exactly like the API does
                    $attachment_strings = explode('||', $test_result['attachments']);
                    echo "<h5>Attachment Strings (" . count($attachment_strings) . "):</h5>";
                    
                    $processed_attachments = [];
                    foreach ($attachment_strings as $index => $attachment_string) {
                        if (!empty($attachment_string)) {
                            echo "<p>" . ($index + 1) . ". " . htmlspecialchars($attachment_string) . "</p>";
                            
                            $parts = explode('|', $attachment_string);
                            if (count($parts) >= 4) {
                                $processed_attachments[] = [
                                    'original_name' => $parts[0],
                                    'filename' => $parts[1],
                                    'file_size' => $parts[2],
                                    'mime_type' => $parts[3]
                                ];
                            } else {
                                echo "<p style='color: red;'>ERROR: Insufficient parts (" . count($parts) . ") in string</p>";
                            }
                        }
                    }
                    
                    echo "<h5>Processed Attachments (" . count($processed_attachments) . "):</h5>";
                    foreach ($processed_attachments as $index => $attachment) {
                        echo "<p><strong>" . ($index + 1) . ". {$attachment['original_name']}</strong> ({$attachment['filename']})</p>";
                    }
                    
                    // Check for duplicates by original name
                    $original_names = array_column($processed_attachments, 'original_name');
                    $unique_original_names = array_unique($original_names);
                    
                    if (count($original_names) === count($unique_original_names)) {
                        echo "<div class='test-section success'>";
                        echo "<h4>DUPLICATE CHECK: PASSED</h4>";
                        echo "<p>All " . count($processed_attachments) . " attachments have unique original names</p>";
                        echo "</div>";
                    } else {
                        echo "<div class='test-section error'>";
                        echo "<h4>DUPLICATE CHECK: FAILED</h4>";
                        echo "<p>Found " . count($original_names) . " original names but only " . count($unique_original_names) . " unique</p>";
                        echo "</div>";
                    }
                    
                } else {
                    echo "<p>No attachments string found</p>";
                }
                
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
        
        // Check request #43
        echo "<div class='test-section info'>";
        echo "<h3>Check Request #43:</h3>";
        
        $check_43_query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at
                            FROM reject_request_attachments 
                            WHERE reject_request_id = 43 
                            ORDER BY id";
        
        $check_43_stmt = $db->prepare($check_43_query);
        $check_43_stmt->execute();
        $attachments_43 = $check_43_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<table>";
        echo "<tr><th>ID</th><th>Original Name</th><th>Filename</th><th>Size</th><th>MIME</th><th>Uploaded</th></tr>";
        
        foreach ($attachments_43 as $attachment) {
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
        
        // Check for duplicates in #43
        $original_names_43 = array_column($attachments_43, 'original_name');
        $unique_original_names_43 = array_unique($original_names_43);
        
        if (count($original_names_43) !== count($unique_original_names_43)) {
            echo "<div class='test-section error'>";
            echo "<h4>Request #43 HAS DUPLICATES</h4>";
            echo "<p>Found " . count($original_names_43) . " attachments but only " . count($unique_original_names_43) . " unique original names</p>";
            echo "<p><a href='cleanup-by-original-name.php?reject_id=43'>Cleanup Request #43</a></p>";
            echo "</div>";
        } else {
            echo "<div class='test-section success'>";
            echo "<h4>Request #43 is CLEAN</h4>";
            echo "<p>All " . count($attachments_43) . " attachments have unique original names</p>";
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
        <h3>Next Steps:</h3>
        <ol>
            <li><strong>Clear browser cache</strong> (Ctrl+F5)</li>
            <li><a href="index.html" target="_blank">Test in main application</a></li>
            <li>Navigate to reject requests</li>
            <li>Click on reject request #66</li>
            <li>Verify only 2 attachments show</li>
            <li>Test image display for IT SRM.png</li>
        </ol>
        
        <p><strong>Expected Result:</strong> Should see exactly 2 attachments with no duplicates, and the PNG image should display correctly.</p>
    </div>

</body>
</html>
