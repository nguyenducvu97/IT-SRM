<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

// Test KPI Export API
echo "<h2>KPI Export API Test</h2>";

// Make API call
$url = 'http://localhost/it-service-request/api/kpi_export.php';
$data = [
    'start_date' => '2026-04-01',
    'end_date' => '2026-05-30'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p>HTTP Status: $http_code</p>";
echo "<p>API Response:</p>";
echo "<pre>" . htmlspecialchars($response) . "</pre>";

$result = json_decode($response, true);
if ($result && isset($result['success']) && $result['success']) {
    echo "<p style='color: green;'>✅ KPI Export API working!</p>";
    if (isset($result['data'])) {
        echo "<p>Found " . count($result['data']) . " staff members</p>";
    }
} else {
    echo "<p style='color: red;'>❌ KPI Export API failed</p>";
    if (isset($result['message'])) {
        echo "<p>Error: " . htmlspecialchars($result['message']) . "</p>";
    }
}
?>
