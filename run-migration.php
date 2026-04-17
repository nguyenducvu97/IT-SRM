<?php
// Migration script to add estimated_completion column
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Migration: Add Estimated Completion Column</h2>";

try {
    require_once __DIR__ . '/config/database.php';
    $db = getDatabaseConnection();
    
    // Check if column already exists
    $check = $db->prepare("SHOW COLUMNS FROM service_requests LIKE 'estimated_completion'");
    $check->execute();
    $exists = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($exists) {
        echo "✅ Column 'estimated_completion' already exists!<br>";
        return;
    }
    
    echo "🔄 Adding column 'estimated_completion' to service_requests table...<br>";
    
    // Add the column
    $alter = $db->prepare("
        ALTER TABLE service_requests 
        ADD COLUMN estimated_completion DATETIME NULL 
        AFTER assigned_at
    ");
    
    if ($alter->execute()) {
        echo "✅ Column added successfully!<br>";
        
        // Add index for better performance
        $index = $db->prepare("CREATE INDEX idx_estimated_completion ON service_requests(estimated_completion)");
        if ($index->execute()) {
            echo "✅ Index created successfully!<br>";
        } else {
            echo "⚠️ Warning: Could not create index<br>";
        }
        
        echo "<h3>🎉 Migration Complete!</h3>";
        echo "✅ Column 'estimated_completion' is now available<br>";
        echo "✅ Admin can now update estimated completion times<br>";
        
    } else {
        echo "❌ Failed to add column<br>";
        echo "Error info: " . print_r($alter->errorInfo(), true) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Migration Error: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}
?>
