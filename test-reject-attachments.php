<?php
// Test script to check reject request attachments API
session_start();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'admin';

echo "<h2>Test Reject Request Attachments API</h2>";

// Test the API directly
$reject_request_id = isset($_GET['id']) ? (int)$_GET['id'] : 36; // Default test ID

echo "<h3>Testing API: api/reject_requests.php?action=get&id=$reject_request_id</h3>";

// Make API call
$api_url = "http://localhost/it-service-request/api/reject_requests.php?action=get&id=$reject_request_id";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: " . $_SERVER['HTTP_COOKIE'] . "\r\n"
    ]
]);

$response = file_get_contents($api_url, false, $context);

if ($response) {
    echo "<h4>API Response:</h4>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    // Parse JSON
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        echo "<h4>Parsed Data:</h4>";
        echo "<pre>" . print_r($data['data'], true) . "</pre>";
        
        if (isset($data['data']['attachments'])) {
            echo "<h4>Attachments Found: " . count($data['data']['attachments']) . "</h4>";
            foreach ($data['data']['attachments'] as $attachment) {
                echo "- " . $attachment['original_name'] . " (" . $attachment['file_size'] . " bytes)<br>";
            }
        } else {
            echo "<h4>No attachments found in response!</h4>";
        }
    } else {
        echo "<h4>API Error:</h4>";
        echo "<p>" . ($data['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<h4>API Call Failed!</h4>";
    echo "<p>Could not connect to API</p>";
}

echo "<h3>Test Database Query:</h3>";
try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    $query = "SELECT original_name, filename, file_size, mime_type 
              FROM reject_request_attachments 
              WHERE reject_request_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':id', $reject_request_id, PDO::PARAM_INT);
    $stmt->execute();
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h4>Direct Database Query Results:</h4>";
    echo "<pre>" . print_r($attachments, true) . "</pre>";
    echo "<p>Found " . count($attachments) . " attachments in database</p>";
    
} catch (Exception $e) {
    echo "<h4>Database Error:</h4>";
    echo "<p>" . $e->getMessage() . "</p>";
}
?>
