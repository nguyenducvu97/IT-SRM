<?php
// Quick test to check reject request for service request 18
session_start();

// Set admin session
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';

echo "<h2>Quick Reject Request Check - Service Request ID 18</h2>";

try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    // Check if service request 18 exists
    echo "<h3>Step 1: Check Service Request 18</h3>";
    $sr_query = "SELECT id, title, status FROM service_requests WHERE id = 18";
    $sr_stmt = $db->prepare($sr_query);
    $sr_stmt->bindValue(':id', 18, PDO::PARAM_INT);
    $sr_stmt->execute();
    $service_request = $sr_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($service_request) {
        echo "<p style='color: green;'>✅ Service Request 18 found: " . $service_request['title'] . " (Status: " . $service_request['status'] . ")</p>";
    } else {
        echo "<p style='color: red;'>❌ Service Request 18 not found!</p>";
        exit;
    }
    
    // Check reject requests for service request 18
    echo "<h3>Step 2: Check Reject Requests</h3>";
    $rr_query = "SELECT id, service_request_id, status, reject_reason, rejected_by, created_at 
                 FROM reject_requests 
                 WHERE service_request_id = 18 
                 ORDER BY created_at DESC";
    $rr_stmt = $db->prepare($rr_query);
    $rr_stmt->bindValue(':service_request_id', 18, PDO::PARAM_INT);
    $rr_stmt->execute();
    $reject_requests = $rr_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Reject requests found:</strong> " . count($reject_requests) . "</p>";
    
    if (count($reject_requests) > 0) {
        foreach ($reject_requests as $rr) {
            echo "<div style='border: 1px solid #ccc; padding: 10px; margin: 5px 0;'>";
            echo "<strong>Reject Request ID:</strong> " . $rr['id'] . "<br>";
            echo "<strong>Service Request ID:</strong> " . $rr['service_request_id'] . "<br>";
            echo "<strong>Status:</strong> " . $rr['status'] . "<br>";
            echo "<strong>Reject Reason:</strong> " . $rr['reject_reason'] . "<br>";
            echo "<strong>Rejected By:</strong> " . $rr['rejected_by'] . "<br>";
            echo "<strong>Created At:</strong> " . $rr['created_at'] . "<br>";
            
            // Test API call for this reject request
            echo "<h5>API Test for Reject Request ID " . $rr['id'] . ":</h5>";
            $api_url = "http://localhost/it-service-request/api/reject_requests.php?action=get&id=" . $rr['id'];
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
                    echo "<p style='color: green;'>✅ API Response: SUCCESS</p>";
                    echo "<p><strong>Attachments:</strong> " . count($data['data']['attachments']) . "</p>";
                    
                    // Show attachments
                    foreach ($data['data']['attachments'] as $attachment) {
                        echo "- " . $attachment['original_name'] . " (" . number_format($attachment['file_size']) . " bytes)<br>";
                    }
                } else {
                    echo "<p style='color: red;'>❌ API Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
                    echo "<pre>" . htmlspecialchars($response) . "</pre>";
                }
            } else {
                echo "<p style='color: red;'>❌ API Call Failed</p>";
            }
            
            echo "</div>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No reject requests found for service request 18</p>";
    }
    
    // Test service_requests.php API
    echo "<h3>Step 3: Test Service Requests API</h3>";
    $api_url = "http://localhost/it-service-request/api/service_requests.php?action=get&id=18";
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
            echo "<p style='color: green;'>✅ Service Requests API: SUCCESS</p>";
            if (isset($data['data']['reject_request'])) {
                echo "<p><strong>Reject Request in Service API:</strong></p>";
                echo "<pre>" . print_r($data['data']['reject_request'], true) . "</pre>";
            } else {
                echo "<p style='color: orange;'>⚠️ No reject_request in service API response</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ Service Requests API Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Service Requests API Call Failed</p>";
    }
    
} catch (Exception $e) {
    echo "<h3>Error:</h3>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
}
?>
