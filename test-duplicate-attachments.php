<?php
// Test file to diagnose duplicate reject request attachments issue
require_once 'config/database.php';

echo "<h2>Reject Request Attachments - Duplicate Issue Diagnosis</h2>";

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

// Get a specific reject request to test
$reject_request_id = $_GET['reject_id'] ?? 103; // Default to a known ID

echo "<h3>Testing Reject Request ID: $reject_request_id</h3>";

// Check reject request
$reject_query = "SELECT id, service_request_id, status FROM reject_requests WHERE id = :id";
$reject_stmt = $db->prepare($reject_query);
$reject_stmt->bindParam(':id', $reject_request_id);
$reject_stmt->execute();
$reject_request = $reject_stmt->fetch(PDO::FETCH_ASSOC);

if (!$reject_request) {
    echo "<p style='color: red;'>Reject request not found!</p>";
    exit;
}

echo "<h4>Reject Request Info:</h4>";
echo "<pre>" . print_r($reject_request, true) . "</pre>";

// Check attachments
$attachment_query = "SELECT id, filename, original_name, file_size, mime_type, created_at, uploaded_at 
                     FROM reject_request_attachments 
                     WHERE reject_request_id = :id 
                     ORDER BY created_at ASC, id ASC";
$attachment_stmt = $db->prepare($attachment_query);
$attachment_stmt->bindParam(':id', $reject_request_id);
$attachment_stmt->execute();
$attachments = $attachment_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h4>Attachments in Database (" . count($attachments) . " items):</h4>";
if (empty($attachments)) {
    echo "<p>No attachments found.</p>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Filename</th><th>Original Name</th><th>Size</th><th>MIME Type</th><th>Created At</th><th>Uploaded At</th><th>File Exists</th></tr>";
    
    foreach ($attachments as $attachment) {
        $filePath = __DIR__ . '/uploads/reject_requests/' . $attachment['filename'];
        $fileExists = file_exists($filePath) ? 'YES' : 'NO';
        
        echo "<tr>";
        echo "<td>{$attachment['id']}</td>";
        echo "<td>{$attachment['filename']}</td>";
        echo "<td>{$attachment['original_name']}</td>";
        echo "<td>{$attachment['file_size']}</td>";
        echo "<td>{$attachment['mime_type']}</td>";
        echo "<td>{$attachment['created_at']}</td>";
        echo "<td>{$attachment['uploaded_at']}</td>";
        echo "<td style='color: " . ($fileExists === 'YES' ? 'green' : 'red') . ";'>$fileExists</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Check for potential duplicates
echo "<h4>Duplicate Check:</h4>";
$duplicate_check = [];
foreach ($attachments as $attachment) {
    $key = $attachment['filename'];
    if (!isset($duplicate_check[$key])) {
        $duplicate_check[$key] = [];
    }
    $duplicate_check[$key][] = $attachment;
}

$has_duplicates = false;
foreach ($duplicate_check as $filename => $items) {
    if (count($items) > 1) {
        $has_duplicates = true;
        echo "<p style='color: red; font-weight: bold;'>DUPLICATE FOUND: $filename (" . count($items) . " times)</p>";
        foreach ($items as $item) {
            echo "<pre> - ID: {$item['id']}, Created: {$item['created_at']}, Uploaded: {$item['uploaded_at']}</pre>";
        }
    }
}

if (!$has_duplicates) {
    echo "<p style='color: green; font-weight: bold;'>No duplicates found in database.</p>";
}

// Test API response
echo "<h4>API Response Test:</h4>";
$service_request_id = $reject_request['service_request_id'];
$api_url = "http://localhost/it-service-request/api/service_requests.php?id={$service_request_id}";

echo "<p>Testing API: <code>$api_url</code></p>";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: ' . $_SERVER['HTTP_COOKIE'] ?? ''
    ]
]);

$response = file_get_contents($api_url, false, $context);
if ($response) {
    $data = json_decode($response, true);
    if ($data && isset($data['data']['reject_request']['attachments'])) {
        $api_attachments = $data['data']['reject_request']['attachments'];
        echo "<p>API returned " . count($api_attachments) . " attachments</p>";
        
        if (count($api_attachments) !== count($attachments)) {
            echo "<p style='color: red; font-weight: bold;'>MISMATCH: Database has " . count($attachments) . " but API returned " . count($api_attachments) . "</p>";
        } else {
            echo "<p style='color: green;'>Database and API counts match.</p>";
        }
        
        // Check for duplicates in API response
        $api_duplicates = [];
        foreach ($api_attachments as $attachment) {
            $key = $attachment['filename'];
            if (!isset($api_duplicates[$key])) {
                $api_duplicates[$key] = [];
            }
            $api_duplicates[$key][] = $attachment;
        }
        
        $api_has_duplicates = false;
        foreach ($api_duplicates as $filename => $items) {
            if (count($items) > 1) {
                $api_has_duplicates = true;
                echo "<p style='color: red; font-weight: bold;'>API DUPLICATE FOUND: $filename (" . count($items) . " times)</p>";
            }
        }
        
        if (!$api_has_duplicates) {
            echo "<p style='color: green;'>No duplicates in API response.</p>";
        }
    } else {
        echo "<p style='color: red;'>Failed to parse API response or no attachments found</p>";
    }
} else {
    echo "<p style='color: red;'>Failed to call API</p>";
}

echo "<h3>Frontend Debug Instructions:</h3>";
echo "<ol>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Go to Console tab</li>";
echo "<li>Navigate to request detail page with reject request</li>";
echo "<li>Look for 'DEBUG: Reject Request Attachments' messages</li>";
echo "<li>Look for 'DEBUG: Rendering attachment' messages</li>";
echo "<li>Check if attachments are being logged multiple times</li>";
echo "</ol>";

echo "<h3>Potential Causes:</h3>";
echo "<ul>";
echo "<li>Browser cache loading old JavaScript version</li>";
echo "<li>Template string being rendered multiple times</li>";
echo "<li>API returning duplicate data</li>";
echo "<li>Frontend rendering logic being called multiple times</li>";
echo "</ul>";

echo "<p><strong>After testing, check browser console for debug messages to identify the root cause.</strong></p>";
?>
