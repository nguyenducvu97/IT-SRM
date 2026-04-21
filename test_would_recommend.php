<?php
// Test script to check would_recommend values
try {
    $pdo = new PDO('mysql:host=localhost;dbname=it_service_request', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Would Recommend values in database:</h2>";
    $stmt = $pdo->query('SELECT DISTINCT would_recommend FROM request_feedback WHERE would_recommend IS NOT NULL ORDER BY would_recommend');
    $values = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<pre>";
    print_r($values);
    echo "</pre>";
    
    echo "<h2>John Smith (ID: 2) feedback values:</h2>";
    $stmt = $pdo->prepare('
        SELECT sr.id, rf.rating, rf.would_recommend 
        FROM service_requests sr 
        LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id 
        WHERE sr.assigned_to = 2 AND sr.status = "resolved"
        LIMIT 5
    ');
    $stmt->execute();
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Request ID</th><th>Rating</th><th>Would Recommend</th></tr>";
    foreach ($feedbacks as $feedback) {
        echo "<tr>";
        echo "<td>{$feedback['id']}</td>";
        echo "<td>{$feedback['rating']}</td>";
        echo "<td>{$feedback['would_recommend']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
