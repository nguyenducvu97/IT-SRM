<?php
// Check current structure of request_feedback table
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

echo "Current structure of request_feedback table:\n";
echo "=====================================\n";

$result = $db->query("DESCRIBE request_feedback");
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Default'] . "\n";
}

echo "\nChecking if table exists:\n";
$check = $db->query("SHOW TABLES LIKE 'request_feedback'");
if ($check->rowCount() > 0) {
    echo "Table exists\n";
} else {
    echo "Table does NOT exist - need to create it first\n";
}
?>
