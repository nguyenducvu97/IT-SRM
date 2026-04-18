<?php
// Test API response for request support count
session_start();

// Mock login for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Test Admin';
}

echo "<h1>API Response Test</h1>";

// Test API call
$api_url = 'http://localhost:8001/api/service_requests.php?action=list';

// Use curl to make API call
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $api_url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . '=' . session_id());
curl_setopt($ch, CURLOPT_HEADER, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<h2>API Response</h2>";
echo "<p>HTTP Status: <strong>$http_code</strong></p>";
echo "<h3>Raw Response:</h3>";
echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: auto;'>";
echo htmlspecialchars($response);
echo "</pre>";

// Parse JSON
$data = json_decode($response, true);

if ($data) {
    echo "<h3>Parsed JSON:</h3>";
    echo "<pre style='background: #e8f5e8; padding: 10px; max-height: 200px; overflow-y: auto;'>";
    echo json_encode($data, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    if (isset($data['data']['status_counts'])) {
        echo "<h3>Status Counts:</h3>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>Status</th><th>Count</th></tr>";
        foreach ($data['data']['status_counts'] as $status => $count) {
            echo "<tr><td>$status</td><td><strong>$count</strong></td></tr>";
        }
        echo "</table>";
        
        $request_support_count = $data['data']['status_counts']['request_support'] ?? 0;
        echo "<h3>Request Support Count: <strong style='color: blue;'>$request_support_count</strong></h3>";
    }
} else {
    echo "<p style='color: red;'>Failed to parse JSON response</p>";
}

echo "<p><a href='index.html'>Back to Application</a></p>";
?>
