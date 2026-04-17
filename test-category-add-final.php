<?php
// Final test for category add functionality
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Final Category Add Test</h1>";

// Step 1: Test the API directly
echo "<h2>Step 1: Direct API Test</h2>";

// Start session and login as admin
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

echo "Session: Admin user logged in<br>";
echo "Session ID: " . session_id() . "<br>";

// Test data
$test_data = [
    'name' => 'Final Test ' . date('H:i:s'),
    'description' => 'Final test category'
];

echo "Test Data: <pre>" . json_encode($test_data, JSON_PRETTY_PRINT) . "</pre>";

// Use cURL to test POST request
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, 'http://localhost/it-service-request/api/categories.php');
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($test_data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, true);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
$headers = substr($response, 0, $header_size);
$body = substr($response, $header_size);

curl_close($ch);

echo "HTTP Code: $http_code<br>";
echo "Response Headers:<br><pre>" . htmlspecialchars($headers) . "</pre>";
echo "Response Body:<br><pre>" . htmlspecialchars($body) . "</pre>";

$response_data = json_decode($body, true);
if ($response_data && $response_data['success']) {
    echo "<br><strong style='color: green;'>SUCCESS: Category created with ID: " . $response_data['data']['id'] . "</strong>";
} else {
    echo "<br><strong style='color: red;'>FAILED: " . ($response_data['message'] ?? 'Unknown error') . "</strong>";
}

// Step 2: Check server logs
echo "<h2>Step 2: Check Server Logs</h2>";
$log_file = 'logs/api_errors.log';
if (file_exists($log_file)) {
    echo "Recent log entries:<br>";
    $logs = file_get_contents($log_file);
    $recent_logs = substr($logs, -2000); // Last 2000 characters
    echo "<pre>" . htmlspecialchars($recent_logs) . "</pre>";
} else {
    echo "No log file found at: $log_file<br>";
}

// Step 3: Verify in database
echo "<h2>Step 3: Verify in Database</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $stmt = $db->prepare("SELECT * FROM categories WHERE name LIKE ? ORDER BY id DESC LIMIT 5");
    $stmt->execute(['Final Test%']);
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Recent test categories:<br>";
    foreach ($categories as $cat) {
        echo "ID: " . $cat['id'] . " - Name: " . $cat['name'] . " - Created: " . $cat['created_at'] . "<br>";
    }
} catch (Exception $e) {
    echo "Database verification failed: " . $e->getMessage() . "<br>";
}

// Step 4: JavaScript test instructions
echo "<h2>Step 4: JavaScript Test Instructions</h2>";
echo "<p>To test the JavaScript functionality:</p>";
echo "<ol>";
echo "<li>Open the main application: <a href='index.html'>index.html</a></li>";
echo "<li>Login as admin</li>";
echo "<li>Open browser console (F12)</li>";
echo "<li>Click on 'Thêm danh muc' button</li>";
echo "<li>Fill in the form and submit</li>";
echo "<li>Check console for debug logs</li>";
echo "<li>Check Network tab for the API request</li>";
echo "</ol>";

echo "<h3>Console Debug Commands:</h3>";
echo "<pre>";
echo "// Check current user
console.log('Current user:', app.currentUser);

// Test modal manually
app.showCategoryModal();

// Check functions
console.log('showCategoryModal:', typeof app.showCategoryModal);
console.log('handleCategorySubmit:', typeof app.handleCategorySubmit);
</pre>";

echo "<h2>Step 5: Common Issues and Solutions</h2>";
echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Issue</th><th>Cause</th><th>Solution</th></tr>";
echo "<tr><td>401 Unauthorized</td><td>Session not started</td><td>Fixed: Added session_start()</td></tr>";
echo "<tr><td>403 Forbidden</td><td>Not admin role</td><td>Check user role in session</td></tr>";
echo "<tr><td>400 Bad Request</td><td>Invalid JSON</td><td>Check JSON format</td></tr>";
echo "<tr><td>409 Conflict</td><td>Category exists</td><td>Use different name</td></tr>";
echo "<tr><td>500 Server Error</td><td>Database issue</td><td>Check logs and connection</td></tr>";
echo "</table>";

echo "<h2>Summary</h2>";
echo "<p>The category API has been fixed with:</p>";
echo "<ul>";
echo "<li>Added session_start() for all admin methods</li>";
echo "<li>Added comprehensive debug logging</li>";
echo "<li>Added JavaScript debug logging</li>";
echo "<li>Enhanced error handling</li>";
echo "</ul>";

echo "<p><strong>If the API test above succeeds, the issue is likely in the JavaScript frontend.</strong></p>";
?>
