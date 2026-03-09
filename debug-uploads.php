<?php
// Debug script for file uploads access
header('Content-Type: text/plain; charset=utf-8');

echo "=== UPLOADS DEBUG ===\n\n";

$uploadsDir = __DIR__ . '/uploads/requests';
$requestedFile = '69ae94fbe129a_SRS_IT_Service_Request_Management%20(2).docx';

echo "1. Uploads directory: " . $uploadsDir . "\n";
echo "2. Requested file: " . $requestedFile . "\n\n";

// Check if uploads directory exists
if (is_dir($uploadsDir)) {
    echo "✅ Uploads directory exists\n";
    
    // Check permissions
    if (is_readable($uploadsDir)) {
        echo "✅ Uploads directory is readable\n";
    } else {
        echo "❌ Uploads directory is NOT readable\n";
    }
    
    // List all files in uploads directory
    echo "\n3. Files in uploads directory:\n";
    $files = scandir($uploadsDir);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $fullPath = $uploadsDir . '/' . $file;
            $size = filesize($fullPath);
            $readable = is_readable($fullPath) ? 'YES' : 'NO';
            echo "   - $file (Size: $size bytes, Readable: $readable)\n";
        }
    }
    
    // Check for the specific file (with and without URL encoding)
    echo "\n4. Looking for requested file:\n";
    
    // Try exact match
    if (file_exists($uploadsDir . '/' . $requestedFile)) {
        echo "✅ Found exact match: $requestedFile\n";
    } else {
        echo "❌ Exact match not found: $requestedFile\n";
        
        // Try URL decoded version
        $decodedFile = urldecode($requestedFile);
        if (file_exists($uploadsDir . '/' . $decodedFile)) {
            echo "✅ Found decoded match: $decodedFile\n";
        } else {
            echo "❌ Decoded match not found: $decodedFile\n";
        }
        
        // Try similar files
        echo "\n5. Searching for similar files:\n";
        $allFiles = array_diff(scandir($uploadsDir), ['.', '..']);
        foreach ($allFiles as $file) {
            if (stripos($file, 'SRS_IT_Service_Request_Management') !== false) {
                echo "   🎯 Similar file found: $file\n";
            }
            if (stripos($file, 'docx') !== false) {
                echo "   📄 DOCX file: $file\n";
            }
        }
    }
    
} else {
    echo "❌ Uploads directory does NOT exist\n";
    
    // Try to create it
    if (mkdir($uploadsDir, 0755, true)) {
        echo "✅ Created uploads directory\n";
    } else {
        echo "❌ Failed to create uploads directory\n";
    }
}

// Check .htaccess file
echo "\n6. Checking .htaccess file:\n";
$htaccessFile = __DIR__ . '/uploads/.htaccess';
if (file_exists($htaccessFile)) {
    echo "✅ .htaccess file exists\n";
    echo "Contents:\n" . file_get_contents($htaccessFile) . "\n";
} else {
    echo "❌ .htaccess file does not exist\n";
}

// Test web access URL
echo "\n7. Testing web access URLs:\n";
$baseUrl = 'http://localhost/it-service-request/uploads/requests/';
echo "Base URL: $baseUrl\n";

foreach ($files as $file) {
    if ($file !== '.' && $file !== '..') {
        $url = $baseUrl . urlencode($file);
        echo "   - $url\n";
    }
}

echo "\n=== DEBUG COMPLETE ===\n";
?>
