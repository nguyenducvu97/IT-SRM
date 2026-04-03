<?php
// Check users table structure
require_once 'config/database.php';

$db = getDatabaseConnection();
$stmt = $db->prepare('DESCRIBE users');
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo 'Users table structure:' . PHP_EOL;
foreach ($columns as $col) {
    echo '- ' . $col['Field'] . ' (' . $col['Type'] . ')' . PHP_EOL;
}
?>
