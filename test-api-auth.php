<?php
// Test API authentication and session
session_start();

echo "<h2>API Authentication Test</h2>";

// Check current session
echo "<h3>Current Session Status:</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "Session Data: " . json_encode($_SESSION) . "\n";
echo "</pre>";

// Try to start proper session
require_once 'config/session.php';
require_once 'config/database.php';

try {
    startSession();
    echo "<p style='color: green;'>✅ Session started successfully</p>";
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Session error: " . $e->getMessage() . "</p>";
}

echo "<h3>After startSession():</h3>";
echo "<pre>";
echo "Session ID: " . session_id() . "\n";
echo "User ID: " . ($_SESSION['user_id'] ?? 'Not set') . "\n";
echo "User Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
echo "Full Session: " . json_encode($_SESSION) . "\n";
echo "</pre>";

// Test API call with current session
echo "<h3>API Test with Current Session:</h3>";

$api_url = "http://localhost/it-service-request/api/reject_requests.php?action=list&page=1&limit=9";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Cookie: PHPSESSID=' . session_id()
    ]
]);

$response = file_get_contents($api_url, false, $context);

if ($response) {
    $data = json_decode($response, true);
    
    echo "<p>Raw Response:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    if ($data) {
        echo "<p>Parsed Response:</p>";
        echo "<pre>" . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";
        
        if ($data['success']) {
            echo "<p style='color: green;'>✅ API Success</p>";
            $total = $data['data']['pagination']['total'] ?? 0;
            $count = count($data['data']['reject_requests'] ?? []);
            echo "<p>Total: $total, Showing: $count</p>";
        } else {
            echo "<p style='color: red;'>❌ API Error: " . $data['message'] . "</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ Failed to parse JSON</p>";
    }
} else {
    echo "<p style='color: red;'>❌ Failed to call API</p>";
}

// Instructions
echo "<h3>How to Test Properly:</h3>";
echo "<ol>";
echo "<li>Open the main application in browser</li>";
echo "<li>Login as admin or staff user</li>";
echo "<li>Open browser developer tools (F12)</li>";
echo "<li>Go to Network tab</li>";
echo "<li>Navigate to 'Yêu cầu từ chối' page</li>";
echo "<li>Look for the API call to reject_requests.php</li>";
echo "<li>Check the response and request headers</li>";
echo "</ol>";

echo "<h3>Expected Behavior:</h3>";
echo "<ul>";
echo "<li>✅ When logged in as admin/staff: Should see reject requests</li>";
echo "<li>❌ When not logged in: Should get 'Unauthorized' error</li>";
echo "<li>❌ When logged in as regular user: Should get access denied error</li>";
echo "</ul>";

echo "<p><strong>The filter functionality should work correctly once you're properly authenticated as admin or staff.</strong></p>";
?>
