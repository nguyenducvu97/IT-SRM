<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/session.php';
require_once 'config/database.php';

// Start session
startSession();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

// Simulate GET request
$_GET['action'] = 'list';
$_GET['limit'] = '10';
$_SERVER['REQUEST_METHOD'] = 'GET';

echo "<h2>Service Requests API Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Logged in: " . (isLoggedIn() ? "Yes" : "No") . "</p>";
echo "<p>User role: " . getCurrentUserRole() . "</p>";

// Capture output
ob_start();
try {
    include 'api/service_requests.php';
    $output = ob_get_clean();
    echo "<h3>Output:</h3>";
    echo "<pre>" . htmlspecialchars($output) . "</pre>";
    
    // Check JSON
    $json_data = json_decode($output, true);
    if ($json_data !== null) {
        echo "<p style='color: green;'>✅ Valid JSON</p>";
    } else {
        echo "<p style='color: red;'>❌ Invalid JSON: " . json_last_error_msg() . "</p>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}
?>
