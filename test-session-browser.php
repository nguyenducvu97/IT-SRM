<?php
echo "<h2>Test Session in Browser Context</h2>";

// Start session
require_once __DIR__ . '/config/session.php';
startSession();

echo "<h3>Current Session Status</h3>";
echo "<p><strong>Session ID:</strong> " . session_id() . "</p>";
echo "<p><strong>Session Status:</strong> " . (session_status() === PHP_SESSION_ACTIVE ? "Active" : "Not Active") . "</p>";

echo "<h3>Session Data</h3>";
if (empty($_SESSION)) {
    echo "<p style='color: red;'>&#10027; No session data found</p>";
} else {
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>Key</th><th>Value</th></tr>";
    foreach ($_SESSION as $key => $value) {
        echo "<tr>";
        echo "<td>{$key}</td>";
        echo "<td>" . (is_array($value) ? json_encode($value) : htmlspecialchars($value)) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

echo "<h3>Login Status</h3>";
if (function_exists('isLoggedIn')) {
    $loggedIn = isLoggedIn();
    echo "<p><strong>Is Logged In:</strong> " . ($loggedIn ? "YES" : "NO") . "</p>";
    
    if ($loggedIn) {
        $user = getCurrentUser();
        echo "<p><strong>Current User:</strong></p>";
        echo "<pre>" . json_encode($user, JSON_PRETTY_PRINT) . "</pre>";
    }
} else {
    echo "<p style='color: red;'>&#10027; isLoggedIn() function not found</p>";
}

echo "<h3>Test API Call with Current Session</h3>";

// Get current session cookie
$sessionName = session_name();
$sessionId = session_id();

echo "<p><strong>Session Name:</strong> {$sessionName}</p>";
echo "<p><strong>Session ID:</strong> {$sessionId}</p>";

// Test API call with current session
$testData = [
    'action' => 'accept_request',
    'request_id' => 72
];

$ch = curl_init('http://localhost/it-service-request/api/service_requests.php');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PUT');
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Cookie: ' . $sessionName . '=' . $sessionId
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "<p><strong>API Response:</strong></p>";
echo "<p>HTTP Status: {$httpCode}</p>";
if ($error) {
    echo "<p>CURL Error: {$error}</p>";
}
echo "<pre>" . htmlspecialchars($response) . "</pre>";

echo "<h3>JavaScript Browser Test</h3>";
echo "<p>To test in browser context:</p>";
echo "<ol>";
echo "<li>Open browser and login as staff</li>";
echo "<li>Navigate to a request detail page</li>";
echo "<li>Open browser console (F12)</li>";
echo "<li>Run this JavaScript code:</li>";
echo "</ol>";

echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
echo "// Check session in JavaScript
fetch('test-session-browser.php')
  .then(response => response.text())
  .then(html => {
    console.log('Session debug output:');
    console.log(html);
  });

// Test API call directly
fetch('api/service_requests.php', {
  method: 'PUT',
  credentials: 'include',
  headers: {
    'Content-Type': 'application/json'
  },
  body: JSON.stringify({
    action: 'accept_request',
    request_id: 72
  })
})
.then(response => response.json())
.then(data => {
  console.log('API Response:', data);
})
.catch(error => {
  console.error('API Error:', error);
});
</pre>";

echo "<h3>Troubleshooting Steps</h3>";
echo "<ul>";
echo "<li>If session data is empty, user is not logged in properly</li>";
echo "<li>If API returns 'Unauthorized access', session cookie is not being sent</li>";
echo "<li>If browser test works but button click doesn't, check JavaScript event handling</li>";
echo "<li>Check browser developer tools > Application > Cookies for session cookie</li>";
echo "<li>Make sure domain and path settings are correct for cookies</li>";
echo "</ul>";
?>
