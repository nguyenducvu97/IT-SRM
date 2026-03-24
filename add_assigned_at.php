<?php
// Add assigned_at column to service_requests table
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Adding assigned_at column</h2>";

try {
    // Add assigned_at column if it doesn't exist
    $alter_query = "ALTER TABLE service_requests 
    ADD COLUMN assigned_at TIMESTAMP NULL DEFAULT NULL 
    AFTER assigned_to";
    
    $stmt = $db->prepare($alter_query);
    $result = $stmt->execute();
    
    if ($result) {
        echo "✅ assigned_at column added successfully<br>";
    } else {
        echo "⚠️ Column might already exist or error occurred<br>";
    }
    
    // Update existing assigned requests with assigned_at time
    echo "<h3>Updating existing requests...</h3>";
    
    $update_query = "UPDATE service_requests 
    SET assigned_at = DATE_ADD(created_at, INTERVAL 1 HOUR)
    WHERE assigned_to IS NOT NULL 
    AND assigned_at IS NULL";
    
    $update_stmt = $db->prepare($update_query);
    $update_result = $update_stmt->execute();
    
    echo "Updated " . $update_stmt->rowCount() . " requests with assigned_at time<br>";
    
    // Check updated data
    echo "<h3>Sample updated data:</h3>";
    $check_query = "SELECT 
        id, 
        assigned_to, 
        created_at, 
        assigned_at, 
        updated_at,
        TIMESTAMPDIFF(MINUTE, created_at, assigned_at) as response_minutes
    FROM service_requests 
    WHERE assigned_to IS NOT NULL 
    ORDER BY id DESC 
    LIMIT 5";
    
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute();
    $data = $check_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Assigned To</th><th>Created</th><th>Assigned At</th><th>Response (minutes)</th></tr>";
    
    foreach ($data as $row) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['assigned_to'] . "</td>";
        echo "<td>" . $row['created_at'] . "</td>";
        echo "<td>" . ($row['assigned_at'] ?? 'NULL') . "</td>";
        echo "<td>" . $row['response_minutes'] . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}

?>
