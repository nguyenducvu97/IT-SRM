<?php
/**
 * Find the missing files for service request #92
 */

echo "<h1>Find Missing Files for Service Request #92</h1>\n";

// Target files from your database view
$targetFiles = [
    'req_69d9cae8b1e458.44955452.pdf',
    'req_69d9cae8b28f80.18853471.png'
];

echo "<h2>Target Files to Find</h2>\n";
echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
echo "<tr><th>Filename</th><th>Original Name</th><th>Status</th></tr>\n";

foreach ($targetFiles as $filename) {
    echo "<tr>\n";
    echo "<td>$filename</td>\n";
    echo "<td>" . (strpos($filename, '.pdf') !== false ? '(MMI) Marlin Magnet_751134800 Rev A.pdf' : 'IT SRM.png') . "</td>\n";
    echo "<td>Searching...</td>\n";
    echo "</tr>\n";
}
echo "</table>\n";

// Search in all upload directories
$uploadDirs = [
    'uploads/requests/',
    'uploads/attachments/',
    'uploads/reject_requests/',
    'uploads/completed/'
];

echo "<h2>Searching in Upload Directories</h2>\n";

$foundFiles = [];

foreach ($uploadDirs as $dir) {
    $fullDir = __DIR__ . '/' . $dir;
    if (file_exists($fullDir)) {
        echo "<h3>Directory: $dir</h3>\n";
        
        $files = scandir($fullDir);
        $matchingFiles = [];
        
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                foreach ($targetFiles as $targetFile) {
                    if ($file === $targetFile) {
                        $filePath = $fullDir . '/' . $file;
                        $matchingFiles[] = [
                            'filename' => $file,
                            'path' => $dir . $file,
                            'size' => filesize($filePath),
                            'modified' => date('Y-m-d H:i:s', filemtime($filePath))
                        ];
                        echo "<p style='color: green;'>Found: $file in $dir</p>\n";
                    }
                }
            }
        }
        
        if (empty($matchingFiles)) {
            echo "<p style='color: orange;'>No target files found in $dir</p>\n";
        }
        
        $foundFiles = array_merge($foundFiles, $matchingFiles);
    } else {
        echo "<p style='color: red;'>Directory not found: $dir</p>\n";
    }
}

// Search for similar files if exact match not found
if (empty($foundFiles)) {
    echo "<h2>Looking for Similar Files</h2>\n";
    
    foreach ($targetFiles as $targetFile) {
        echo "<h3>Similar to: $targetFile</h3>\n";
        
        $baseFilename = pathinfo($targetFile, PATHINFO_FILENAME);
        $extension = pathinfo($targetFile, PATHINFO_EXTENSION);
        
        foreach ($uploadDirs as $dir) {
            $fullDir = __DIR__ . '/' . $dir;
            if (file_exists($fullDir)) {
                $files = scandir($fullDir);
                $similarFiles = [];
                
                foreach ($files as $file) {
                    if ($file !== '.' && $file !== '..') {
                        $filePath = $fullDir . '/' . $file;
                        if (is_file($filePath)) {
                            // Check for similar patterns
                            if (strpos($file, $baseFilename) !== false || 
                                strpos($file, '69d9cae8b') !== false ||
                                (strpos($file, '.pdf') !== false && $extension === 'pdf') ||
                                (strpos($file, '.png') !== false && $extension === 'png')) {
                                
                                $fileTime = filemtime($filePath);
                                $similarFiles[] = [
                                    'filename' => $file,
                                    'path' => $dir . $file,
                                    'size' => filesize($filePath),
                                    'modified' => date('Y-m-d H:i:s', $fileTime),
                                    'similarity' => 'high'
                                ];
                            }
                        }
                    }
                }
                
                if (!empty($similarFiles)) {
                    echo "<h4>Similar files in $dir:</h4>\n";
                    echo "<table border='1' cellpadding='5' cellspacing='0'>\n";
                    echo "<tr><th>Filename</th><th>Size</th><th>Modified</th><th>Action</th></tr>\n";
                    
                    foreach ($similarFiles as $file) {
                        echo "<tr>\n";
                        echo "<td>{$file['filename']}</td>\n";
                        echo "<td>" . number_format($file['size']) . " bytes</td>\n";
                        echo "<td>{$file['modified']}</td>\n";
                        echo "<td><a href='?assign=92&file=" . urlencode($file['filename']) . "&path=" . urlencode($file['path']) . "'>Assign to SR #92</a></td>\n";
                        echo "</tr>\n";
                    }
                    echo "</table>\n";
                }
            }
        }
    }
}

