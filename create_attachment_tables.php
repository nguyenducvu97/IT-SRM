<?php
// Script to create all attachment tables
require_once 'config/database.php';

echo "<h2>Creating Attachment Tables</h2>";

try {
    $pdo = getDatabaseConnection();
    echo "<p>✓ Database connection successful</p>";
    
    // Read and execute the SQL file
    $sqlFile = __DIR__ . '/database/create_all_attachment_tables.sql';
    if (file_exists($sqlFile)) {
        $sql = file_get_contents($sqlFile);
        
        // Split SQL into individual statements
        $statements = array_filter(array_map('trim', explode(';', $sql)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^--/', $statement) && !preg_match('/^USE /', $statement) && !preg_match('/SELECT.*message/', $statement)) {
                echo "<p>Executing: " . substr($statement, 0, 50) . "...</p>";
                $pdo->exec($statement);
            }
        }
        
        echo "<p style='color: green; font-weight: bold;'>✓ All attachment tables created successfully!</p>";
        
        // Verify tables were created
        echo "<h3>Verification:</h3>";
        $tables = ['support_request_attachments', 'reject_request_attachments', 'complete_request_attachments'];
        
        foreach ($tables as $table) {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            $exists = $stmt->fetch();
            
            if ($exists) {
                echo "<p>✓ Table '$table' exists</p>";
                
                // Show table structure
                $stmt = $pdo->prepare("DESCRIBE $table");
                $stmt->execute();
                $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo "<table border='1' cellpadding='3' style='margin-left: 20px;'>";
                echo "<tr><th style='background: #f0f0f0;'>Field</th><th style='background: #f0f0f0;'>Type</th></tr>";
                foreach ($columns as $col) {
                    echo "<tr>";
                    echo "<td>{$col['Field']}</td>";
                    echo "<td>{$col['Type']}</td>";
                    echo "</tr>";
                }
                echo "</table><br>";
            } else {
                echo "<p style='color: red;'>✗ Table '$table' was not created</p>";
            }
        }
        
    } else {
        echo "<p style='color: red;'>✗ SQL file not found: $sqlFile</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>✗ Error: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='index.html'>← Back to Application</a></p>";
?>
