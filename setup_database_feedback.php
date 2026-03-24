<?php
// Script to setup database for feedback functionality
require_once 'config/database.php';

echo "Setting up database for feedback functionality...\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        die("Database connection failed\n");
    }
    
    // SQL files to execute
    $sqlFiles = [
        'database/create_request_feedback_table.sql',
        'database/add_software_feedback.sql', 
        'database/add_feedback_rating_columns.sql',
        'database/add_closed_at_column.sql'
    ];
    
    foreach ($sqlFiles as $sqlFile) {
        echo "\nProcessing: $sqlFile\n";
        
        if (!file_exists($sqlFile)) {
            echo "⚠ File not found: $sqlFile\n";
            continue;
        }
        
        $sql = file_get_contents($sqlFile);
        
        // Split into individual statements (simplified)
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement)) {
                echo "Executing: $statement\n";
                
                try {
                    $db->exec($statement);
                    echo "✓ Success\n";
                } catch (PDOException $e) {
                    if (strpos($e->getMessage(), 'Duplicate column name') !== false || 
                        strpos($e->getMessage(), 'already exists') !== false) {
                        echo "⚠ Already exists - skipping\n";
                    } else {
                        echo "✗ Error: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    }
    
    echo "\nDatabase setup completed!\n";
    echo "Feedback functionality is now ready.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
