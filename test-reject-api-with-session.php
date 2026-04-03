<?php
// Test reject requests API with proper session
require_once 'config/session.php';
require_once 'config/database.php';

echo "<h2>Reject Requests API Test with Session</h2>";

// Start session
startSession();

// Get database connection
$db = getDatabaseConnection();

echo "<h3>Current Session Data:</h3>";
echo "<pre>";
var_dump($_SESSION);
echo "</pre>";

if (!isset($_SESSION['user_id'])) {
    echo "<h3>❌ No user session found</h3>";
    echo "<p>Please <a href='index.html'>login first</a></p>";
    exit;
}

echo "<h3>✅ User Session Found:</h3>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";
echo "<p>Username: " . $_SESSION['username'] . "</p>";
echo "<p>Role: " . $_SESSION['role'] . "</p>";

// Test API with session
echo "<h3>Testing API with Session:</h3>";

// Prepare the request URL
$url = 'http://localhost/it-service-request/api/reject_requests.php?action=list';

// Get session cookie
$session_name = session_name();
$session_id = session_id();

echo "<p>Session Name: $session_name</p>";
echo "<p>Session ID: $session_id</p>";

// Use curl with session cookie
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, $session_name . '=' . $session_id);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

echo "<h4>API Test Results:</h4>";
echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
if ($curlError) {
    echo "<p><strong>cURL Error:</strong> $curlError</p>";
}
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Also test the simple API
echo "<h3>Testing Simple API with Session:</h3>";
$url_simple = 'http://localhost/it-service-request/api/reject_requests_simple.php?action=list';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url_simple);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, $session_name . '=' . $session_id);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$response_simple = curl_exec($ch);
$httpCode_simple = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError_simple = curl_error($ch);
curl_close($ch);

echo "<h4>Simple API Test Results:</h4>";
echo "<p><strong>HTTP Code:</strong> $httpCode_simple</p>";
if ($curlError_simple) {
    echo "<p><strong>cURL Error:</strong> $curlError_simple</p>";
}
echo "<p><strong>Response:</strong></p>";
echo "<pre>" . htmlspecialchars($response_simple) . "</pre>";

// Test if we can access the API directly through PHP include
echo "<h3>Direct PHP Include Test:</h3>";

// Backup current session
$backup_session = $_SESSION;

// Simulate API call
$_GET['action'] = 'list';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture output
ob_start();
try {
    include 'api/reject_requests.php';
    $direct_response = ob_get_contents();
} catch (Exception $e) {
    $direct_response = "Exception: " . $e->getMessage();
} catch (Error $e) {
    $direct_response = "Error: " . $e->getMessage();
}
ob_end_clean();

// Restore session
$_SESSION = $backup_session;

echo "<p><strong>Direct Include Response:</strong></p>";
echo "<pre>" . htmlspecialchars($direct_response) . "</pre>";
?>
