<?php
// Cleanup script for missing reject request attachments
require_once 'config/database.php';

echo "<h2>Cleanup Missing Reject Request Attachments</h2>";

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

// Get all attachments from database
$query = "SELECT id, filename, original_name, reject_request_id FROM reject_request_attachments ORDER BY id";
$stmt = $db->prepare($query);
$stmt->execute();
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$uploadsDir = __DIR__ . '/uploads/reject_requests/';
$missingFiles = [];
$existingFiles = [];

echo "<h3>Checking " . count($attachments) . " attachments...</h3>";

foreach ($attachments as $attachment) {
    $filePath = $uploadsDir . $attachment['filename'];
    
    if (file_exists($filePath)) {
        $existingFiles[] = $attachment;
        echo "<p style='color: green;'>✅ Found: " . $attachment['filename'] . " (" . $attachment['original_name'] . ")</p>";
    } else {
        $missingFiles[] = $attachment;
        echo "<p style='color: red;'>❌ Missing: " . $attachment['filename'] . " (" . $attachment['original_name'] . ")</p>";
    }
}

echo "<h3>Summary:</h3>";
echo "<p><strong>Total attachments in database:</strong> " . count($attachments) . "</p>";
echo "<p><strong>Files found:</strong> " . count($existingFiles) . "</p>";
echo "<p><strong>Files missing:</strong> " . count($missingFiles) . "</p>";

if (!empty($missingFiles) && isset($_POST['cleanup'])) {
    echo "<h3>Cleaning up missing files...</h3>";
    
    $deletedCount = 0;
    foreach ($missingFiles as $missing) {
        $deleteQuery = "DELETE FROM reject_request_attachments WHERE id = :id";
        $deleteStmt = $db->prepare($deleteQuery);
        $deleteStmt->bindParam(':id', $missing['id']);
        
        if ($deleteStmt->execute()) {
            echo "<p style='color: orange;'>🗑️ Deleted record for missing file: " . $missing['filename'] . "</p>";
            $deletedCount++;
        } else {
            echo "<p style='color: red;'>❌ Failed to delete record for: " . $missing['filename'] . "</p>";
        }
    }
    
    echo "<p><strong>Cleanup complete. Deleted $deletedCount records.</strong></p>";
}

if (!empty($missingFiles)) {
    echo "<form method='post' style='margin-top: 20px;'>";
    echo "<input type='hidden' name='cleanup' value='1'>";
    echo "<input type='submit' value='Cleanup Missing Files' style='background: #dc3545; color: white; padding: 10px 20px; border: none; cursor: pointer;'>";
    echo "</form>";
    echo "<p><small>This will remove database records for missing files. This action cannot be undone.</small></p>";
} else {
    echo "<p style='color: green; font-weight: bold;'>✅ All attachments are present - no cleanup needed!</p>";
}

echo "<h3>File Directory Status:</h3>";
echo "<p><strong>Uploads directory:</strong> " . $uploadsDir . "</p>";
echo "<p><strong>Directory exists:</strong> " . (file_exists($uploadsDir) ? 'YES' : 'NO') . "</p>";
echo "<p><strong>Directory readable:</strong> " . (is_readable($uploadsDir) ? 'YES' : 'NO') . "</p>";

if (file_exists($uploadsDir)) {
    $files = glob($uploadsDir . '*');
    echo "<p><strong>Files in directory:</strong> " . count($files) . "</p>";
    
    echo "<h4>Sample files in directory:</h4>";
    $sampleFiles = array_slice($files, 0, 10);
    foreach ($sampleFiles as $file) {
        $filename = basename($file);
        echo "<p>- $filename</p>";
    }
    if (count($files) > 10) {
        echo "<p>... and " . (count($files) - 10) . " more files</p>";
    }
}
?>