// Handle file assignment
if (isset($_GET['assign']) && isset($_GET['file']) && isset($_GET['path'])) {
    require_once 'config/database.php';
    
    $serviceRequestId = (int)$_GET['assign'];
    $filename = $_GET['file'];
    $filePath = $_GET['path'];
    
    echo "<h2>Assign File to Service Request #$serviceRequestId</h2>\n";
    
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
    echo "<p><strong>Original Name:</strong> $originalName</p>\n";
    echo "<p><strong>Service Request:</strong> #$serviceRequestId</p>\n";
    
    if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
        try {
            $db = getDatabaseConnection();
            
            // Check if file already exists in database
            $checkQuery = "SELECT COUNT(*) as count FROM attachments WHERE filename = :filename";
            $checkStmt = $db->prepare($checkQuery);
            $checkStmt->bindValue(':filename', $filename);
            $checkStmt->execute();
            $checkResult = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($checkResult['count'] > 0) {
                echo "<p style='color: orange;'>File already exists in database. Updating service request assignment...</p>\n";
                
                // Update existing record
                $updateQuery = "UPDATE attachments SET service_request_id = :service_request_id WHERE filename = :filename";
                $updateStmt = $db->prepare($updateQuery);
                $updateStmt->bindValue(':service_request_id', $serviceRequestId, PDO::PARAM_INT);
                $updateStmt->bindValue(':filename', $filename);
                
                if ($updateStmt->execute()) {
                    echo "<p style='color: green; font-weight: bold;'>Successfully updated file assignment to service request #$serviceRequestId</p>\n";
                } else {
                    echo "<p style='color: red;'>Failed to update file assignment</p>\n";
                }
            } else {
                // Insert new record
                $insertQuery = "INSERT INTO attachments (service_request_id, original_name, filename, file_size, mime_type, uploaded_by, uploaded_at) VALUES (:service_request_id, :original_name, :filename, :file_size, :mime_type, :uploaded_by, NOW())";
                $insertStmt = $db->prepare($insertQuery);
                
                $insertStmt->bindValue(':service_request_id', $serviceRequestId, PDO::PARAM_INT);
                $insertStmt->bindValue(':original_name', $originalName);
                $insertStmt->bindValue(':filename', $filename);
                $insertStmt->bindValue(':file_size', $fileSize);
                $insertStmt->bindValue(':mime_type', $mimeType);
                $insertStmt->bindValue(':uploaded_by', 4); // User ID 4
                
                if ($insertStmt->execute()) {
                    echo "<p style='color: green; font-weight: bold;'>Successfully assigned file to service request #$serviceRequestId</p>\n";
                } else {
                    echo "<p style='color: red;'>Failed to assign file to database</p>\n";
                }
            }
            
            echo "<p><a href='check-current-sr-92.php'>Check SR #92</a></p>\n";
            
        } catch (Exception $e) {
            echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>\n";
        }
    } else {
        echo "<p><strong>WARNING:</strong> This will add the file to the database and make it appear as an attachment for service request #$serviceRequestId.</p>\n";
        echo "<p><a href='?assign=$serviceRequestId&file=" . urlencode($filename) . "&path=" . urlencode($filePath) . "&confirm=yes' style='background: blue; color: white; padding: 10px; text-decoration: none; border-radius: 4px;'>Confirm Assignment</a></p>\n";
        echo "<p><a href='?' style='background: gray; color: white; padding: 10px; text-decoration: none; border-radius: 4px;'>Cancel</a></p>\n";
    }
}

?>
