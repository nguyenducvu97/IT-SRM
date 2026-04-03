<?php
// Force cache refresh and debug script
echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>Force Cache Refresh - Service Request Attachments</title>";
echo "<style>";
echo "body { font-family: Arial, sans-serif; padding: 20px; }";
echo ".debug-section { border: 1px solid #ccc; padding: 15px; margin: 10px 0; }";
echo ".success { color: green; font-weight: bold; }";
echo ".warning { color: orange; font-weight: bold; }";
echo ".error { color: red; font-weight: bold; }";
echo "</style>";
echo "</head>";
echo "<body>";

echo "<h1>🔧 Service Request Attachments Debug</h1>";

// Get request ID from URL
$request_id = isset($_GET['id']) ? (int)$_GET['id'] : 22;
echo "<div class='debug-section'>";
echo "<h2>Request ID: $request_id</h2>";
echo "<p><a href='request-detail.html?id=$request_id' target='_blank'>🔗 Open Request Detail (New Tab)</a></p>";
echo "<p><a href='request-detail.html?id=$request_id&v=" . time() . "' target='_blank'>🔄 Force Refresh Request Detail</a></p>";
echo "</div>";

// Check current session
echo "<div class='debug-section'>";
echo "<h2>Current Session:</h2>";
if (isset($_SESSION['user_id'])) {
    echo "<p class='success'>✅ Logged in as: " . $_SESSION['full_name'] . " (" . $_SESSION['role'] . ")</p>";
} else {
    echo "<p class='error'>❌ Not logged in</p>";
}
echo "</div>";

// Test API directly
echo "<div class='debug-section'>";
echo "<h2>Direct API Test:</h2>";
$api_url = "http://localhost/it-service-request/api/service_requests.php?action=get&id=$request_id";
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "Cookie: " . ($_SERVER['HTTP_COOKIE'] ?? '') . "\r\n"
    ]
]);

$response = file_get_contents($api_url, false, $context);
if ($response) {
    $data = json_decode($response, true);
    if ($data && $data['success']) {
        $request = $data['data'];
        echo "<p class='success'>✅ API Response: SUCCESS</p>";
        echo "<p><strong>Title:</strong> " . $request['title'] . "</p>";
        echo "<p><strong>Status:</strong> " . $request['status'] . "</p>";
        
        if (isset($request['attachments'])) {
            echo "<p><strong>Attachments in API:</strong> " . count($request['attachments']) . "</p>";
            foreach ($request['attachments'] as $attachment) {
                echo "- " . $attachment['original_name'] . " (" . number_format($attachment['file_size']) . " bytes)<br>";
            }
        } else {
            echo "<p class='warning'>⚠️ No attachments key in API response</p>";
        }
    } else {
        echo "<p class='error'>❌ API Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
} else {
    echo "<p class='error'>❌ API Call Failed</p>";
}
echo "</div>";

// Database check
echo "<div class='debug-section'>";
echo "<h2>Database Check:</h2>";
try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    $query = "SELECT a.id, a.original_name, a.file_size, a.mime_type 
              FROM attachments a 
              WHERE a.service_request_id = :request_id 
              ORDER BY a.uploaded_at ASC";
    $stmt = $db->prepare($query);
    $stmt->bindValue(':request_id', $request_id, PDO::PARAM_INT);
    $stmt->execute();
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p><strong>Attachments in Database:</strong> " . count($attachments) . "</p>";
    foreach ($attachments as $attachment) {
        echo "- " . $attachment['original_name'] . " (" . number_format($attachment['file_size']) . " bytes)<br>";
    }
    
} catch (Exception $e) {
    echo "<p class='error'>❌ Database Error: " . $e->getMessage() . "</p>";
}
echo "</div>";

// JavaScript debug
echo "<div class='debug-section'>";
echo "<h2>JavaScript Debug Instructions:</h2>";
echo "<ol>";
echo "<li>Open request detail page: <a href='request-detail.html?id=$request_id' target='_blank'>request-detail.html?id=$request_id</a></li>";
echo "<li>Open browser console (F12)</li>";
echo "<li>Look for these logs:</li>";
echo "<ul>";
echo "<li><code>Loading reject request details for ID: [ID]</code></li>";
echo "<li><code>Reject request API response: [data]</code></li>";
echo "<li><code>Attachments found: [number]</code></li>";
echo "</ul>";
echo "<li>Check Network tab for API calls:</li>";
echo "<ul>";
echo "<li><code>api/service_requests.php?action=get&id=$request_id</code></li>";
echo "<li><code>api/reject_requests.php?action=get&id=[reject_id]</code></li>";
echo "</ul>";
echo "</ol>";
echo "</div>";

// Cache refresh
echo "<div class='debug-section'>";
echo "<h2>Cache Refresh:</h2>";
echo "<p><strong>Step 1:</strong> Clear browser cache (Ctrl+Shift+Del)</p>";
echo "<p><strong>Step 2:</strong> Hard refresh (Ctrl+F5)</p>";
echo "<p><strong>Step 3:</strong> Open in incognito mode</p>";
echo "</div>";

echo "</body>";
echo "</html>";
?>
