<?php
// Test API to check if attachments are returned
echo "<h2>🔍 Testing API Attachment Response</h2>";

// Test a specific request ID that might have attachments
$test_request_id = 24; // Change this to a request ID that has attachments

echo "<h3>🧪 Testing API for Request ID: $test_request_id</h3>";

// Call the API to get request details
$api_url = "http://localhost/it-service-request/api/service_requests.php?id=$test_request_id";

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => 'Content-Type: application/json'
    ]
]);

$response = file_get_contents($api_url, false, $context);

if ($response) {
    $data = json_decode($response, true);
    
    echo "<h4>📋 API Response:</h4>";
    echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; font-family: monospace; font-size: 12px; max-height: 400px; overflow-y: auto;'>";
    echo "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
    echo "</div>";
    
    if (isset($data['success']) && $data['success']) {
        $request = $data['data'];
        
        echo "<h4>📊 Request Details:</h4>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $request['id'] . "</li>";
        echo "<li><strong>Title:</strong> " . htmlspecialchars($request['title']) . "</li>";
        echo "<li><strong>Status:</strong> " . $request['status'] . "</li>";
        echo "</ul>";
        
        if (isset($request['attachments'])) {
            $attachments = $request['attachments'];
            
            echo "<h4>📎 Attachments Found: " . count($attachments) . "</h4>";
            
            if (!empty($attachments)) {
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
                echo "<tr><th>ID</th><th>Filename</th><th>Original Name</th><th>Size</th><th>MIME Type</th><th>File Path</th><th>Image?</th></tr>";
                
                foreach ($attachments as $attachment) {
                    $is_image = strpos($attachment['mime_type'], 'image/') === 0;
                    $file_path = "uploads/requests/" . $attachment['filename'];
                    $file_exists = file_exists($file_path);
                    
                    echo "<tr>";
                    echo "<td>" . $attachment['id'] . "</td>";
                    echo "<td>" . htmlspecialchars($attachment['filename']) . "</td>";
                    echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
                    echo "<td>" . $attachment['file_size'] . " bytes</td>";
                    echo "<td>" . $attachment['mime_type'] . "</td>";
                    echo "<td>" . $file_path . "</td>";
                    echo "<td style='color: " . ($is_image ? 'green' : 'blue') . ";'>" . ($is_image ? '✅ Yes' : '❌ No') . "</td>";
                    echo "</tr>";
                    
                    echo "<tr>";
                    echo "<td colspan='7'>";
                    echo "<strong>File exists:</strong> " . ($file_exists ? '✅ Yes' : '❌ No');
                    
                    if ($is_image && $file_exists) {
                        echo " | <strong>Preview:</strong><br>";
                        echo "<img src='$file_path' style='max-width: 200px; max-height: 100px; border: 1px solid #ccc; border-radius: 4px;' alt='Preview'>";
                    }
                    echo "</td>";
                    echo "</tr>";
                }
                
                echo "</table>";
                
                echo "<h4>🔍 File System Check:</h4>";
                $upload_dir = "uploads/requests/";
                if (is_dir($upload_dir)) {
                    echo "<p>✅ Upload directory exists: $upload_dir</p>";
                    
                    $files = scandir($upload_dir);
                    $image_files = array_filter($files, function($file) {
                        return in_array(pathinfo($file, PATHINFO_EXTENSION), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                    });
                    
                    echo "<p>📁 Found " . count($image_files) . " image files in upload directory:</p>";
                    echo "<ul>";
                    foreach ($image_files as $file) {
                        $file_path = $upload_dir . $file;
                        $file_size = filesize($file_path);
                        echo "<li>$file (" . number_format($file_size) . " bytes)</li>";
                    }
                    echo "</ul>";
                } else {
                    echo "<p style='color: red;'>❌ Upload directory not found: $upload_dir</p>";
                }
                
            } else {
                echo "<p style='color: orange;'>⚠️ No attachments found for this request</p>";
            }
        } else {
            echo "<p style='color: red;'>❌ No attachments key in API response</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ API Error: " . ($data['message'] ?? 'Unknown error') . "</p>";
    }
    
} else {
    echo "<p style='color: red;'>❌ Failed to call API</p>";
}

echo "<hr>";

echo "<h3>🔧 Troubleshooting Steps:</h3>";
echo "<ol>";
echo "<li><strong>Check if API returns attachments:</strong> Look for 'attachments' key in response</li>";
echo "<li><strong>Verify file paths:</strong> Check if uploads/requests/ directory exists</li>";
echo "<li><strong>Check file existence:</strong> Verify attachment files actually exist on server</li>";
echo "<li><strong>Test different request IDs:</strong> Try requests that should have attachments</li>";
echo "<li><strong>Check database:</strong> Verify attachments table has records</li>";
echo "</ol>";

echo "<hr>";

echo "<h3>💡 Common Issues:</h3>";
echo "<ul>";
echo "<li>🔍 <strong>API not returning attachments:</strong> Check service_requests.php lines 196-210</li>";
echo "<li>📁 <strong>Upload directory missing:</strong> Create uploads/requests/ directory</li>";
echo "<li>🗄️ <strong>Database issue:</strong> Check attachments table exists</li>";
echo "<li>🔒 <strong>Permission issue:</strong> Check web server can read upload directory</li>";
echo "</ul>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
