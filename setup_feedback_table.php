<?php
// Complete script to create/update request_feedback table
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

echo "Setting up request_feedback table...\n";

try {
    // Create table if not exists
    $create_table_query = "CREATE TABLE IF NOT EXISTS request_feedback (
        id INT AUTO_INCREMENT PRIMARY KEY,
        service_request_id INT NOT NULL,
        created_by INT NOT NULL,
        rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        feedback TEXT,
        software_feedback TEXT,
        would_recommend VARCHAR(20),
        ease_of_use INT CHECK (ease_of_use IS NULL OR (ease_of_use >= 1 AND ease_of_use <= 5)),
        speed_stability INT CHECK (speed_stability IS NULL OR (speed_stability >= 1 AND speed_stability <= 5)),
        requirement_meeting INT CHECK (requirement_meeting IS NULL OR (requirement_meeting >= 1 AND requirement_meeting <= 5)),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        
        FOREIGN KEY (service_request_id) REFERENCES service_requests(id) ON DELETE CASCADE,
        FOREIGN KEY (created_by) REFERENCES users(id),
        
        INDEX idx_service_request_id (service_request_id),
        INDEX idx_created_by (created_by)
    )";
    
    $db->exec($create_table_query);
    echo "Table created/verified successfully\n";
    
    // Check if columns exist and add if needed
    $check_columns = [
        'software_feedback' => "TEXT COMMENT 'Feedback about IT SRM software'",
        'ease_of_use' => "INT CHECK (ease_of_use IS NULL OR (ease_of_use >= 1 AND ease_of_use <= 5))",
        'speed_stability' => "INT CHECK (speed_stability IS NULL OR (speed_stability >= 1 AND speed_stability <= 5))",
        'requirement_meeting' => "INT CHECK (requirement_meeting IS NULL OR (requirement_meeting >= 1 AND requirement_meeting <= 5))"
    ];
    
    foreach ($check_columns as $column => $definition) {
        $result = $db->query("SHOW COLUMNS FROM request_feedback LIKE '$column'");
        if ($result->rowCount() == 0) {
            echo "Adding column: $column\n";
            $db->exec("ALTER TABLE request_feedback ADD COLUMN $column $definition");
        } else {
            echo "Column $column already exists\n";
        }
    }
    
    echo "Setup completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
