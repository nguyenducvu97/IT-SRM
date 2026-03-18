<?php
// Check if reject_request_attachments table exists
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->prepare("DESCRIBE reject_request_attachments");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "reject_request_attachments table columns:\n";
    foreach ($columns as $column) {
        echo "- " . $column['Field'] . " (" . $column['Type'] . ")\n";
    }
    
    echo "\nTable exists: YES\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "Table exists: NO\n";
    
    // Try to create the table
    echo "\nCreating table...\n";
    $create_sql = "
        CREATE TABLE reject_request_attachments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            reject_request_id INT NOT NULL,
            original_name VARCHAR(255) NOT NULL,
            filename VARCHAR(255) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            
            FOREIGN KEY (reject_request_id) REFERENCES reject_requests(id) ON DELETE CASCADE,
            
            INDEX idx_reject_request_id (reject_request_id),
            INDEX idx_filename (filename),
            INDEX idx_uploaded_at (uploaded_at)
        )
    ";
    
    try {
        $db->exec($create_sql);
        echo "Table created successfully!\n";
    } catch (Exception $create_error) {
        echo "Failed to create table: " . $create_error->getMessage() . "\n";
    }
}
?>
