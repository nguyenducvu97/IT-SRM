<?php
echo "<h2>Test Attachment API</h2>";

$testFile = "req_69d751488ba4a3.13984562.jpg";

echo "<h3>Testing View Action:</h3>";
echo "<p>File: $testFile</p>";

// Test view action (should work without authentication)
$url = "http://localhost/it-service-request/api/attachment.php?file=" . urlencode($testFile) . "&action=view";
echo "<p>URL: $url</p>";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => "User-Agent: Test Client\r\n"
    ]
]);

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>Failed to get response</p>";
    
    // Try to get headers
    if (isset($http_response_header)) {
        echo "<p>Response headers:</p>";
        echo "<pre>";
        print_r($http_response_header);
        echo "</pre>";
    }
} else {
    echo "<p style='color: green;'>Success! Got response</p>";
    
    // Check if it's JSON (error) or image data
    $json_data = json_decode($response, true);
    if ($json_data) {
        echo "<p>JSON Response:</p>";
        echo "<pre>";
        print_r($json_data);
        echo "</pre>";
    } else {
        echo "<p>Image data received (size: " . strlen($response) . " bytes)</p>";
        
        // Check content type
        if (isset($http_response_header)) {
            foreach ($http_response_header as $header) {
                if (strpos($header, 'Content-Type:') === 0) {
                    echo "<p>Content-Type: $header</p>";
                    break;
                }
            }
        }
    }
}

echo "<h3>Testing Download Action:</h3>";
$url = "http://localhost/it-service-request/api/attachment.php?file=" . urlencode($testFile) . "&action=download";
echo "<p>URL: $url</p>";

$response = file_get_contents($url, false, $context);

if ($response === false) {
    echo "<p style='color: red;'>Failed to get response</p>";
    
    if (isset($http_response_header)) {
        echo "<p>Response headers:</p>";
        echo "<pre>";
        print_r($http_response_header);
        echo "</pre>";
    }
} else {
    $json_data = json_decode($response, true);
    if ($json_data) {
        echo "<p>JSON Response:</p>";
        echo "<pre>";
        print_r($json_data);
        echo "</pre>";
    } else {
        echo "<p style='color: green;'>Success! File data received</p>";
    }
}

echo "<h3>Expected Results:</h3>";
echo "<ul>";
echo "<li>View action: Should return image data without authentication</li>";
echo "<li>Download action: Should return file data or require authentication</li>";
echo "<li>No more 403 Forbidden errors</li>";
echo "</ul>";
?>
