<?php
// Script to remove updated_at from tables that don't need it
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

echo "Removing updated_at from tables that don't need it...\n";
echo "================================================\n\n";

// Tables that should have updated_at removed
$tables_to_clean = [
    'request_feedback',
    'resolutions', 
    'comments',
    'attachments',
    'reject_requests',
    'support_requests'
];

foreach ($tables_to_clean as $table) {
    echo "Processing table: $table\n";
    
    try {
        // Check if table exists
        $check = $db->query("SHOW TABLES LIKE '$table'");
        if ($check->rowCount() == 0) {
            echo "  - Table does not exist, skipping\n\n";
            continue;
        }
        
        // Check if updated_at column exists
        $column_check = $db->query("SHOW COLUMNS FROM `$table` LIKE 'updated_at'");
        if ($column_check->rowCount() > 0) {
            echo "  - Found updated_at column, removing...\n";
            $db->exec("ALTER TABLE `$table` DROP COLUMN updated_at");
            echo "  - updated_at removed successfully\n";
        } else {
            echo "  - No updated_at column found\n";
        }
        
    } catch (Exception $e) {
        echo "  - ERROR: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

echo "Cleanup completed!\n";
echo "\n=== Tables that SHOULD KEEP updated_at ===\n";
echo "These tables need update tracking:\n";
echo "- service_requests (status, assignment changes)\n";
echo "- users (profile updates)\n"; 
echo "- categories (modifications)\n";
echo "- departments (modifications)\n";
?>
