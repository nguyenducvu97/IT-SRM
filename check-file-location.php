<?php
echo "<h2>File Location Check</h2>";

$targetFile = "req_69d751488ba4a3.13984562.jpg";

// Check all possible upload directories
$directories = [
    'uploads/requests/',
    'uploads/completed/',
    'uploads/attachments/',
];

echo "<h3>Searching for: $targetFile</h3>";

foreach ($directories as $dir) {
    echo "<h4>Directory: $dir</h4>";
    
    if (is_dir($dir)) {
        echo "<p>Directory exists</p>";
        
        // Search recursively
        $files = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        
        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getFilename() === $targetFile) {
                $files[] = $file->getPathname();
            }
        }
        
        if (!empty($files)) {
            echo "<p style='color: green;'>Found in:</p>";
            foreach ($files as $found) {
                echo "<p>- $found</p>";
            }
        } else {
            echo "<p style='color: red;'>Not found</p>";
        }
        
        // List some files in this directory for reference
        echo "<p>Sample files in directory:</p>";
        $sampleFiles = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
        $count = 0;
        foreach ($iterator as $file) {
            if ($file->isFile() && $count < 5) {
                $sampleFiles[] = $file->getFilename();
                $count++;
            }
            if ($count >= 5) break;
        }
        
        if (!empty($sampleFiles)) {
            foreach ($sampleFiles as $sample) {
                echo "<p>- $sample</p>";
            }
        } else {
            echo "<p>No files found</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Directory does not exist</p>";
    }
    echo "<hr>";
}

// Check attachments directory structure
echo "<h3>Attachments Directory Structure:</h3>";
$attachmentsDir = 'uploads/attachments/';
if (is_dir($attachmentsDir)) {
    $subdirs = glob($attachmentsDir . '*', GLOB_ONLYDIR);
    echo "<p>Subdirectories found: " . count($subdirs) . "</p>";
    
    foreach (array_slice($subdirs, 0, 5) as $subdir) {
        echo "<p>- " . basename($subdir) . "</p>";
        
        // List files in this subdirectory
        $files = glob($subdir . '/*');
        if (!empty($files)) {
            foreach (array_slice($files, 0, 3) as $file) {
                echo "<p>  - " . basename($file) . "</p>";
            }
        }
    }
} else {
    echo "<p style='color: red;'>Attachments directory does not exist</p>";
}

// Check database for this attachment
echo "<h3>Database Check:</h3>";
try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    $stmt = $db->prepare("SELECT * FROM attachments WHERE filename = ?");
    $stmt->execute([$targetFile]);
    $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attachment) {
        echo "<p style='color: green;'>Found in database:</p>";
        echo "<table border='1'>";
        echo "<tr><th>ID</th><td>{$attachment['id']}</td></tr>";
        echo "<tr><th>Service Request ID</th><td>{$attachment['service_request_id']}</td></tr>";
        echo "<tr><th>Original Name</th><td>{$attachment['original_name']}</td></tr>";
        echo "<tr><th>Filename</th><td>{$attachment['filename']}</td></tr>";
        echo "<tr><th>File Size</th><td>{$attachment['file_size']}</td></tr>";
        echo "<tr><th>Mime Type</th><td>{$attachment['mime_type']}</td></tr>";
        echo "<tr><th>Uploaded By</th><td>{$attachment['uploaded_by']}</td></tr>";
        echo "<tr><th>Uploaded At</th><td>{$attachment['uploaded_at']}</td></tr>";
        echo "</table>";
        
        // Expected path based on database
        $expectedPath = "uploads/attachments/{$attachment['service_request_id']}/{$attachment['filename']}";
        echo "<p>Expected path: $expectedPath</p>";
        echo "<p>File exists: " . (file_exists($expectedPath) ? 'Yes' : 'No') . "</p>";
    } else {
        echo "<p style='color: red;'>Not found in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Database error: " . $e->getMessage() . "</p>";
}
?>
