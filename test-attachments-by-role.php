<?php
// Test script to check service request attachments for different user roles
session_start();

echo "<h2>Test Service Request Attachments by Role</h2>";

// Test request ID
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 28; // Default test ID

echo "<h3>Testing Request ID: $request_id</h3>";

// Test as different users
$test_users = [
    ['user_id' => 2, 'username' => 'staff1', 'full_name' => 'Staff User 1', 'role' => 'staff'],
    ['user_id' => 1, 'username' => 'admin', 'full_name' => 'System Administrator', 'role' => 'admin'],
    ['user_id' => 3, 'username' => 'user1', 'full_name' => 'Regular User 1', 'role' => 'user']
];

foreach ($test_users as $user) {
    echo "<h4>Testing as {$user['role']} ({$user['full_name']}):</h4>";
    
    // Set session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['username'] = $user['username'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role'] = $user['role'];
    
    // Make API call
    $api_url = "http://localhost/it-service-request/api/service_requests.php?action=get&id=$request_id";
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
            $request = $data['data'];
            echo "<p><strong>Request Title:</strong> " . $request['title'] . "</p>";
            echo "<p><strong>Status:</strong> " . $request['status'] . "</p>";
            
            if (isset($request['attachments'])) {
                echo "<p><strong>Attachments Found:</strong> " . count($request['attachments']) . "</p>";
                foreach ($request['attachments'] as $attachment) {
                    echo "- " . $attachment['original_name'] . " (" . $attachment['file_size'] . " bytes)<br>";
                }
            } else {
                echo "<p><strong>Attachments Found:</strong> 0 (No attachments key)</p>";
            }
            
            if (isset($request['reject_request'])) {
                echo "<p><strong>Reject Request:</strong> Yes</p>";
                if (isset($request['reject_request']['attachments'])) {
                    echo "<p><strong>Reject Request Attachments:</strong> " . count($request['reject_request']['attachments']) . "</p>";
                }
            }
            
        } else {
            echo "<p style='color: red;'>API Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
        }
    } else {
        echo "<p style='color: red;'>API Call Failed!</p>";
    }
    
    echo "<hr>";
}

// Test direct database query
echo "<h3>Direct Database Query:</h3>";
try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    $query = "SELECT a.id, a.original_name, a.file_size, a.mime_type, sr.title as request_title, sr.status
              FROM attachments a
              JOIN service_requests sr ON a.service_request_id = sr.id
              WHERE a.service_request_id = :request_id
              ORDER BY a.uploaded_at ASC";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':request_id', $request_id, PDO::PARAM_INT);
    $stmt->execute();
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>All Attachments in Database:</h4>";
    echo "<pre>" . print_r($attachments, true) . "</pre>";
    echo "<p>Total attachments in database: " . count($attachments) . "</p>";
    
} catch (Exception $e) {
    echo "<h4>Database Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
