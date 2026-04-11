<?php
/**
 * Debug real-time image detection issue
 */

require_once 'config/database.php';

echo "<h1>Debug Real-time Image Detection</h1>\n";

try {
    $db = getDatabaseConnection();
    
    echo "<h2>Check Current JavaScript Logic</h2>\n";
    
    // Get the most recent attachment with .png extension
    $query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 
            FROM attachments 
            WHERE original_name LIKE '%.png%' 
            ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attachment) {
        echo "<h3>Latest PNG Attachment:</h3>\n";
        echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
        echo "<tr><th>ID</th><th>Filename</th><th>Original Name</th><th>MIME Type</th><th>Size</th><th>Uploaded</th></tr>\n";
        echo "<tr>\n";
        echo "<td>{$attachment['id']}</td>\n";
        echo "<td>{$attachment['filename']}</td>\n";
        echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>\n";
        echo "<td>" . ($attachment['mime_type'] ?: 'NULL') . "</td>\n";
        echo "<td>" . number_format($attachment['file_size']) . " bytes</td>\n";
        echo "<td>{$attachment['uploaded_at']}</td>\n";
        echo "</tr>\n";
        echo "</table>\n";
        
        // Test JavaScript logic simulation
        echo "<h3>JavaScript Logic Simulation:</h3>\n";
        
        $fileExt = pathinfo($attachment['filename'], PATHINFO_EXTENSION);
        echo "<p><strong>File Extension:</strong> $fileExt</p>\n";
        
        $isImageByMime = $attachment['mime_type'] && strpos($attachment['mime_type'], 'image/') === 0;
        echo "<p><strong>Is Image (MIME):</strong> " . ($isImageByMime ? 'YES' : 'NO') . "</p>\n";
        
        $isImageByExt = in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
        echo "<p><strong>Is Image (Extension):</strong> " . ($isImageByExt ? 'YES' : 'NO') . "</p>\n";
        
        $isImageCombined = ($attachment['mime_type'] && strpos($attachment['mime_type'], 'image/') === 0) || 
                         in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
        echo "<p><strong>Is Image (Combined):</strong> " . ($isImageCombined ? 'YES' : 'NO') . "</p>\n";
        
        // Test icon selection
        $iconType = $isImageCombined ? 'image' : 'file';
        echo "<p><strong>Icon Class:</strong> fa-$iconType</p>\n";
        
        // Test actual API response
        echo "<h3>API Response Test:</h3>\n";
        
        // Get service request with this attachment
        $srQuery = "SELECT id FROM service_requests ORDER BY id DESC LIMIT 1";
        $srStmt = $db->prepare($srQuery);
        $srStmt->execute();
        $serviceRequest = $srStmt->fetch(PDO::FETCH_ASSOC);
        
        if ($serviceRequest) {
            $apiUrl = 'http://localhost/it-service-request/api/service_requests.php?action=get&id=' . $serviceRequest['id'];
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $apiUrl);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'Cookie: session_id=' . session_id()
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            echo "<p><strong>API HTTP Status:</strong> $httpCode</p>\n";
            
            if ($response && $httpCode === 200) {
                $data = json_decode($response, true);
                if ($data && isset($data['success']) {
                    echo "<h4>API Response Data:</h4>\n";
                    
                    if (isset($data['data']['attachments'])) {
                        foreach ($data['data']['attachments'] as $att) {
                            if (strpos($att['original_name'], 'ERD_Diagram_VI.png') !== false) {
                                echo "<pre>" . json_encode($att, JSON_PRETTY_PRINT) . "</pre>\n";
                                
                                // Simulate JavaScript logic
                                $attFileExt = pathinfo($att['filename'], PATHINFO_EXTENSION);
                                $attIsImage = ($att['mime_type'] && strpos($att['mime_type'], 'image/') === 0) || 
                                             in_array(strtolower($attFileExt), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
                                $attIcon = $attIsImage ? 'image' : 'file';
                                
                                echo "<p><strong>JavaScript isImage:</strong> " . ($attIsImage ? 'YES' : 'NO') . "</p>\n";
                                echo "<p><strong>JavaScript Icon:</strong> fa-$attIcon</p>\n";
                                break 2;
                            }
                        }
                    }
                }
            }
        }
    } else {
        echo "<p>No PNG attachment found</p>\n";
    }
    
    echo "<h2>Browser Cache Check</h2>\n";
    echo "<p><strong>Note:</strong> If you still see the old icon, it might be browser cache. Try:</p>\n";
    echo "<ol>\n";
    echo "<li>Hard refresh: Ctrl+F5 (or Cmd+R on Mac)</li>\n";
    echo "<li>Clear cache: Ctrl+Shift+Delete (or Cmd+Shift+Delete on Mac)</li>\n";
    echo "<li>Developer tools: F12 > Network tab > Disable cache</li>\n";
    echo "<li>Incognito/Private browsing</li>\n";
    echo "</ol>\n";
    
    echo "<h2>JavaScript Console Test</h2>\n";
    echo "<p>Open browser console (F12) and run:</p>\n";
    echo "<pre style='background: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
    echo "// Test the actual JavaScript logic\n";
    echo "const attachment = {\n";
    echo "    filename: 'req_69d9fba59cedd2.56618149.png',\n";
    echo "    original_name: 'ERD_Diagram_VI.png',\n";
    echo "    mime_type: 'image/png'\n";
    echo "};\n";
    echo "\n";
    echo "const fileExt = attachment.filename.split('.').pop().toLowerCase();\n";
    echo "const isImage = (attachment.mime_type && attachment.mime_type.startsWith('image/')) || \n";
    echo "                ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg'].includes(fileExt);\n";
    echo "const iconType = isImage ? 'image' : 'file';\n";
    echo "console.log('File Extension:', fileExt);\n";
    echo "console.log('Is Image:', isImage);\n";
    echo "console.log('Icon Type:', iconType);\n";
    echo "</pre>\n";
    
    echo "<h2>Force Refresh Test</h2>\n";
    echo "<p>Click this button to force refresh the page:</p>\n";
    echo "<button onclick='location.reload(true)' style='background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer;'>Force Refresh Page</button>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

?>
