<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    $reject_id = $_GET['id'] ?? 0;
    
    echo "<h2>Verify Cleanup for Reject Request #$reject_id</h2>";
    
    if ($reject_id == 0) {
        echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
        echo "<h4>Invalid Request ID</h4>";
        echo "</div>";
        exit;
    }
    
    // Get reject request details
    $details_query = "SELECT rr.*, sr.title as service_request_title, sr.id as service_request_id,
                      requester.username as requester_name, rejecter.username as rejecter_name
                      FROM reject_requests rr 
                      LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
                      LEFT JOIN users requester ON sr.user_id = requester.id
                      LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id
                      WHERE rr.id = :id";
    
    $details_stmt = $db->prepare($details_query);
    $details_stmt->execute(['id' => $reject_id]);
    $details = $details_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$details) {
        echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
        echo "<h4>Reject Request #$reject_id NOT Found</h4>";
        echo "</div>";
        exit;
    }
    
    echo "<h3>Reject Request Details:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>Reject Request ID</td><td><strong>{$details['id']}</strong></td></tr>";
    echo "<tr><td>Service Request ID</td><td>#{$details['service_request_id']}</td></tr>";
    echo "<tr><td>Service Request Title</td><td>" . htmlspecialchars($details['service_request_title']) . "</td></tr>";
    echo "<tr><td>Rejecter</td><td>{$details['rejecter_name']}</td></tr>";
    echo "<tr><td>Reason</td><td>" . htmlspecialchars($details['reject_reason']) . "</td></tr>";
    echo "<tr><td>Status</td><td>{$details['status']}</td></tr>";
    echo "<tr><td>Created</td><td>{$details['created_at']}</td></tr>";
    echo "</table>";
    
    // Get current attachments
    echo "<h3>Current Attachments After Cleanup:</h3>";
    
    $attachment_query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at
                         FROM reject_request_attachments 
                         WHERE reject_request_id = :id 
                         ORDER BY id";
    
    $attachment_stmt = $db->prepare($attachment_query);
    $attachment_stmt->execute(['id' => $reject_id]);
    $attachments = $attachment_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Total Attachments:</strong> " . count($attachments) . "</p>";
    
    if (count($attachments) > 0) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>";
        echo "<tr><th>ID</th><th>Original Name</th><th>Filename</th><th>Size</th><th>MIME</th><th>Uploaded</th><th>Preview</th></tr>";
        
        foreach ($attachments as $attachment) {
            $isImage = strpos($attachment['mime_type'], 'image/') === 0;
            
            echo "<tr style='background: #d4edda;'>";
            echo "<td>{$attachment['id']}</td>";
            echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
            echo "<td><strong>{$attachment['filename']}</strong></td>";
            echo "<td>" . number_format($attachment['file_size']) . "</td>";
            echo "<td>{$attachment['mime_type']}</td>";
            echo "<td>{$attachment['uploaded_at']}</td>";
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
            echo "</tr>";
        }
        
        echo "</table>";
        
        // Check for duplicates
        $original_names = array_column($attachments, 'original_name');
        $unique_original_names = array_unique($original_names);
        
        if (count($original_names) === count($unique_original_names)) {
            echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
            echo "<h4>VERIFICATION: SUCCESS</h4>";
            echo "<p>All " . count($attachments) . " attachments have unique original names</p>";
            echo "</div>";
        } else {
            echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
            echo "<h4>VERIFICATION: FAILED</h4>";
            echo "<p>Found " . count($original_names) . " attachments but only " . count($unique_original_names) . " unique original names</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div class='info' style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0;'>";
        echo "<h4>No Attachments Found</h4>";
        echo "<p>Reject request #$reject_id has no attachments</p>";
        echo "</div>";
    }
    
    // Test API response
    echo "<h3>Test API Response:</h3>";
    
    session_start();
    $_SESSION['user_id'] = 1;
    $_SESSION['role'] = 'admin';
    $_SESSION['username'] = 'admin';
    $_SESSION['full_name'] = 'System Administrator';
    
    $api_url = "http://localhost/it-service-request/api/reject_requests.php?action=get&id=$reject_id";
    
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
            echo "<tr><th>#</th><th>Original Name</th><th>Filename</th><th>Size</th><th>MIME</th></tr>";
            
            foreach ($api_data['data']['attachments'] as $index => $attachment) {
                echo "<tr>";
                echo "<td>" . ($index + 1) . "</td>";
                echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
                echo "<td>{$attachment['filename']}</td>";
                echo "<td>" . number_format($attachment['file_size']) . "</td>";
                echo "<td>{$attachment['mime_type']}</td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
            if (count($api_data['data']['attachments']) === count($attachments)) {
                echo "<p><strong>API and Database match!</strong></p>";
            } else {
                echo "<p style='color: red;'><strong>API and Database mismatch!</strong></p>";
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

<div style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0;'>
    <h3>Next Steps:</h3>
    <ol>
        <li><strong>Clear browser cache</strong> (Ctrl+F5)</li>
        <li><a href="index.html" target="_blank">Test in main application</a></li>
        <li>Find reject request for service request #77</li>
        <li>Verify exactly 2 attachments show</li>
        <li>Test image display for JPG file</li>
        <li>Test PDF view functionality</li>
    </ol>
</div>
