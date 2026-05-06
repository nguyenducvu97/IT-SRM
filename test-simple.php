<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== SIMPLE TEST ===\n";

try {
    require_once 'config/database.php';
    echo "✅ Database config loaded\n";
    
    $stmt = $db->query("SELECT 1");
    echo "✅ Database connected\n";
    
    $stmt = $db->query("SHOW TABLES LIKE 'sessions'");
    $table = $stmt->fetch();
    
    if ($table) {
        echo "✅ Sessions table exists\n";
        
        $stmt = $db->query("SELECT COUNT(*) as total FROM sessions");
        $count = $stmt->fetch();
        echo "Total sessions: " . $count['total'] . "\n";
    } else {
        echo "❌ Sessions table missing\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
