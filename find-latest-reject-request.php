<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Find Latest Reject Request</h2>";
    
    // Get latest reject request
    $latest_query = "SELECT rr.*, sr.title as service_request_title, sr.id as service_request_id,
                      requester.username as requester_name, rejecter.username as rejecter_name
                      FROM reject_requests rr 
                      LEFT JOIN service_requests sr ON rr.service_request_id = sr.id
                      LEFT JOIN users requester ON sr.user_id = requester.id
                      LEFT JOIN users rejecter ON rr.rejected_by = rejecter.id
                      ORDER BY rr.created_at DESC
                      LIMIT 5";
    
    $latest_stmt = $db->prepare($latest_query);
    $latest_stmt->execute();
    $latest_requests = $latest_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Latest 5 Reject Requests:</h3>";
    echo "<table border='1' cellpadding='5' cellspacing='0'>";
    echo "<tr><th>Reject ID</th><th>Service ID</th><th>Service Title</th><th>Rejecter</th><th>Reason</th><th>Created</th><th>Attachments</th><th>Action</th></tr>";
    
    foreach ($latest_requests as $request) {
        // Get attachments for this request
        $attachment_query = "SELECT COUNT(*) as count, GROUP_CONCAT(original_name SEPARATOR ', ') as files
                             FROM reject_request_attachments 
                             WHERE reject_request_id = :id";
        
        $attachment_stmt = $db->prepare($attachment_query);
        $attachment_stmt->execute(['id' => $request['id']]);
        $attachment_info = $attachment_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<tr>";
        echo "<td><strong>{$request['id']}</strong></td>";
        echo "<td>#{$request['service_request_id']}</td>";
        echo "<td>" . htmlspecialchars(substr($request['service_request_title'], 0, 30)) . "...</td>";
        echo "<td>{$request['rejecter_name']}</td>";
        echo "<td>" . htmlspecialchars(substr($request['reject_reason'], 0, 20)) . "...</td>";
        echo "<td>{$request['created_at']}</td>";
        echo "<td>{$attachment_info['count']} files</td>";
        echo "<td><a href='debug-reject-request.php?id={$request['id']}' target='_blank'>Debug</a></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Check the most recent one
    if (count($latest_requests) > 0) {
        $latest = $latest_requests[0];
        $reject_id = $latest['id'];
        
        echo "<h3>Detailed Debug for Latest Reject Request #$reject_id:</h3>";
        
        // Get all attachments for this request
        $detail_query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at
                          FROM reject_request_attachments 
                          WHERE reject_request_id = :id 
                          ORDER BY id";
        
        $detail_stmt = $db->prepare($detail_query);
        $detail_stmt->execute(['id' => $reject_id]);
        $attachments = $detail_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Total Attachments:</strong> " . count($attachments) . "</p>";
        
        if (count($attachments) > 0) {
            echo "<table border='1' cellpadding='5' cellspacing='0'>";
            echo "<tr><th>ID</th><th>Original Name</th><th>Filename</th><th>Size</th><th>MIME</th><th>Uploaded</th><th>Is Duplicate?</th></tr>";
            
            $seen_files = [];
            foreach ($attachments as $attachment) {
                $original_name = $attachment['original_name'];
                $is_duplicate = in_array($original_name, $seen_files);
                
                echo "<tr style='background: " . ($is_duplicate ? '#f8d7da' : '#d4edda') . ";'>";
                echo "<td>{$attachment['id']}</td>";
                echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
                echo "<td>{$attachment['filename']}</td>";
                echo "<td>" . number_format($attachment['file_size']) . "</td>";
                echo "<td>{$attachment['mime_type']}</td>";
                echo "<td>{$attachment['uploaded_at']}</td>";
                echo "<td><strong>" . ($is_duplicate ? 'DUPLICATE' : 'UNIQUE') . "</strong></td>";
                echo "</tr>";
                
                $seen_files[] = $original_name;
            }
            
            echo "</table>";
            
            // Check for duplicates
            $unique_files = array_unique($seen_files);
            if (count($seen_files) !== count($unique_files)) {
                echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                echo "<h4>DUPLICATES DETECTED!</h4>";
                echo "<p>Found " . count($seen_files) . " files but only " . count($unique_files) . " unique original names</p>";
                echo "<p><a href='cleanup-reject-request.php?id=$reject_id&auto=1'>Auto-Cleanup Request #$reject_id</a></p>";
                echo "</div>";
            } else {
                echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                echo "<h4>NO DUPLICATES</h4>";
                echo "<p>All " . count($attachments) . " attachments have unique original names</p>";
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
                    echo "<div class='info' style='background: #d1ecf1; color: #0c5460; padding: 10px; margin: 10px 0;'>";
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
                    
                    // Check API vs Database
                    if (count($api_data['data']['attachments']) === count($attachments)) {
                        echo "<p><strong>✅ API and Database match!</strong></p>";
                    } else {
                        echo "<p><strong>❌ API and Database mismatch!</strong></p>";
                        echo "<p>Database: " . count($attachments) . " files, API: " . count($api_data['data']['attachments']) . " files</p>";
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
        }
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
    echo "<strong>Error:</strong> " . $e->getMessage();
    echo "</div>";
}
?>
