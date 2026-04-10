<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Test API Fix for Reject Request #69</h2>";
    
    // Test the fixed query
    echo "<h3>Test Fixed Query:</h3>";
    
    $test_query = "SELECT GROUP_CONCAT(
        CONCAT(attachment.original_name, '|', attachment.filename, '|', COALESCE(attachment.file_size, 0), '|', COALESCE(attachment.mime_type, 'application/octet-stream'))
        ORDER BY attachment.id 
        SEPARATOR '||'
    ) as attachments
                     FROM reject_requests rr 
                     LEFT JOIN reject_request_attachments attachment ON rr.id = attachment.reject_request_id
                     WHERE rr.id = 69
                     GROUP BY rr.id";
    
    $test_stmt = $db->prepare($test_query);
    $test_stmt->execute();
    $test_result = $test_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($test_result) {
        echo "<p><strong>Raw Attachments String:</strong></p>";
        echo "<code style='background: #f8f9fa; padding: 10px; display: block; white-space: pre-wrap;'>";
        echo htmlspecialchars($test_result['attachments']);
        echo "</code>";
        
        // Process with new logic
        echo "<h3>Process with New Logic:</h3>";
        
        $attachment_strings = explode('||', $test_result['attachments']);
        echo "<h5>Attachment Strings (" . count($attachment_strings) . "):</h5>";
        
        $processed_attachments = [];
        $seen_original_names = [];
        
        foreach ($attachment_strings as $index => $attachment_string) {
            if (!empty($attachment_string) && trim($attachment_string) !== '') {
                echo "<p>" . ($index + 1) . ". " . htmlspecialchars($attachment_string) . "</p>";
                
                $parts = explode('|', $attachment_string);
                
                // Filter out empty parts
                $filtered_parts = array_filter($parts, function($part) {
                    return $part !== '' && $part !== null;
                });
                
                if (count($filtered_parts) >= 4 && !empty($filtered_parts[0]) && !empty($filtered_parts[1])) {
                    $original_name = trim($filtered_parts[0]);
                    
                    // Skip if we've already seen this original name
                    if (!in_array($original_name, $seen_original_names)) {
                        $processed_attachments[] = [
                            'original_name' => $original_name,
                            'filename' => trim($filtered_parts[1]),
                            'file_size' => intval($filtered_parts[2]),
                            'mime_type' => trim($filtered_parts[3])
                        ];
                        
                        $seen_original_names[] = $original_name;
                        echo "<p style='color: green;'>✅ Added: {$original_name}</p>";
                    } else {
                        echo "<p style='color: red;'>❌ Skipped duplicate: {$original_name}</p>";
                    }
                } else {
                    echo "<p style='color: red;'>ERROR: Insufficient parts</p>";
                }
            }
        }
        
        echo "<h5>Final Processed Attachments (" . count($processed_attachments) . "):</h5>";
        foreach ($processed_attachments as $index => $attachment) {
            echo "<p><strong>" . ($index + 1) . ". {$attachment['original_name']}</strong> ({$attachment['filename']}) - " . number_format($attachment['file_size']) . " bytes</p>";
        }
        
        // Check for duplicates
        $original_names = array_column($processed_attachments, 'original_name');
        $unique_original_names = array_unique($original_names);
        
        if (count($original_names) === count($unique_original_names)) {
            echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
            echo "<h4>PROCESSING SUCCESSFUL:</h4>";
            echo "<p>All " . count($processed_attachments) . " attachments are unique!</p>";
            echo "</div>";
        } else {
            echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
            echo "<h4>PROCESSING FAILED:</h4>";
            echo "<p>Still have duplicates in processed result</p>";
            echo "</div>";
        }
        
    } else {
        echo "<p>No attachments string found</p>";
    }
    
    // Test actual API
    echo "<h3>Test Actual API:</h3>";
    
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    
    $api_url = "http://localhost/it-service-request/api/reject_requests.php?action=get&id=69";
    
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
            echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
            echo "<h4>API Response: SUCCESS</h4>";
            echo "<p><strong>Attachments in API:</strong> " . count($api_data['data']['attachments']) . "</p>";
            
            echo "<table>";
            echo "<tr><th>#</th><th>Original Name</th><th>Filename</th><th>Size</th></tr>";
            
            foreach ($api_data['data']['attachments'] as $index => $attachment) {
                echo "<tr>";
                echo "<td>" . ($index + 1) . "</td>";
                echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
                echo "<td>{$attachment['filename']}</td>";
                echo "<td>" . number_format($attachment['file_size']) . "</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            if (count($api_data['data']['attachments']) === 2) {
                echo "<p><strong>✅ API Fix Successful!</strong></p>";
            } else {
                echo "<p><strong>❌ API Still Has Issues!</strong></p>";
            }
            
            echo "</div>";
        } else {
            echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
            echo "<h4>API Response: ERROR</h4>";
            echo "<p>" . ($api_data['message'] ?? 'Unknown error') . "</p>";
            echo "</div>";
        }
    } else {
        echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
        echo "<h4>API Response: FAILED</h4>";
        echo "<p>Could not connect to API</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
