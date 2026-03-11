<?php
// Remove updated_at column from request_feedback table
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

echo "Removing updated_at column from request_feedback table...\n";

try {
    // Check if column exists
    $result = $db->query("SHOW COLUMNS FROM request_feedback LIKE 'updated_at'");
    if ($result->rowCount() > 0) {
        echo "Dropping updated_at column...\n";
        $db->exec("ALTER TABLE request_feedback DROP COLUMN updated_at");
        echo "updated_at column removed successfully!\n";
    } else {
        echo "updated_at column does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
