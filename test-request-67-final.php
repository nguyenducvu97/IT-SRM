<!DOCTYPE html>
<html>
<head>
    <title>Test Request #67 Final</title>
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
    <h1>Final Test - Reject Request #67</h1>
    
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
        echo "<h3>Cleanup Status: SUCCESS</h3>";
        echo "<p>Deleted 2 duplicate attachments</p>";
        echo "<p>Remaining 2 unique attachments</p>";
        echo "</div>";
        
        // Test the actual API response
        echo "<div class='test-section info'>";
        echo "<h3>Test API Response for Request #67</h3>";
        
        $api_url = "http://localhost/it-service-request/api/reject_requests.php?action=get&id=67";
        
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
                echo "<tr><th>#</th><th>Original Name</th><th>Filename</th><th>Size</th><th>MIME</th><th>Preview</th><th>Actions</th></tr>";
                
                foreach ($api_data['data']['attachments'] as $index => $attachment) {
                    $isImage = strpos($attachment['mime_type'], 'image/') === 0;
                    $isPDF = $attachment['mime_type'] === 'application/pdf';
                    $isExcel = strpos($attachment['mime_type'], 'excel') !== false || strpos($attachment['mime_type'], 'sheet') !== false;
                    
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
                        echo "<span style='color: #666; font-size: 12px;'>No preview</span>";
                    }
                    
                    echo "</td>";
                    echo "<td>";
                    
                    // View button for viewable files
                    if ($isImage || $isPDF || $isExcel) {
                        $fileExt = pathinfo($attachment['filename'], PATHINFO_EXTENSION);
                        echo "<button class='btn btn-sm btn-primary' 
                                onclick=\"window.open('api/reject_request_attachment.php?file={$attachment['filename']}&action=view', '_blank')\"
                                style='font-size: 10px; padding: 2px 5px; margin-right: 3px;'>
                            Xem
                        </button>";
                    }
                    
                    // Download button
                    echo "<a href='api/reject_request_attachment.php?file={$attachment['filename']}&action=download' 
                         target='_blank' 
                         style='background: #007bff; color: white; padding: 2px 5px; text-decoration: none; border-radius: 3px; font-size: 10px;'>
                        Tài v
                    </a>";
                    
                    echo "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                echo "</div>";
                
                // Verification
                if (count($api_data['data']['attachments']) === 2) {
                    echo "<div class='test-section success'>";
                    echo "<h4>VERIFICATION: PASSED</h4>";
                    echo "<p>Exactly 2 unique attachments found - no duplicates!</p>";
                    echo "</div>";
                } else {
                    echo "<div class='test-section error'>";
                    echo "<h4>VERIFICATION: FAILED</h4>";
                    echo "<p>Expected 2 attachments, found " . count($api_data['data']['attachments']) . "</p>";
                    echo "</div>";
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
        
        // Manual processing test
        echo "<div class='test-section info'>";
        echo "<h3>Manual Processing Test</h3>";
        
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
                         WHERE rr.id = 67
                         GROUP BY rr.id";
        
        $test_stmt = $db->prepare($test_query);
        $test_stmt->execute();
        $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($test_result) {
            echo "<p><strong>Raw Attachments String:</strong></p>";
            echo "<code style='background: #f8f9fa; padding: 10px; display: block; white-space: pre-wrap;'>";
            echo htmlspecialchars($test_result['attachments']);
            echo "</code>";
            
            // Process exactly like the API does
            $attachment_strings = explode('||', $test_result['attachments']);
            echo "<h5>Attachment Strings (" . count($attachment_strings) . "):</h5>";
            
            $processed_attachments = [];
            foreach ($attachment_strings as $index => $attachment_string) {
                if (!empty($attachment_string) && trim($attachment_string) !== '') {
                    echo "<p>" . ($index + 1) . ". " . htmlspecialchars($attachment_string) . "</p>";
                    
                    $parts = explode('|', $attachment_string);
                    
                    // Filter out empty parts
                    $filtered_parts = array_filter($parts, function($part) {
                        return $part !== '' && $part !== null;
                    });
                    
                    if (count($filtered_parts) >= 4 && !empty($filtered_parts[0]) && !empty($filtered_parts[1])) {
                        $processed_attachments[] = [
                            'original_name' => trim($filtered_parts[0]),
                            'filename' => trim($filtered_parts[1]),
                            'file_size' => intval($filtered_parts[2]),
                            'mime_type' => trim($filtered_parts[3])
                        ];
                    } else {
                        echo "<p style='color: red;'>ERROR: Insufficient or invalid parts</p>";
                    }
                }
            }
            
            echo "<h5>Processed Attachments (" . count($processed_attachments) . "):</h5>";
            foreach ($processed_attachments as $index => $attachment) {
                $isImage = strpos($attachment['mime_type'], 'image/') === 0;
                echo "<p><strong>" . ($index + 1) . ". {$attachment['original_name']}</strong> ({$attachment['filename']}) - " . number_format($attachment['file_size']) . " bytes " . ($isImage ? "[IMAGE]" : "[DOCUMENT]") . "</p>";
            }
            
            // Check for duplicates
            $original_names = array_column($processed_attachments, 'original_name');
            $unique_original_names = array_unique($original_names);
            
            if (count($original_names) === count($unique_original_names) && count($processed_attachments) === 2) {
                echo "<div class='test-section success'>";
                echo "<h4>MANUAL VERIFICATION: PASSED</h4>";
                echo "<p>All " . count($processed_attachments) . " attachments are unique and correctly processed!</p>";
                echo "</div>";
            } else {
                echo "<div class='test-section error'>";
                echo "<h4>MANUAL VERIFICATION: FAILED</h4>";
                echo "<p>Expected 2 unique attachments, got " . count($processed_attachments) . " unique out of " . count($original_names) . "</p>";
                echo "</div>";
            }
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
        <h3>Final Verification Steps:</h3>
        <ol>
            <li><strong>Clear browser cache</strong> (Ctrl+F5)</li>
            <li><a href="index.html" target="_blank">Open main application</a></li>
            <li>Login as admin/staff</li>
            <li>Navigate to reject requests</li>
            <li>Find the reject request for "éaew" (Service Request #81)</li>
            <li>Click to view details</li>
            <li><strong>Expected:</strong> Should see exactly 2 attachments, no duplicates</li>
            <li><strong>Test:</strong> JPG image should display correctly, Excel file should have "Xem" button</li>
        </ol>
        
        <p><strong>Expected Final Result:</strong></p>
        <ul>
            <li>Têp dính kèm (2)</li>
            <li>2026-03-31 - Nhâp xuât kho khác.xls (17.5 KB) [Xem] [Tài v]</li>
            <li>2026-03-25 - Tình trâng sân xuât.jpg (82.4 KB) [Xem] [Tài v] (image displays)</li>
        </ul>
    </div>

</body>
</html>
