<?php
// Check service_requests table structure
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Service Requests Table Structure</h2>";

$query = "DESCRIBE service_requests";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";

foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>" . $column['Field'] . "</td>";
    echo "<td>" . $column['Type'] . "</td>";
    echo "<td>" . $column['Null'] . "</td>";
    echo "<td>" . $column['Key'] . "</td>";
    echo "<td>" . $column['Default'] . "</td>";
    echo "<td>" . $column['Extra'] . "</td>";
    echo "</tr>";
}

echo "</table>";

// Check if assigned_at column exists
$has_assigned_at = false;
foreach ($columns as $column) {
    if ($column['Field'] === 'assigned_at') {
        $has_assigned_at = true;
        break;
    }
}

if (!$has_assigned_at) {
    echo "<h3>assigned_at column does not exist - need to add it</h3>";
} else {
    echo "<h3>assigned_at column already exists</h3>";
}

// Sample data check
echo "<h3>Sample Data:</h3>";
$data_query = "SELECT id, assigned_to, created_at, updated_at, resolved_at FROM service_requests ORDER BY id DESC LIMIT 3";
$data_stmt = $db->prepare($data_query);
$data_stmt->execute();
$data = $data_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Assigned To</th><th>Created</th><th>Updated</th><th>Resolved</th></tr>";

foreach ($data as $row) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . ($row['assigned_to'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "<td>" . ($row['updated_at'] ?? 'NULL') . "</td>";
    echo "<td>" . ($row['resolved_at'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";

?>
