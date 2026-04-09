<?php
echo "<h2>Simple Database Test</h2>";

$start_time = microtime(true);

try {
    require_once 'config/database.php';
    $db = getDatabaseConnection();
    
    if ($db) {
        echo "<p style='color: green;'>Database connected successfully</p>";
        
        // Test simple query
        $query = "SELECT COUNT(*) as total FROM service_requests";
        $stmt = $db->prepare($query);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Total requests: " . $result['total'] . "</p>";
        
        // Test insert
        $insert_query = "INSERT INTO service_requests 
                        (user_id, category_id, title, description, priority, status, created_at, updated_at)
                        VALUES (4, 1, 'Speed Test', 'Speed test description', 'medium', 'open', NOW(), NOW())";
        
        $insert_stmt = $db->prepare($insert_query);
        
        if ($insert_stmt->execute()) {
            $id = $db->lastInsertId();
            echo "<p style='color: green;'>Test request created: ID $id</p>";
        } else {
            echo "<p style='color: red;'>Failed to create test request</p>";
        }
        
    } else {
        echo "<p style='color: red;'>Database connection failed</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

$end_time = microtime(true);
$execution_time = ($end_time - $start_time) * 1000;

echo "<h3>Performance:</h3>";
echo "<p>Execution time: " . number_format($execution_time, 2) . " ms</p>";

if ($execution_time < 100) {
    echo "<p style='color: green;'>Database operations are fast!</p>";
} else {
    echo "<p style='color: orange;'>Database operations are slow</p>";
}
?>
