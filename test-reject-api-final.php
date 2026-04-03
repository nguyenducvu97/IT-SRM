<?php
// Final test of reject requests API
require_once 'config/session.php';
require_once 'config/database.php';

echo "<h2>Final Reject Requests API Test</h2>";

// Start session
startSession();

// Get database connection
$db = getDatabaseConnection();

echo "<h3>Current Session Status:</h3>";
if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'admin') {
    echo "✅ Admin session active<br>";
    echo "User ID: " . $_SESSION['user_id'] . "<br>";
    echo "Username: " . $_SESSION['username'] . "<br>";
    echo "Role: " . $_SESSION['role'] . "<br>";
} else {
    echo "❌ No valid admin session<br>";
    echo "<pre>" . json_encode($_SESSION) . "</pre>";
    exit;
}

// Test the main API directly
echo "<h3>Testing Main API:</h3>";

// Set up the environment like the browser would
$_GET['action'] = 'list';
$_SERVER['REQUEST_METHOD'] = 'GET';

// Capture output
ob_start();
try {
    include 'api/reject_requests.php';
    $response = ob_get_contents();
} catch (Exception $e) {
    $response = "Exception: " . $e->getMessage();
} catch (Error $e) {
    $response = "Error: " . $e->getMessage();
} catch (ParseError $e) {
    $response = "Parse Error: " . $e->getMessage();
}
ob_end_clean();

echo "<h4>API Response:</h4>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

// Try to decode JSON if it's JSON
$json_data = json_decode($response, true);
if ($json_data !== null) {
    echo "<h4>JSON Decoded Successfully:</h4>";
    echo "<pre>" . json_encode($json_data, JSON_PRETTY_PRINT) . "</pre>";
    
    if (isset($json_data['success']) && $json_data['success']) {
        echo "<h4>✅ Success! Found " . count($json_data['data']['reject_requests'] ?? []) . " reject requests</h4>";
    } else {
        echo "<h4>❌ API Error: " . ($json_data['message'] ?? 'Unknown error') . "</h4>";
    }
} else {
    echo "<h4>❌ Response is not valid JSON</h4>";
}

// Test with cURL as well
echo "<h3>Testing with cURL (like browser):</h3>";

$session_name = session_name();
$session_id = session_id();

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/reject_requests.php?action=list');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, $session_name . '=' . $session_id);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$curl_response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $http_code</p>";
if ($curl_error) {
    echo "<p><strong>cURL Error:</strong> $curl_error</p>";
}
echo "<p><strong>cURL Response:</strong></p>";
echo "<pre>" . htmlspecialchars($curl_response) . "</pre>";

// Check if there are any PHP errors in the log
echo "<h3>Recent PHP Errors (if any):</h3>";
$error_log = 'C:/xampp/apache/logs/error.log';
if (file_exists($error_log)) {
    $lines = file($error_log);
    $recent_lines = array_slice($lines, -20); // Last 20 lines
    echo "<pre>" . htmlspecialchars(implode('', $recent_lines)) . "</pre>";
} else {
    echo "<p>No error log found at: $error_log</p>";
}
?>
