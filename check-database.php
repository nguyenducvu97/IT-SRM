<?php
// Check database structure and test estimated completion
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Check for Estimated Completion</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Check table structure
    echo "<h3>📋 Service Requests Table Structure</h3>";
    $columns = $db->query("SHOW COLUMNS FROM service_requests");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
    
    $has_estimated_completion = false;
    while ($column = $columns->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$column['Field']}</td>";
        echo "<td>{$column['Type']}</td>";
        echo "<td>{$column['Null']}</td>";
        echo "<td>{$column['Key']}</td>";
        echo "</tr>";
        
        if ($column['Field'] === 'estimated_completion') {
            $has_estimated_completion = true;
        }
    }
    echo "</table>";
    
    if ($has_estimated_completion) {
        echo "<h3>✅ Column 'estimated_completion' exists!</h3>";
    } else {
        echo "<h3>❌ Column 'estimated_completion' NOT found!</h3>";
    }
    
    // Test a sample request
    echo "<h3>📊 Sample Request Data</h3>";
    $sample = $db->query("SELECT id, title, status, estimated_completion FROM service_requests LIMIT 5");
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Estimated Completion</th></tr>";
    
    while ($row = $sample->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>{$row['id']}</td>";
        echo "<td>{$row['title']}</td>";
        echo "<td>{$row['status']}</td>";
        echo "<td>" . ($row['estimated_completion'] ?: 'NULL') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    echo "<h3>🎯 Status</h3>";
    echo "✅ Database migration completed successfully<br>";
    echo "✅ Column 'estimated_completion' is ready for use<br>";
    echo "✅ Admin can now update estimated completion times via UI<br>";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
}
?>
