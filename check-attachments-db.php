<?php
// Check database for requests with attachments
echo "<h2>🔍 Checking Database for Attachments</h2>";

require_once 'config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<h3>📊 Checking Attachments Table:</h3>";
    
    // Check if attachments table exists
    $table_check = $db->query("SHOW TABLES LIKE 'attachments'");
    if ($table_check->rowCount() > 0) {
        echo "<p>✅ Attachments table exists</p>";
        
        // Get all attachments
        $attachments_query = "SELECT a.*, s.title as request_title, s.id as request_id 
                              FROM attachments a 
                              LEFT JOIN service_requests s ON a.service_request_id = s.id 
                              ORDER BY a.uploaded_at DESC 
                              LIMIT 10";
        
        $attachments_stmt = $db->prepare($attachments_query);
        $attachments_stmt->execute();
        $attachments = $attachments_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "<h4>📎 Recent Attachments (" . count($attachments) . " found):</h4>";
        
        if (!empty($attachments)) {
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>Request ID</th><th>Request Title</th><th>Filename</th><th>Original Name</th><th>Size</th><th>MIME Type</th><th>File Path</th><th>File Exists</th></tr>";
            
            foreach ($attachments as $attachment) {
                $file_path = "uploads/requests/" . $attachment['filename'];
                $file_exists = file_exists($file_path);
                $is_image = strpos($attachment['mime_type'], 'image/') === 0;
                
                echo "<tr>";
                echo "<td><a href='request-detail.html?id=" . $attachment['request_id'] . "' target='_blank'>" . $attachment['request_id'] . "</a></td>";
                echo "<td>" . htmlspecialchars($attachment['request_title'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($attachment['filename']) . "</td>";
                echo "<td>" . htmlspecialchars($attachment['original_name']) . "</td>";
                echo "<td>" . number_format($attachment['file_size']) . " bytes</td>";
                echo "<td>" . $attachment['mime_type'] . "</td>";
                echo "<td>" . $file_path . "</td>";
                echo "<td style='color: " . ($file_exists ? 'green' : 'red') . ";'>" . ($file_exists ? '✅ Yes' : '❌ No') . "</td>";
                echo "</tr>";
                
                if ($is_image && $file_exists) {
                    echo "<tr>";
                    echo "<td colspan='8'>";
                    echo "<strong>Image Preview:</strong><br>";
                    echo "<img src='$file_path' style='max-width: 300px; max-height: 150px; border: 1px solid #ccc; border-radius: 4px;' alt='Preview'>";
                    echo "</td>";
                    echo "</tr>";
                }
            }
            
            echo "</table>";
            
            // Show requests with attachments
            echo "<h3>📋 Requests with Attachments:</h3>";
            $requests_with_attachments = "SELECT DISTINCT service_request_id, COUNT(*) as attachment_count 
                                          FROM attachments 
                                          GROUP BY service_request_id";
            
            $req_stmt = $db->prepare($requests_with_attachments);
            $req_stmt->execute();
            $requests = $req_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
            echo "<tr><th>Request ID</th><th>Attachment Count</th><th>Action</th></tr>";
            
            foreach ($requests as $request) {
                echo "<tr>";
                echo "<td>" . $request['service_request_id'] . "</td>";
                echo "<td>" . $request['attachment_count'] . "</td>";
                echo "<td><a href='request-detail.html?id=" . $request['service_request_id'] . "' target='_blank' class='btn btn-sm btn-primary'>View Request</a></td>";
                echo "</tr>";
            }
            
            echo "</table>";
            
        } else {
            echo "<p style='color: orange;'>⚠️ No attachments found in database</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Attachments table does not exist</p>";
        
        // Create attachments table
        echo "<h3>🔧 Creating Attachments Table:</h3>";
        $create_table = "
            CREATE TABLE attachments (
                id INT AUTO_INCREMENT PRIMARY KEY,
                service_request_id INT NOT NULL,
                filename VARCHAR(255) NOT NULL,
                original_name VARCHAR(255) NOT NULL,
                file_size INT NOT NULL,
                mime_type VARCHAR(100) NOT NULL,
                uploaded_by INT NOT NULL,
                uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
                FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ";
        
        try {
            $db->exec($create_table);
            echo "<p style='color: green;'>✅ Attachments table created successfully</p>";
        } catch (Exception $e) {
            echo "<p style='color: red;'>❌ Failed to create table: " . $e->getMessage() . "</p>";
        }
    }
    
    // Check upload directory
    echo "<h3>📁 Upload Directory Check:</h3>";
    $upload_dir = "uploads/requests/";
    
    if (is_dir($upload_dir)) {
        echo "<p>✅ Upload directory exists: $upload_dir</p>";
        
        if (is_writable($upload_dir)) {
            echo "<p>✅ Upload directory is writable</p>";
        } else {
            echo "<p style='color: orange;'>⚠️ Upload directory is not writable</p>";
        }
        
        $files = scandir($upload_dir);
        $files = array_diff($files, ['.', '..']);
        
        echo "<p>📁 Files in upload directory: " . count($files) . "</p>";
        
        if (!empty($files)) {
            echo "<ul>";
            foreach ($files as $file) {
                $file_path = $upload_dir . $file;
                $file_size = filesize($file_path);
                $is_readable = is_readable($file_path);
                echo "<li style='color: " . ($is_readable ? 'green' : 'red') . ";'>";
                echo "$file (" . number_format($file_size) . " bytes) " . ($is_readable ? '✅' : '❌');
                echo "</li>";
            }
            echo "</ul>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Upload directory does not exist: $upload_dir</p>";
        
        // Create upload directory
        if (mkdir($upload_dir, 0777, true)) {
            echo "<p style='color: green;'>✅ Upload directory created: $upload_dir</p>";
        } else {
            echo "<p style='color: red;'>❌ Failed to create upload directory</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database error: " . $e->getMessage() . "</p>";
}

echo "<hr>";

echo "<h3>🎯 Next Steps:</h3>";
echo "<ol>";
echo "<li><strong>If attachments exist in database:</strong> Click 'View Request' to test display</li>";
echo "<li><strong>If no attachments:</strong> Create a new request with file upload</li>";
echo "<li><strong>If files don't exist:</strong> Check upload process or file permissions</li>";
echo "<li><strong>If table missing:</strong> Table should be created automatically</li>";
echo "</ol>";

echo "<hr>";
echo "<p><a href='javascript:history.back()'>← Back</a></p>";
?>
