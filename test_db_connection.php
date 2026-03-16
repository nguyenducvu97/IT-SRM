<?php
require_once 'config/database.php';

echo "<h2>Testing Database Connection</h2>";

try {
    $db = getDatabaseConnection();
    echo "<p>✅ Database connection successful</p>";
    
    // Test simple query
    $count_query = "SELECT COUNT(*) as total FROM service_requests";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $total = $count_stmt->fetchColumn();
    
    echo "<p>Total requests: $total</p>";
    
} catch (Exception $e) {
    echo "<p>❌ Database connection failed: " . $e->getMessage() . "</p>";
}
?>
