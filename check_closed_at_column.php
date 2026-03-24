<?php
// Check if closed_at column exists
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Checking service_requests table structure...\n";

// Get table structure
$stmt = $db->prepare("DESCRIBE service_requests");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

$found_closed_at = false;
echo "Columns in service_requests table:\n";
foreach ($columns as $column) {
    echo "- {$column['Field']} ({$column['Type']})\n";
    if ($column['Field'] === 'closed_at') {
        $found_closed_at = true;
    }
}

if (!$found_closed_at) {
    echo "\n❌ closed_at column not found. Adding it...\n";
    
    // Add the column
    $sql = "ALTER TABLE service_requests ADD COLUMN closed_at TIMESTAMP NULL COMMENT 'When request was closed by user'";
    $db->exec($sql);
    echo "✅ closed_at column added successfully\n";
} else {
    echo "\n✅ closed_at column already exists\n";
}

echo "\nChecking request_feedback table structure...\n";

// Get feedback table structure
$stmt = $db->prepare("DESCRIBE request_feedback");
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Columns in request_feedback table:\n";
foreach ($columns as $column) {
    echo "- {$column['Field']} ({$column['Type']})\n";
}

echo "\nCheck completed.\n";
?>
