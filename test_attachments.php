<?php
require_once 'config/database.php';
require_once 'config/session.php';

// Start session for authentication
startSession();

// Check if user is logged in
if (!isLoggedIn()) {
    die("Please login first");
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

echo "<h2>Service Requests with Attachments</h2>";

$attachments_query = "SELECT sr.id, sr.title, COUNT(a.id) as attachment_count 
                     FROM service_requests sr 
                     LEFT JOIN attachments a ON sr.id = a.service_request_id 
                     GROUP BY sr.id, sr.title 
                     HAVING COUNT(a.id) > 0 
                     ORDER BY COUNT(a.id) DESC 
                     LIMIT 10";

$stmt = $db->prepare($attachments_query);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Request ID</th><th>Title</th><th>Attachment Count</th><th>Test API</th></tr>";

foreach ($requests as $request) {
    echo "<tr>";
    echo "<td>{$request['id']}</td>";
    echo "<td>" . htmlspecialchars($request['title']) . "</td>";
    echo "<td>{$request['attachment_count']}</td>";
    echo "<td><a href='api/service_requests.php?action=get&id={$request['id']}' target='_blank'>Test API</a></td>";
    echo "</tr>";
}

echo "</table>";

echo "<h2>All Support Requests (Debug)</h2>";

$all_support_query = "SELECT sr.id, sr.title, sr.description, sr.requester_id, sr.service_request_id, 
                      COUNT(a.id) as attachment_count 
                      FROM support_requests sr 
                      LEFT JOIN support_request_attachments a ON sr.id = a.support_request_id 
                      GROUP BY sr.id, sr.title, sr.description, sr.requester_id, sr.service_request_id 
                      ORDER BY sr.id DESC 
                      LIMIT 10";

$all_support_stmt = $db->prepare($all_support_query);
$all_support_stmt->execute();
$all_support_requests = $all_support_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p><strong>Found " . count($all_support_requests) . " support requests total</strong></p>";

echo "<table border='1'>";
echo "<tr><th>Support ID</th><th>Title</th><th>Description</th><th>Requester ID</th><th>Service Request ID</th><th>Attachment Count</th><th>Test API</th></tr>";

foreach ($all_support_requests as $request) {
    echo "<tr>";
    echo "<td>{$request['id']}</td>";
    echo "<td>" . htmlspecialchars($request['title']) . "</td>";
    echo "<td>" . htmlspecialchars(substr($request['description'], 0, 50)) . "...</td>";
    echo "<td>{$request['requester_id']}</td>";
    echo "<td>{$request['service_request_id']}</td>";
    echo "<td>{$request['attachment_count']}</td>";
    echo "<td><a href='api/support_requests.php?action=get&id={$request['id']}' target='_blank'>Test API</a></td>";
    echo "</tr>";
}

echo "</table>";

// Also test the API directly
echo "<h2>API Test Results</h2>";
if (!empty($all_support_requests)) {
    $test_id = $all_support_requests[0]['id'];
    echo "<p>Testing API with ID: $test_id</p>";
    
    // Simulate API call
    $api_query = "SELECT sr.*, 
                   u.username as requester_name,
                   srq.title as request_title
            FROM support_requests sr
            JOIN users u ON sr.requester_id = u.id
            JOIN service_requests srq ON sr.service_request_id = srq.id
            WHERE sr.id = ?";
    
    $api_stmt = $db->prepare($api_query);
    $api_stmt->execute([$test_id]);
    $api_result = $api_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($api_result) {
        echo "<p style='color: green;'>✅ API Query SUCCESS - Found request</p>";
        echo "<pre>" . print_r($api_result, true) . "</pre>";
    } else {
        echo "<p style='color: red;'>❌ API Query FAILED - Request not found</p>";
    }
}

echo "<h2>Reject Requests with Attachments</h2>";

$reject_attachments_query = "SELECT rr.id, rr.title, COUNT(a.id) as attachment_count 
                             FROM reject_requests rr 
                             LEFT JOIN reject_request_attachments a ON rr.id = a.reject_request_id 
                             GROUP BY rr.id, rr.title 
                             HAVING COUNT(a.id) > 0 
                             ORDER BY COUNT(a.id) DESC 
                             LIMIT 10";

$reject_stmt = $db->prepare($reject_attachments_query);
$reject_stmt->execute();
$reject_requests = $reject_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($reject_requests)) {
    echo "<p><strong>No reject requests found with attachments.</strong></p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>Reject ID</th><th>Title</th><th>Attachment Count</th><th>Test API</th></tr>";

    foreach ($reject_requests as $request) {
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . htmlspecialchars($request['title']) . "</td>";
        echo "<td>{$request['attachment_count']}</td>";
        echo "<td><a href='api/reject_requests.php?action=get&id={$request['id']}' target='_blank'>Test API</a></td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo "<h2>Resolution Requests with Attachments</h2>";

$resolution_attachments_query = "SELECT r.id, sr.title as request_title, COUNT(a.id) as attachment_count 
                                FROM resolutions r 
                                LEFT JOIN service_requests sr ON r.service_request_id = sr.id 
                                LEFT JOIN resolution_attachments a ON r.id = a.resolution_id 
                                GROUP BY r.id, sr.title 
                                HAVING COUNT(a.id) > 0 
                                ORDER BY COUNT(a.id) DESC 
                                LIMIT 10";

$resolution_stmt = $db->prepare($resolution_attachments_query);
$resolution_stmt->execute();
$resolution_requests = $resolution_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($resolution_requests)) {
    echo "<p><strong>No resolution requests found with attachments.</strong></p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>Resolution ID</th><th>Request Title</th><th>Attachment Count</th><th>Test API</th></tr>";

    foreach ($resolution_requests as $request) {
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . htmlspecialchars($request['request_title']) . "</td>";
        echo "<td>{$request['attachment_count']}</td>";
        echo "<td><a href='api/service_requests.php?action=get&id={$request['id']}' target='_blank'>Test API</a></td>";
        echo "</tr>";
    }

    echo "</table>";
}

echo "<h2>Support Requests with Attachments</h2>";

$support_attachments_query = "SELECT sr.id, sr.title, COUNT(a.id) as attachment_count 
                              FROM support_requests sr 
                              LEFT JOIN support_request_attachments a ON sr.id = a.support_request_id 
                              GROUP BY sr.id, sr.title 
                              HAVING COUNT(a.id) > 0 
                              ORDER BY COUNT(a.id) DESC 
                              LIMIT 10";

$support_stmt = $db->prepare($support_attachments_query);
$support_stmt->execute();
$support_requests = $support_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($support_requests)) {
    echo "<p><strong>No support requests found with attachments.</strong></p>";
} else {
    echo "<table border='1'>";
    echo "<tr><th>Support ID</th><th>Title</th><th>Attachment Count</th><th>Test API</th></tr>";

    foreach ($support_requests as $request) {
        echo "<tr>";
        echo "<td>{$request['id']}</td>";
        echo "<td>" . htmlspecialchars($request['title']) . "</td>";
        echo "<td>{$request['attachment_count']}</td>";
        echo "<td><a href='api/support_requests.php?action=get&id={$request['id']}' target='_blank'>Test API</a></td>";
        echo "</tr>";
    }

    echo "</table>";
}

