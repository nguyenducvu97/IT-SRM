<?php
// Database update script to add accepted_at column
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Check if accepted_at column already exists
    $checkQuery = "SHOW COLUMNS FROM service_requests LIKE 'accepted_at'";
    $stmt = $db->prepare($checkQuery);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Add the column
        $alterQuery = "ALTER TABLE service_requests ADD COLUMN accepted_at TIMESTAMP NULL AFTER assigned_to";
        $db->exec($alterQuery);
        echo "Column 'accepted_at' added successfully.\n";
        
        // Update existing records
        $updateQuery = "UPDATE service_requests SET accepted_at = updated_at WHERE assigned_to IS NOT NULL AND accepted_at IS NULL";
        $db->exec($updateQuery);
        echo "Existing records updated successfully.\n";
    } else {
        echo "Column 'accepted_at' already exists.\n";
    }
    
    echo "Database update completed successfully!\n";
    
} catch (PDOException $e) {
    echo "Error updating database: " . $e->getMessage() . "\n";
}
?>
