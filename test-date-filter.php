<?php
// Test Date Filter Functionality
header("Content-Type: text/plain; charset=utf-8");

echo "=== TEST DATE FILTER FUNCTIONALITY ===\n\n";

// Test API endpoints
echo "1. Testing service_requests.php with date filter:\n";
$service_url = "http://localhost/it-service-request/api/service_requests.php?action=list&start_date=2024-01-01&end_date=2024-12-31&limit=3";
echo "URL: $service_url\n";

$context = stream_context_create([
    'http' => [
        'header' => "Cookie: PHPSESSID=test_session"
    ]
]);

$response = file_get_contents($service_url, false, $context);
if ($response) {
    echo "Response received: " . substr($response, 0, 200) . "...\n\n";
} else {
    echo "ERROR: No response received\n\n";
}

echo "2. Testing search_requests.php with date filter:\n";
$search_url = "http://localhost/it-service-request/api/search_requests.php?search=test&start_date=2024-01-01&end_date=2024-12-31&limit=3";
echo "URL: $search_url\n";

$response = file_get_contents($search_url, false, $context);
if ($response) {
    echo "Response received: " . substr($response, 0, 200) . "...\n\n";
} else {
    echo "ERROR: No response received\n\n";
}

echo "3. Checking HTML elements:\n";
$html_content = file_get_contents('http://localhost/it-service-request/index.html');
if ($html_content) {
    if (strpos($html_content, 'id="startDate"') !== false) {
        echo "✓ startDate input found\n";
    } else {
        echo "✗ startDate input NOT found\n";
    }
    
    if (strpos($html_content, 'id="endDate"') !== false) {
        echo "✓ endDate input found\n";
    } else {
        echo "✗ endDate input NOT found\n";
    }
    
    if (strpos($html_content, 'id="clearDateFilter"') !== false) {
        echo "✓ clearDateFilter button found\n";
    } else {
        echo "✗ clearDateFilter button NOT found\n";
    }
    
    if (strpos($html_content, 'date-filter-handler.js') !== false) {
        echo "✓ date-filter-handler.js script included\n";
    } else {
        echo "✗ date-filter-handler.js script NOT included\n";
    }
} else {
    echo "ERROR: Could not load HTML\n";
}

echo "\n=== TEST COMPLETE ===\n";
?>