// Test one specific support request
if (!empty($support_requests)) {
    $test_request_id = $support_requests[0]['id'];
    echo "<h2>Testing Support Request ID: $test_request_id</h2>";
    
    // Test the API call
    $attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 
                         FROM support_request_attachments 
                         WHERE support_request_id = :id 
                         ORDER BY uploaded_at ASC";
    $attachments_stmt = $db->prepare($attachments_query);
    $attachments_stmt->bindParam(":id", $test_request_id);
    $attachments_stmt->execute();
    
    $attachments = $attachments_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Attachments Found: " . count($attachments) . "</h3>";
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Filename</th><th>Original Name</th><th>Size</th><th>MIME Type</th><th>Uploaded At</th></tr>";
    
    foreach ($attachments as $attachment) {
        echo "<tr>";
        echo "<td>{$attachment['id']}</td>";
        echo "<td>{$attachment['filename']}</td>";
        echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
        echo "<td>{$attachment['file_size']}</td>";
        echo "<td>{$attachment['mime_type']}</td>";
        echo "<td>{$attachment['uploaded_at']}</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    // Test file existence
    echo "<h3>File Existence Check</h3>";
    $uploads_dir = __DIR__ . '/uploads/support_requests/';
    
    foreach ($attachments as $attachment) {
        $file_path = $uploads_dir . $attachment['filename'];
        $exists = file_exists($file_path);
        $size = $exists ? filesize($file_path) : 0;
        
        echo "<p>";
        echo "<strong>{$attachment['original_name']}</strong><br>";
        echo "Path: {$attachment['filename']}<br>";
        echo "Exists: " . ($exists ? "YES" : "NO") . "<br>";
        echo "Size: " . $size . " bytes<br>";
        echo "</p>";
    }
}

?>
