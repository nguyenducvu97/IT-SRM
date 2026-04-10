<?php
require_once 'config/database.php';
require_once 'config/session.php';

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Test Complete Fix for Reject Request #69</h2>";
    
    // Test actual API
    echo "<h3>Test Actual API (GET action):</h3>";
    
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
            
            if (count($api_data['data']['attachments']) === 2) {
                echo "<p><strong>FINAL RESULT: COMPLETE SUCCESS! </strong></p>";
                echo "<p><strong>Reject request attachment duplication issue is FIXED! </strong></p>";
                echo "<p><strong>Ready for production testing! </strong></p>";
            } else {
                echo "<p><strong>Still has issues - got " . count($api_data['data']['attachments']) . " attachments</strong></p>";
            }
            
            echo "</div>";
            
            // Test list action too
            echo "<h3>Test List Action:</h3>";
            
            $list_url = "http://localhost/it-service-request/api/reject_requests.php?action=list";
            
            $ch2 = curl_init();
            curl_setopt($ch2, CURLOPT_URL, $list_url);
            curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch2, CURLOPT_COOKIE, session_name() . '=' . session_id());
            curl_setopt($ch2, CURLOPT_FOLLOWLOCATION, true);
            
            $list_response = curl_exec($ch2);
            curl_close($ch2);
            
            if ($list_response) {
                $list_data = json_decode($list_response, true);
                
                if ($list_data && $list_data['success']) {
                    // Find request #69 in the list
                    $found_request = null;
                    foreach ($list_data['data'] as $request) {
                        if ($request['id'] == 69) {
                            $found_request = $request;
                            break;
                        }
                    }
                    
                    if ($found_request) {
                        echo "<div class='success' style='background: #d4edda; color: #155724; padding: 10px; margin: 10px 0;'>";
                        echo "<h4>List Action: Request #69 Found</h4>";
                        echo "<p><strong>Attachments in List:</strong> " . count($found_request['attachments']) . "</p>";
                        
                        if (count($found_request['attachments']) === 2) {
                            echo "<p><strong>Both GET and LIST actions work correctly! </strong></p>";
                        } else {
                            echo "<p><strong>List action still has issues - got " . count($found_request['attachments']) . " attachments</strong></p>";
                        }
                        
                        echo "</div>";
                    } else {
                        echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                        echo "<h4>List Action: Request #69 Not Found</h4>";
                        echo "</div>";
                    }
                } else {
                    echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                    echo "<h4>List Action: ERROR</h4>";
                    echo "<p>" . ($list_data['message'] ?? 'Unknown error') . "</p>";
                    echo "</div>";
                }
            } else {
                echo "<div class='error' style='background: #f8d7da; color: #721c24; padding: 10px; margin: 10px 0;'>";
                echo "<h4>List Action: FAILED</h4>";
                echo "<p>Could not connect to API</p>";
                echo "</div>";
            }
            
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
    <h3>Final Testing Steps:</h3>
    <ol>
        <li><strong>If test passes:</strong> Clear browser cache (Ctrl+F5)</li>
        <li><strong>Create new reject request</strong> to test future behavior</li>
        <li><strong>Verify</strong> only 2 unique attachments show</li>
        <li><strong>Test image display</strong> and PDF viewing</li>
        <li><strong>Confirm no more duplicates</strong> in new requests</li>
    </ol>
    
    <p><strong>Expected Final Result:</strong></p>
    <ul>
        <li>GET action: 2 unique attachments</li>
        <li>LIST action: 2 unique attachments</li>
        <li>Frontend: Shows exactly 2 attachments</li>
        <li>No more duplicates in future requests</li>
    </ul>
</div>
