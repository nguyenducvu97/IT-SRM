<?php
/**
 * Simple test for image detection
 */

require_once 'config/database.php';

echo "<h1>Image Detection Test</h1>\n";

try {
    $db = getDatabaseConnection();
    
    // Get latest PNG attachment
    $query = "SELECT id, filename, original_name, mime_type FROM attachments WHERE original_name LIKE '%.png%' ORDER BY id DESC LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attachment) {
        echo "<h3>Test Data:</h3>\n";
        echo "<p>Original Name: " . htmlspecialchars($attachment['original_name']) . "</p>\n";
        echo "<p>MIME Type: " . ($attachment['mime_type'] ?: 'NULL') . "</p>\n";
        echo "<p>File Extension: " . pathinfo($attachment['filename'], PATHINFO_EXTENSION) . "</p>\n";
        
        // Test the logic
        $fileExt = pathinfo($attachment['filename'], PATHINFO_EXTENSION);
        $isImage = (isset($attachment['mime_type']) && strpos($attachment['mime_type'], 'image/') === 0) || 
                   in_array(strtolower($fileExt), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
        
        echo "<h3>Detection Result:</h3>\n";
        echo "<p>Is Image: " . ($isImage ? 'YES' : 'NO') . "</p>\n";
        echo "<p>Expected Icon: fa-" . ($isImage ? 'image' : 'file') . "</p>\n";
        
        // Test API
        echo "<h3>API Test:</h3>\n";
        $apiUrl = 'http://localhost/it-service-request/api/service_requests.php?action=list';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        echo "<p>API Status: $httpCode</p>\n";
        
        if ($response && $httpCode === 200) {
            $data = json_decode($response, true);
            if ($data && isset($data['data'])) {
                foreach ($data['data'] as $request) {
                    if (isset($request['attachments'])) {
                        foreach ($request['attachments'] as $att) {
                            if ($att['original_name'] === $attachment['original_name']) {
                                echo "<h4>API Attachment Data:</h4>\n";
                                echo "<pre>" . json_encode($att, JSON_PRETTY_PRINT) . "</pre>\n";
                                
                                // Test JavaScript logic
                                $attFileExt = pathinfo($att['filename'], PATHINFO_EXTENSION);
                                $attIsImage = (isset($att['mime_type']) && strpos($att['mime_type'], 'image/') === 0) || 
                                                 in_array(strtolower($attFileExt), ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp', 'svg']);
                                
                                echo "<p>JavaScript isImage: " . ($attIsImage ? 'YES' : 'NO') . "</p>\n";
                                echo "<p>JavaScript Icon: fa-" . ($attIsImage ? 'image' : 'file') . "</p>\n";
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
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

?>
