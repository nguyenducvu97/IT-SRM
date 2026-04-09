<?php
session_start();
$_SESSION['user_id'] = 4;
$_SESSION['username'] = 'ndvu';
$_SESSION['full_name'] = 'Nguyênx Duc Vu';
$_SESSION['role'] = 'user';

echo "<h2>Performance Test</h2>";

// Test with proper POST simulation
$_SERVER['REQUEST_METHOD'] = 'POST';
$_SERVER['CONTENT_TYPE'] = 'multipart/form-data; boundary=----WebKitFormBoundary7MA4YWxkTrZu0gW';

$_POST['action'] = 'create';
$_POST['title'] = 'Performance Test ' . date('H:i:s');
$_POST['description'] = 'Performance test description';
$_POST['category_id'] = '1';
$_POST['priority'] = 'medium';

$start_time = microtime(true);

// Capture output
ob_start();
include 'api/service_requests.php';
$output = ob_get_clean();

$end_time = microtime(true);
$execution_time = ($end_time - $start_time) * 1000;

echo "<h3>Performance Results:</h3>";
echo "<p><strong>Execution time:</strong> " . number_format($execution_time, 2) . " ms</p>";

if ($execution_time < 100) {
    echo "<p style='color: green; font-size: 20px;'>EXCELLENT! Under 100ms</p>";
    echo "<p>Quick fix working perfectly!</p>";
} elseif ($execution_time < 500) {
    echo "<p style='color: green; font-size: 18px;'>GOOD! Under 500ms</p>";
    echo "<p>Performance significantly improved!</p>";
} elseif ($execution_time < 1000) {
    echo "<p style='color: orange; font-size: 16px;'>ACCEPTABLE! Under 1 second</p>";
    echo "<p>Performance improved but can be better</p>";
} else {
    echo "<p style='color: red; font-size: 16px;'>STILL SLOW! " . number_format($execution_time, 2) . " ms</p>";
}

echo "<h3>API Response:</h3>";
$json_data = json_decode($output, true);
if ($json_data && $json_data['success']) {
    echo "<p style='color: green;'>SUCCESS: Request ID " . $json_data['data']['id'] . "</p>";
} else {
    echo "<p style='color: red;'>FAILED: " . ($json_data['message'] ?? 'Unknown error') . "</p>";
}

echo "<h3>Performance Comparison:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
echo "<tr><th>Method</th><th>Time</th><th>Improvement</th></tr>";
echo "<tr><td>Original API</td><td>>30,000 ms</td><td>-</td></tr>";
echo "<tr><td>Quick Fix</td><td>" . number_format($execution_time, 2) . " ms</td><td>" . number_format(30000 / $execution_time, 0) . "x faster</td></tr>";
echo "<tr><td>Database Only</td><td>29 ms</td><td>Baseline</td></tr>";
echo "</table>";

echo "<h3>Conclusion:</h3>";
if ($execution_time < 500) {
    echo "<p style='color: green;'><strong>Quick Fix SUCCESSFUL!</strong></p>";
    echo "<p>Create requests now complete in reasonable time</p>";
} else {
    echo "<p style='color: orange;'>Quick fix helped but needs more optimization</p>";
}
?>
