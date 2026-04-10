<?php
// Check database structure
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Database Structure Check</h2>";

// Check users table structure
echo "<h3>Users Table Structure:</h3>";
$query = "DESCRIBE users";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";

foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Check service_requests table structure
echo "<h3>Service Requests Table Structure:</h3>";
$query = "DESCRIBE service_requests";
$stmt = $db->prepare($query);
$stmt->execute();
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
echo "<tr style='background-color: #f2f2f2;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";

foreach ($columns as $column) {
    echo "<tr>";
    echo "<td>" . htmlspecialchars($column['Field']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Type']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Null']) . "</td>";
    echo "<td>" . htmlspecialchars($column['Key']) . "</td>";
    echo "</tr>";
}
echo "</table>";

// Test basic JOIN
echo "<h3>Test Basic JOIN:</h3>";
$query = "SELECT sr.id, sr.title, u.username 
          FROM service_requests sr 
          LEFT JOIN users u ON sr.user_id = u.id 
          LIMIT 5";
$stmt = $db->prepare($query);

try {
    $stmt->execute();
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='width: 100%; border-collapse: collapse;'>";
    echo "<tr style='background-color: #f2f2f2;'><th>ID</th><th>Title</th><th>Username</th></tr>";
    
    foreach ($results as $result) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($result['id']) . "</td>";
        echo "<td>" . htmlspecialchars($result['title']) . "</td>";
        echo "<td>" . htmlspecialchars($result['username']) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<p style='color: green;'>JOIN test successful!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>JOIN test failed: " . htmlspecialchars($e->getMessage()) . "</p>";
}

?>
