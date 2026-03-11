<?php
// Check all tables and their updated_at columns
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

echo "Checking all tables for updated_at columns...\n";
echo "==========================================\n\n";

// Get all tables
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

foreach ($tables as $table) {
    echo "Table: $table\n";
    echo str_repeat("-", strlen($table) + 7) . "\n";
    
    // Get table structure
    $columns = $db->query("DESCRIBE $table")->fetchAll(PDO::FETCH_ASSOC);
    
    $has_created_at = false;
    $has_updated_at = false;
    $created_at_col = null;
    $updated_at_col = null;
    
    foreach ($columns as $column) {
        if ($column['Field'] === 'created_at') {
            $has_created_at = true;
            $created_at_col = $column;
        }
        if ($column['Field'] === 'updated_at') {
            $has_updated_at = true;
            $updated_at_col = $column;
        }
    }
    
    echo "  - Has created_at: " . ($has_created_at ? "YES" : "NO") . "\n";
    echo "  - Has updated_at: " . ($has_updated_at ? "YES" : "NO") . "\n";
    
    if ($has_updated_at) {
        echo "  - Updated_at type: " . $updated_at_col['Type'] . "\n";
        echo "  - Updated_at default: " . $updated_at_col['Default'] . "\n";
        echo "  - Updated_at extra: " . $updated_at_col['Extra'] . "\n";
        
        // Check if this table likely needs updated_at
        $needs_update = analyzeTableNeedsUpdate($table, $columns);
        echo "  - RECOMMENDATION: " . ($needs_update ? "KEEP updated_at" : "REMOVE updated_at") . "\n";
    }
    echo "\n";
}

function analyzeTableNeedsUpdate($table, $columns) {
    // Tables that typically need update tracking
    $update_needed_tables = [
        'service_requests', // requests can be updated (status, assignment, etc.)
        'users', // user profiles can be updated
        'categories', // categories can be modified
        'departments' // departments can be modified
    ];
    
    // Static/historical tables that don't need updates
    $static_tables = [
        'request_feedback', // feedback is created once, never modified
        'resolutions', // resolutions are created once, never modified
        'comments', // comments are created once, never modified (typically)
        'attachments', // attachments are created once, never modified
        'reject_requests', // rejection records are created once, never modified
        'support_requests' // support requests are created once, never modified
    ];
    
    // Check if table needs update tracking
    if (in_array($table, $update_needed_tables)) {
        return true;
    }
    
    if (in_array($table, $static_tables)) {
        return false;
    }
    
    // For other tables, check if they have update operations
    // This is a heuristic - you may need to adjust based on your actual business logic
    return false; // Default to no updates unless explicitly needed
}

echo "\n=== SUMMARY ===\n";
echo "Tables to KEEP updated_at:\n";
$keep_tables = ['service_requests', 'users', 'categories', 'departments'];
foreach ($keep_tables as $table) {
    if (in_array($table, $tables)) {
        echo "  - $table\n";
    }
}

echo "\nTables to REMOVE updated_at:\n";
$remove_tables = ['request_feedback', 'resolutions', 'comments', 'attachments', 'reject_requests', 'support_requests'];
foreach ($remove_tables as $table) {
    if (in_array($table, $tables)) {
        echo "  - $table\n";
    }
}
?>
