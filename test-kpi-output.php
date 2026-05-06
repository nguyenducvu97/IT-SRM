<?php
// Include required files
require_once 'config/session.php';
require_once 'config/database.php';

// Start session
startSession();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

// Simulate POST data
$_POST['start_date'] = '2026-04-01';
$_POST['end_date'] = '2026-05-30';
$_SERVER['REQUEST_METHOD'] = 'POST';

// Capture output
ob_start();

// Include and execute KPI Export API
include 'api/kpi_export.php';

$output = ob_get_clean();

// Display result
header('Content-Type: text/plain');
echo "KPI Export API Output:\n";
echo "====================\n";
echo $output;
echo "\n\nJSON Check:\n";
echo "============\n";

// Check if output is valid JSON
$json_data = json_decode($output, true);
if ($json_data !== null) {
    echo "✅ Valid JSON\n";
    echo "Success: " . ($json_data['success'] ? 'Yes' : 'No') . "\n";
    if (isset($json_data['data'])) {
        echo "Staff count: " . count($json_data['data']) . "\n";
    }
} else {
    echo "❌ Invalid JSON\n";
    echo "Error: " . json_last_error_msg() . "\n";
}
?>
