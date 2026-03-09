<?php
// Process attachment fix
require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if (isset($_GET['file']) && !empty($_GET['file'])) {
        // Update attachment record
        $filename = $_GET['file'];
        $file_path = "uploads/requests/" . $filename;
        
        if (file_exists($file_path)) {
            $file_size = filesize($file_path);
            $mime_type = mime_content_type($file_path);
            
            $update_query = "UPDATE attachments 
                             SET filename = :filename, 
                                 file_size = :file_size, 
                                 mime_type = :mime_type 
                             WHERE service_request_id = 27";
            
            $update_stmt = $db->prepare($update_query);
            $update_stmt->bindParam(':filename', $filename);
            $update_stmt->bindParam(':file_size', $file_size);
            $update_stmt->bindParam(':mime_type', $mime_type);
            
            if ($update_stmt->execute()) {
                echo "<h2>🔧 Processing Attachment Fix</h2>";
                echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
                echo "<h3>✅ Attachment Updated Successfully!</h3>";
                echo "<p><strong>File:</strong> $filename</p>";
                echo "<p><strong>Size:</strong> " . number_format($file_size) . " bytes</p>";
                echo "<p><strong>MIME Type:</strong> $mime_type</p>";
                echo "<p><strong>Request ID:</strong> 27</p>";
                echo "</div>";
                
                echo "<h4>📋 Updated Attachment Details:</h4>";
                echo "<ul>";
                echo "<li><strong>File Path:</strong> uploads/requests/$filename</li>";
                echo "<li><strong>File Exists:</strong> ✅ Yes</li>";
                echo "<li><strong>Is Image:</strong> " . (strpos($mime_type, 'image/') === 0 ? '✅ Yes' : '❌ No') . "</li>";
                echo "</ul>";
                
                echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
                echo "<h4>🔍 Test the Fix:</h4>";
                echo "<p><a href='request-detail.html?id=27' target='_blank' class='btn btn-primary'>View Request 27</a></p>";
                echo "<p>The attachment should now display correctly in the request details.</p>";
                echo "</div>";
                
            } else {
                echo "<h2>🔧 Processing Attachment Fix</h2>";
                echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
                echo "<h3>❌ Failed to Update Attachment</h3>";
                echo "</div>";
            }
            
        } else {
            echo "<h2>🔧 Processing Attachment Fix</h2>";
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
            echo "<h3>❌ File Not Found</h3>";
            echo "<p>File: $file_path</p>";
            echo "</div>";
        }
        
    } elseif (isset($_GET['action']) && $_GET['action'] === 'delete') {
        // Delete orphaned attachment record
        $delete_query = "DELETE FROM attachments WHERE service_request_id = 27";
        $delete_stmt = $db->prepare($delete_query);
        
        if ($delete_stmt->execute()) {
            echo "<h2>🔧 Processing Attachment Fix</h2>";
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
            echo "<h3>✅ Attachment Record Deleted</h3>";
            echo "<p>The orphaned attachment record for request 27 has been deleted.</p>";
            echo "</div>";
            
            echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
            echo "<h4>🔍 Test the Fix:</h4>";
            echo "<p><a href='request-detail.html?id=27' target='_blank' class='btn btn-primary'>View Request 27</a></p>";
            echo "<p>The request should now load without attachment errors.</p>";
            echo "</div>";
            
        } else {
            echo "<h2>🔧 Processing Attachment Fix</h2>";
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
            echo "<h3>❌ Failed to Delete Attachment</h3>";
            echo "</div>";
        }
        
    } else {
        echo "<h2>🔧 Processing Attachment Fix</h2>";
        echo "<div style='background: #fff3cd; color: #856404; padding: 15px; border-radius: 5px;'>";
        echo "<h3>⚠️ No Action Specified</h3>";
        echo "<p>Please specify a file to use or an action to perform.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<h2>🔧 Processing Attachment Fix</h2>";
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Database Error: " . $e->getMessage() . "</h3>";
    echo "</div>";
}

echo "<hr>";

echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>Test the fix:</strong> Click 'View Request 27' to see if attachment displays</li>";
echo "<li><strong>Check attachment section:</strong> Should show the image/file correctly</li>";
echo "<li><strong>Test image preview:</strong> Click on image to open modal</li>";
echo "<li><strong>Test download:</strong> Click download button</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='fix-attachment-issue.php'>← Back to Fix Options</a></p>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";

<style>
.btn {
    padding: 0.5rem 1rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    text-decoration: none;
    display: inline-block;
    font-size: 0.9rem;
    margin: 0.25rem;
}
.btn-primary {
    background: #007bff;
    color: white;
}
.btn-danger {
    background: #dc3545;
    color: white;
}
</style>
?>
