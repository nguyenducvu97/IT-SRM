<?php
// Direct fix for request 27 attachment
echo "<h2>🔧 Direct Fix for Request 27 Attachment</h2>";

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Use the PNG file that exists
    $filename = '69a1081d63042_1772161053.png';
    $file_path = "uploads/requests/" . $filename;
    
    echo "<h3>📁 File Check:</h3>";
    echo "<p><strong>File:</strong> $file_path</p>";
    echo "<p><strong>Exists:</strong> " . (file_exists($file_path) ? '✅ Yes' : '❌ No') . "</p>";
    
    if (file_exists($file_path)) {
        $file_size = filesize($file_path);
        $mime_type = 'image/png'; // We know it's a PNG
        
        echo "<p><strong>Size:</strong> " . number_format($file_size) . " bytes</p>";
        echo "<p><strong>MIME Type:</strong> $mime_type</p>";
        
        // Update the database
        $update_query = "UPDATE attachments 
                         SET filename = :filename, 
                             file_size = :file_size, 
                             mime_type = :mime_type,
                             original_name = :original_name
                         WHERE service_request_id = 27";
        
        $update_stmt = $db->prepare($update_query);
        $update_stmt->bindParam(':filename', $filename);
        $update_stmt->bindParam(':file_size', $file_size);
        $update_stmt->bindParam(':mime_type', $mime_type);
        $update_stmt->bindParam(':original_name', $filename);
        
        echo "<h3>🔧 Updating Database:</h3>";
        
        if ($update_stmt->execute()) {
            echo "<div style='background: #d4edda; color: #155724; padding: 15px; border-radius: 5px;'>";
            echo "<h3>✅ Database Updated Successfully!</h3>";
            echo "<p><strong>Rows affected:</strong> " . $update_stmt->rowCount() . "</p>";
            echo "</div>";
            
            // Verify the update
            echo "<h3>🔍 Verification:</h3>";
            $verify_query = "SELECT * FROM attachments WHERE service_request_id = 27";
            $verify_stmt = $db->prepare($verify_query);
            $verify_stmt->execute();
            $attachment = $verify_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($attachment) {
                echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
                echo "<tr><th>Field</th><th>Value</th></tr>";
                echo "<tr><td>ID</td><td>" . $attachment['id'] . "</td></tr>";
                echo "<tr><td>Service Request ID</td><td>" . $attachment['service_request_id'] . "</td></tr>";
                echo "<tr><td>Filename</td><td>" . $attachment['filename'] . "</td></tr>";
                echo "<tr><td>Original Name</td><td>" . $attachment['original_name'] . "</td></tr>";
                echo "<tr><td>File Size</td><td>" . number_format($attachment['file_size']) . " bytes</td></tr>";
                echo "<tr><td>MIME Type</td><td>" . $attachment['mime_type'] . "</td></tr>";
                echo "<tr><td>File Exists</td><td style='color: " . (file_exists("uploads/requests/" . $attachment['filename']) ? 'green' : 'red') . ";'>" . (file_exists("uploads/requests/" . $attachment['filename']) ? '✅ Yes' : '❌ No') . "</td></tr>";
                echo "</table>";
                
                echo "<div style='background: #f8f9fa; padding: 15px; border-radius: 5px; margin-top: 20px;'>";
                echo "<h4>🔍 Test the Fix:</h4>";
                echo "<p><a href='request-detail.html?id=27' target='_blank' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px;'>View Request 27</a></p>";
                echo "<p>The attachment should now display correctly in the request details.</p>";
                echo "</div>";
                
                // Show image preview
                echo "<h4>🖼️ Image Preview:</h4>";
                echo "<img src='$file_path' style='max-width: 300px; max-height: 200px; border: 1px solid #ccc; border-radius: 4px;' alt='Attachment Preview'>";
                
            } else {
                echo "<p style='color: red;'>❌ Could not verify the update</p>";
            }
            
        } else {
            echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
            echo "<h3>❌ Database Update Failed</h3>";
            echo "<p>Error: " . print_r($update_stmt->errorInfo(), true) . "</p>";
            echo "</div>";
        }
        
    } else {
        echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
        echo "<h3>❌ File Not Found</h3>";
        echo "<p>The file $file_path does not exist.</p>";
        echo "</div>";
    }
    
} catch (Exception $e) {
    echo "<div style='background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px;'>";
    echo "<h3>❌ Error: " . $e->getMessage() . "</h3>";
    echo "</div>";
}

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
