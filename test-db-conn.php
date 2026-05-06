<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db) {
        echo "✅ Database connection SUCCESSFUL\n";
        
        // Test query
        $stmt = $db->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Users count: " . $result['count'] . "\n";
        
        // Test sessions table
        $stmt = $db->query("SELECT COUNT(*) as count FROM sessions");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "Sessions count: " . $result['count'] . "\n";
        
    } else {
        echo "❌ Database connection FAILED\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
