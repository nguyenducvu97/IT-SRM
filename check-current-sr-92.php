<?php
/**
 * Check current state of service request #92 attachments
 */

require_once 'config/database.php';

echo "<h1>Current State of Service Request #92 Attachments</h1>\n";

try {
    $db = getDatabaseConnection();
    
    // Check current attachments in database
    echo "<h2>Current Database Records</h2>\n";
    
    $query = "SELECT * FROM attachments WHERE service_request_id = 92 ORDER BY id";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($attachments) . " attachment records for SR #92</p>\n";
    
    if (!empty($attachments)) {
        echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
        echo "<tr><th>ID</th><th>Service Request ID</th><th>Filename</th><th>Original Name</th><th>Size</th><th>MIME Type</th><th>Uploaded By</th><th>Uploaded At</th><th>File Exists</th></tr>\n";
        
        foreach ($attachments as $att) {
            $filename = $att['filename'];
            
            // Check if file exists
            $paths = [
                __DIR__ . '/uploads/requests/' . $filename,
                __DIR__ . '/uploads/attachments/' . $filename,
                __DIR__ . '/uploads/reject_requests/' . $filename,
                __DIR__ . '/uploads/completed/' . $filename,
            ];
            
            $found = false;
            $foundPath = '';
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $found = true;
                    $foundPath = $path;
                    break;
                }
            }
            
            echo "<tr>\n";
            echo "<td>{$att['id']}</td>\n";
            echo "<td>{$att['service_request_id']}</td>\n";
            echo "<td>$filename</td>\n";
            echo "<td>" . htmlspecialchars($att['original_name']) . "</td>\n";
            echo "<td>" . number_format($att['file_size']) . " bytes</td>\n";
            echo "<td>{$att['mime_type']}</td>\n";
            echo "<td>{$att['uploaded_by']}</td>\n";
            echo "<td>{$att['uploaded_at']}</td>\n";
            echo "<td>" . ($found ? '<span style="color: green;">YES</span><br><small>' . str_replace(__DIR__, '', $foundPath) . '</small>' : '<span style="color: red;">NO</span>') . "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
        
        // Check if files exist and can be accessed
        echo "<h2>File Access Test</h2>\n";
        
        foreach ($attachments as $att) {
            $filename = $att['filename'];
            echo "<h3>Testing file: $filename</h3>\n";
            
            $found = false;
            $foundPath = '';
            foreach ($paths as $path) {
                if (file_exists($path)) {
                    $found = true;
                    $foundPath = $path;
                    break;
                }
            }
            
            if ($found) {
                echo "<p style='color: green;'>File exists at: " . str_replace(__DIR__, '', $foundPath) . "</p>\n";
                
                // Test API access
                $apiUrl = 'http://localhost/it-service-request/api/attachment.php?file=' . urlencode($filename) . '&action=view';
                echo "<p>API URL: <a href='$apiUrl' target='_blank'>$apiUrl</a></p>\n";
                
                // Test with curl if available
                if (function_exists('curl_init')) {
                    $ch = curl_init();
                    curl_setopt($ch, CURLOPT_URL, $apiUrl);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_COOKIE, $_SERVER['HTTP_COOKIE'] ?? '');
                    curl_setopt($ch, CURLOPT_HEADER, true);
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    echo "<p><strong>HTTP Status:</strong> $httpCode</p>\n";
                    
                    if ($httpCode == 200) {
                        echo "<p style='color: green;'>API access: SUCCESS</p>\n";
                    } else {
                        echo "<p style='color: red;'>API access: FAILED</p>\n";
                        echo "<pre>" . htmlspecialchars(substr($response, 0, 500)) . "...</pre>\n";
                    }
                }
            } else {
                echo "<p style='color: red;'>File NOT FOUND in any location</p>\n";
                
                // Look for similar files
                echo "<h4>Looking for similar files...</h4>\n";
                $baseFilename = pathinfo($filename, PATHINFO_FILENAME);
                $extension = pathinfo($filename, PATHINFO_EXTENSION);
                
                foreach ($paths as $path) {
                    $dir = dirname($path);
                    if (file_exists($dir)) {
                        $files = scandir($dir);
                        $similarFiles = [];
                        
                        foreach ($files as $file) {
                            if ($file !== '.' && $file !== '..') {
                                if (strpos($file, $baseFilename) !== false || 
                                    strpos($file, $extension) !== false) {
                                    $similarFiles[] = $file;
                                }
                            }
                        }
                        
                        if (!empty($similarFiles)) {
                            echo "<p>Similar files in " . str_replace(__DIR__, '', $dir) . ":</p>\n";
                            echo "<ul>\n";
                            foreach ($similarFiles as $similarFile) {
                                $filePath = $dir . '/' . $similarFile;
                                $exists = file_exists($filePath);
                                echo "<li>$similarFile - " . ($exists ? '<span style="color: green;">EXISTS</span>' : '<span style="color: red;">MISSING</span>') . "</li>\n";
                            }
                            echo "</ul>\n";
                        }
                    }
                }
            }
            
            echo "<hr>\n";
        }
        
    } else {
        echo "<p style='color: orange;'>No attachment records found for SR #92 in database</p>\n";
    }
    
    // Check if there are any files that might be the missing ones
    echo "<h2>Looking for Missing Files</h2>\n";
    
    $targetFiles = [
        'req_69d9cae8b1e458.44955452.pdf',
        'req_69d9cae8b28f80.18853471.png'
    ];
    
    foreach ($targetFiles as $targetFile) {
        echo "<h3>Looking for: $targetFile</h3>\n";
        
        $found = false;
        $foundPath = '';
        foreach ($paths as $path) {
            $filePath = dirname($path) . '/' . $targetFile;
            if (file_exists($filePath)) {
                $found = true;
                $foundPath = $filePath;
                break;
            }
        }
        
        if ($found) {
            echo "<p style='color: green;'>Found at: " . str_replace(__DIR__, '', $foundPath) . "</p>\n";
            echo "<p><a href='?restore=92&file=" . urlencode($targetFile) . "&path=" . urlencode(str_replace(__DIR__, '', $foundPath)) . "'>Restore Record</a></p>\n";
        } else {
            echo "<p style='color: red;'>File not found</p>\n";
        }
    }
    
    // Handle restoration
    if (isset($_GET['restore']) && isset($_GET['file']) && isset($_GET['path'])) {
        $serviceRequestId = (int)$_GET['restore'];
        $filename = $_GET['file'];
        $filePath = $_GET['path'];
        
        echo "<h2>Restore Attachment Record</h2>\n";
        
        $fullPath = __DIR__ . '/' . $filePath;
        if (!file_exists($fullPath)) {
            echo "<p style='color: red;'>File not found: $fullPath</p>\n";
            exit;
        }
        
        $fileSize = filesize($fullPath);
        $mimeType = mime_content_type($fullPath);
        $originalName = pathinfo($filename, PATHINFO_FILENAME);
        
        echo "<p><strong>File:</strong> $filename</p>\n";
        echo "<p><strong>Path:</strong> $filePath</p>\n";
        echo "<p><strong>Size:</strong> " . number_format($fileSize) . " bytes</p>\n";
        echo "<p><strong>Type:</strong> $mimeType</p>\n";
        echo "<p><strong>Service Request:</strong> #$serviceRequestId</p>\n";
        
        if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
            try {
                $insertQuery = "INSERT INTO attachments (service_request_id, original_name, filename, file_size, mime_type, uploaded_by, uploaded_at) VALUES (:service_request_id, :original_name, :filename, :file_size, :mime_type, :uploaded_by, NOW())";
                $insertStmt = $db->prepare($insertQuery);
                
                $insertStmt->bindValue(':service_request_id', $serviceRequestId, PDO::PARAM_INT);
                $insertStmt->bindValue(':original_name', $originalName);
                $insertStmt->bindValue(':filename', $filename);
                $insertStmt->bindValue(':file_size', $fileSize);
                $insertStmt->bindValue(':mime_type', $mimeType);
                $insertStmt->bindValue(':uploaded_by', 4); // User ID 4
                
                if ($insertStmt->execute()) {
                    echo "<p style='color: green; font-weight: bold;'>Successfully restored attachment record</p>\n";
                    echo "<p><a href='?'>Check Status</a></p>\n";
                } else {
                    echo "<p style='color: red;'>Failed to restore record</p>\n";
                }
            } catch (Exception $e) {
                echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
            }
        } else {
            echo "<p><strong>WARNING:</strong> This will recreate the attachment record for the file.</p>\n";
            echo "<p><a href='?restore=$serviceRequestId&file=" . urlencode($filename) . "&path=" . urlencode($filePath) . "&confirm=yes' style='background: blue; color: white; padding: 10px; text-decoration: none; border-radius: 4px;'>Confirm Restoration</a></p>\n";
            echo "<p><a href='?' style='background: gray; color: white; padding: 10px; text-decoration: none; border-radius: 4px;'>Cancel</a></p>\n";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
}

?>
