<?php
// Test script to check reject request attachments for request ID 18
session_start();

// Set admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';

echo "<h2>Reject Request Attachments Test - Request ID 18</h2>";

try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    // Step 1: Find reject request for service request 18
    echo "<h3>Step 1: Find reject request for service request 18</h3>";
    $reject_query = "SELECT id, service_request_id, status, reject_reason, reject_details, created_at 
                     FROM reject_requests 
                     WHERE service_request_id = 18";
    $reject_stmt = $db->prepare($reject_query);
    $reject_stmt->bindValue(':service_request_id', 18, PDO::PARAM_INT);
    $reject_stmt->execute();
    $reject_requests = $reject_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Reject requests found:</strong> " . count($reject_requests) . "</p>";
    
    foreach ($reject_requests as $rr) {
        echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
        echo "<strong>Reject Request ID:</strong> " . $rr['id'] . "<br>";
        echo "<strong>Service Request ID:</strong> " . $rr['service_request_id'] . "<br>";
        echo "<strong>Status:</strong> " . $rr['status'] . "<br>";
        echo "<strong>Reject Reason:</strong> " . $rr['reject_reason'] . "<br>";
        echo "<strong>Created At:</strong> " . $rr['created_at'] . "<br>";
        echo "</div>";
        
        // Step 2: Check attachments for this reject request
        echo "<h4>Attachments for Reject Request ID " . $rr['id'] . ":</h4>";
        $attachment_query = "SELECT id, original_name, filename, file_size, mime_type, uploaded_at 
                            FROM reject_request_attachments 
                            WHERE reject_request_id = :reject_request_id 
                            ORDER BY uploaded_at ASC";
        $attachment_stmt = $db->prepare($attachment_query);
        $attachment_stmt->bindValue(':reject_request_id', $rr['id'], PDO::PARAM_INT);
        $attachment_stmt->execute();
        $attachments = $attachment_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<p><strong>Attachments found:</strong> " . count($attachments) . "</p>";
        
        foreach ($attachments as $attachment) {
            echo "<div style='border: 1px solid #ddd; padding: 8px; margin: 3px 0; background: #f9f9f9;'>";
            echo "<strong>ID:</strong> " . $attachment['id'] . "<br>";
            echo "<strong>Original Name:</strong> " . $attachment['original_name'] . "<br>";
            echo "<strong>Filename:</strong> " . $attachment['filename'] . "<br>";
            echo "<strong>Size:</strong> " . number_format($attachment['file_size']) . " bytes<br>";
            echo "<strong>Type:</strong> " . $attachment['mime_type'] . "<br>";
            echo "<strong>Uploaded At:</strong> " . $attachment['uploaded_at'] . "<br>";
            echo "</div>";
        }
    }
    
    // Step 3: Check for duplicate attachments
    echo "<h3>Step 3: Check for duplicate attachments</h3>";
    $duplicate_query = "SELECT original_name, filename, COUNT(*) as count 
                        FROM reject_request_attachments 
                        WHERE reject_request_id IN (SELECT id FROM reject_requests WHERE service_request_id = 18)
                        GROUP BY original_name, filename 
                        HAVING COUNT(*) > 1";
    $duplicate_stmt = $db->prepare($duplicate_query);
    $duplicate_stmt->execute();
    $duplicates = $duplicate_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Duplicates found:</strong> " . count($duplicates) . "</p>";
    
    foreach ($duplicates as $duplicate) {
        echo "<div style='border: 1px solid red; padding: 8px; margin: 3px 0; background: #ffe6e6;'>";
        echo "<strong>DUPLICATE FOUND:</strong> " . $duplicate['original_name'] . "<br>";
        echo "<strong>Count:</strong> " . $duplicate['count'] . "<br>";
        echo "</div>";
    }
    
    // Step 4: Test API response
    echo "<h3>Step 4: Test API Response</h3>";
    if (count($reject_requests) > 0) {
        $reject_id = $reject_requests[0]['id'];
        
        $api_url = "http://localhost/it-service-request/api/reject_requests.php?action=get&id=$reject_id";
        $context = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n"
            ]
        ]);

        $response = file_get_contents($api_url, false, $context);

        if ($response) {
            $data = json_decode($response, true);
            if ($data && $data['success']) {
                echo "<p><strong>API Response: SUCCESS</strong></p>";
                echo "<p><strong>Attachments in API:</strong> " . count($data['data']['attachments']) . "</p>";
                
                foreach ($data['data']['attachments'] as $attachment) {
                    echo "- " . $attachment['original_name'] . " (" . number_format($attachment['file_size']) . " bytes)<br>";
                }
            } else {
                echo "<p style='color: red;'><strong>API Error:</strong> " . ($data['message'] ?? 'Unknown error') . "</p>";
            }
        } else {
            echo "<p style='color: red;'><strong>API Call Failed</strong></p>";
        }
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
