<?php
// Debug and create attachment tables individually
require_once 'config/database.php';

echo "<h2>Creating Attachment Tables - Debug Version</h2>";

try {
    $pdo = getDatabaseConnection();
    echo "<p>✓ Database connection successful</p>";
    
    // Check if support_requests table exists
    echo "<h3>Checking support_requests table:</h3>";
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'support_requests'");
    $stmt->execute();
    $support_requests_exists = $stmt->fetch();
    
    if ($support_requests_exists) {
        echo "<p>✓ support_requests table exists</p>";
        
        // Show structure
        $stmt = $pdo->prepare("DESCRIBE support_requests");
        $stmt->execute();
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "<table border='1' cellpadding='3'>";
        echo "<tr><th>Field</th><th>Type</th></tr>";
        foreach ($columns as $col) {
            echo "<tr><td>{$col['Field']}</td><td>{$col['Type']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>✗ support_requests table does not exist!</p>";
    }
    
    // Create support_request_attachments table manually
    echo "<h3>Creating support_request_attachments table:</h3>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS support_request_attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            support_request_id INT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_support_request_id (support_request_id),
            INDEX idx_filename (filename),
            INDEX idx_uploaded_at (uploaded_at)
        )";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ support_request_attachments table created successfully!</p>";
        
        // Add foreign key if support_requests exists
        if ($support_requests_exists) {
            try {
                $pdo->exec("ALTER TABLE support_request_attachments ADD CONSTRAINT fk_support_request_attachments_support_request_id FOREIGN KEY (support_request_id) REFERENCES support_requests(id) ON DELETE CASCADE");
                echo "<p style='color: green;'>✓ Foreign key added successfully!</p>";
            } catch (Exception $e) {
                echo "<p style='color: orange;'>⚠ Foreign key warning: " . $e->getMessage() . "</p>";
            }
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error creating support_request_attachments: " . $e->getMessage() . "</p>";
    }
    
    // Create complete_request_attachments table manually
    echo "<h3>Creating complete_request_attachments table:</h3>";
    try {
        $sql = "CREATE TABLE IF NOT EXISTS complete_request_attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            service_request_id INT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            INDEX idx_service_request_id (service_request_id),
            INDEX idx_filename (filename),
            INDEX idx_uploaded_at (uploaded_at)
        )";
        
        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ complete_request_attachments table created successfully!</p>";
        
        // Add foreign key
        try {
            $pdo->exec("ALTER TABLE complete_request_attachments ADD CONSTRAINT fk_complete_request_attachments_service_request_id FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE");
            echo "<p style='color: green;'>✓ Foreign key added successfully!</p>";
        } catch (Exception $e) {
            echo "<p style='color: orange;'>⚠ Foreign key warning: " . $e->getMessage() . "</p>";
        }
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>✗ Error creating complete_request_attachments: " . $e->getMessage() . "</p>";
    }
    
    // Final verification
    echo "<h3>Final Verification:</h3>";
    $tables = ['support_request_attachments', 'reject_request_attachments', 'complete_request_attachments'];
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        $exists = $stmt->fetch();
        
        if ($exists) {
            echo "<p style='color: green;'>✓ Table '$table' exists</p>";
        } else {
            echo "<p style='color: red;'>✗ Table '$table' was not created</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Database Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.html'>← Back to Application</a></p>";
?>
