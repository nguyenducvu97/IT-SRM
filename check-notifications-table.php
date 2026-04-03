<?php
// Check notifications table structure
require_once 'config/database.php';

echo "<h2>Check Notifications Table Structure</h2>";

try {
    $db = getDatabaseConnection();
    
    // Get table structure
    $stmt = $db->prepare("DESCRIBE notifications");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Table Columns:</h3>";
    echo "<table border='1' style='border-collapse: collapse;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test INSERT with different parameter counts
    echo "<h3>Test INSERT Statements:</h3>";
    
    // Test with 4 parameters (original)
    echo "<h4>Test 1: 4 Parameters</h4>";
    try {
        $test1 = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, related_type)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        echo "<p>✅ 4-parameter prepare: SUCCESS</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 4-parameter prepare: " . $e->getMessage() . "</p>";
    }
    
    // Test with 5 parameters
    echo "<h4>Test 2: 5 Parameters</h4>";
    try {
        $test2 = $db->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        echo "<p>✅ 5-parameter prepare: SUCCESS</p>";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ 5-parameter prepare: " . $e->getMessage() . "</p>";
    }
    
    // Check if created_at has default value
    echo "<h3>Check created_at Column:</h3>";
    foreach ($columns as $col) {
        if ($col['Field'] === 'created_at') {
            echo "<p>created_at column: {$col['Type']} (Default: " . ($col['Default'] ?: 'NULL') . ")</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>ERROR: " . $e->getMessage() . "</p>";
}
?>
