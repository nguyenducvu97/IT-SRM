<?php
// Script to update the request_feedback table with new columns
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

echo "Updating request_feedback table...\n";

try {
    // Check if columns already exist
    $check_columns = [
        'ease_of_use',
        'speed_stability', 
        'requirement_meeting',
        'software_feedback'
    ];
    
    foreach ($check_columns as $column) {
        $result = $db->query("SHOW COLUMNS FROM request_feedback LIKE '$column'");
        if ($result->rowCount() == 0) {
            echo "Adding column: $column\n";
            if ($column === 'software_feedback') {
                $db->exec("ALTER TABLE request_feedback ADD COLUMN $column TEXT COMMENT 'Feedback about IT SRM software'");
            } else {
                $db->exec("ALTER TABLE request_feedback ADD COLUMN $column INT COMMENT 'Rating 1-5'");
            }
        } else {
            echo "Column $column already exists\n";
        }
    }
    
    echo "Table update completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error updating table: " . $e->getMessage() . "\n";
}
?>
