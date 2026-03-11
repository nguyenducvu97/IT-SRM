<?php
// Setup notifications table
require_once 'config/database.php';

echo "<h2>Setting up Notifications Table</h2>";

try {
    $database = new Database();
    $pdo = $database->getConnection();
    
    if ($pdo === null) {
        die("Database connection failed");
    }
    
    // Read and execute the SQL file
    $sqlFile = 'database/create_notifications_table.sql';
    if (!file_exists($sqlFile)) {
        die("SQL file not found: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Execute the SQL
    $pdo->exec($sql);
    
    echo "<p style='color: green;'>✅ Notifications table created successfully!</p>";
    
    // Verify table exists
    $stmt = $pdo->prepare("SHOW TABLES LIKE 'notifications'");
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "<p style='color: green;'>✅ Table verification successful!</p>";
        
        // Show table structure
        echo "<h3>Table Structure:</h3>";
        $stmt = $pdo->prepare("DESCRIBE notifications");
        $stmt->execute();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 20px 0;'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['Field']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Null']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Key']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Default']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Extra']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
    } else {
        echo "<p style='color: red;'>❌ Table verification failed!</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='index.html'>Go to IT Service Request System</a></p>";
?>
