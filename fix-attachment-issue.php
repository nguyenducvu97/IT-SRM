<?php
// Fix attachment issue for request 27
echo "<h2>🔧 Fixing Attachment Issue for Request 27</h2>";

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h3>🔍 Current Attachment Status:</h3>";
    
    // Get current attachment for request 27
    $current_query = "SELECT * FROM attachments WHERE service_request_id = 27";
    $current_stmt = $db->prepare($current_query);
    $current_stmt->execute();
    $current = $current_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current) {
        echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px;'>";
        echo "<h4>Current Attachment Record:</h4>";
        echo "<ul>";
        echo "<li><strong>ID:</strong> " . $current['id'] . "</li>";
        echo "<li><strong>Filename:</strong> " . $current['filename'] . "</li>";
        echo "<li><strong>Original Name:</strong> " . $current['original_name'] . "</li>";
        echo "<li><strong>File Path:</strong> uploads/requests/" . $current['filename'] . "</li>";
        echo "<li><strong>File Exists:</strong> " . (file_exists("uploads/requests/" . $current['filename']) ? '✅ Yes' : '❌ No') . "</li>";
        echo "</ul>";
        echo "</div>";
        
        // Get available files
        echo "<h3>📁 Available Files in Upload Directory:</h3>";
        $upload_dir = "uploads/requests/";
        $files = scandir($upload_dir);
        $files = array_diff($files, ['.', '..']);
        
        // Filter for image files
        $image_files = array_filter($files, function($file) {
            $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
            return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        });
        
        echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr><th>Filename</th><th>Size</th><th>Action</th></tr>";
        
        foreach ($image_files as $file) {
            $file_path = $upload_dir . $file;
            $file_size = filesize($file_path);
            
            echo "<tr>";
            echo "<td>" . htmlspecialchars($file) . "</td>";
            echo "<td>" . number_format($file_size) . " bytes</td>";
            echo "<td>";
            echo "<button onclick='fixAttachment(\"" . htmlspecialchars($file) . "\")' class='btn btn-sm btn-primary'>Use This File</button>";
            echo "</td>";
            echo "</tr>";
        }
        
        echo "</table>";
        
    } else {
        echo "<p style='color: orange;'>⚠️ No attachment found for request 27</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

?>

<script>
function fixAttachment(filename) {
    if (confirm('Use file "' + filename + '" for request 27?')) {
        window.location.href = 'fix-attachment.php?file=' + encodeURIComponent(filename);
    }
}
</script>

<style>
.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9rem;
}
.btn-primary {
    background: #007bff;
    color: white;
}
.btn-sm {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}
</style>

<hr>

<h3>🔧 Alternative Solutions:</h3>
<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <div>
        <h4>Option 1: Update Database</h4>
        <p>Update the attachment record to point to an existing file.</p>
    </div>
    <div>
        <h4>Option 2: Delete Orphaned Record</h4>
        <p>Delete the attachment record since the file doesn't exist.</p>
        <button onclick="deleteAttachment()" class="btn btn-danger">Delete Attachment Record</button>
    </div>
</div>

<script>
function deleteAttachment() {
    if (confirm('Delete the attachment record for request 27?')) {
        window.location.href = 'fix-attachment.php?action=delete';
    }
}
</script>

<hr>
<p><a href='javascript:history.back()'>← Back</a></p>
?>
