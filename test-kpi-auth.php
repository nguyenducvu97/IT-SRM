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

echo "<h2>KPI Export Auth Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Logged in: " . (isLoggedIn() ? "Yes" : "No") . "</p>";
echo "<p>User role: " . getCurrentUserRole() . "</p>";

// Test authentication check
if (isLoggedIn() && (getCurrentUserRole() === 'admin' || getCurrentUserRole() === 'staff')) {
    echo "<p style='color: green;'>✅ Authentication passed</p>";
} else {
    echo "<p style='color: red;'>❌ Authentication failed</p>";
}

// Simulate POST data
$_POST['start_date'] = '2026-04-01';
$_POST['end_date'] = '2026-05-30';
$_SERVER['REQUEST_METHOD'] = 'POST';

echo "<h3>Testing KPI Export API directly:</h3>";

// Capture output
ob_start();
try {
    include 'api/kpi_export.php';
    $output = ob_get_clean();
    echo "<p>Output length: " . strlen($output) . " characters</p>";
    if (!empty($output)) {
        echo "<h4>Output:</h4>";
        echo "<pre>" . htmlspecialchars($output) . "</pre>";
        
        // Check JSON
        $json_data = json_decode($output, true);
        if ($json_data !== null) {
            echo "<p style='color: green;'>✅ Valid JSON</p>";
        } else {
            echo "<p style='color: red;'>❌ Invalid JSON: " . json_last_error_msg() . "</p>";
        }
    } else {
        echo "<p style='color: orange;'>⚠️ No output</p>";
    }
} catch (Exception $e) {
    ob_end_clean();
    echo "<p style='color: red;'>❌ Exception: " . $e->getMessage() . "</p>";
}
?>
