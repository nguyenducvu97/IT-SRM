<?php
// Test script to check processing_results values
try {
    $pdo = new PDO('mysql:host=localhost;dbname=it_service_request', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>Processing Results values in database:</h2>";
    $stmt = $pdo->query('SELECT DISTINCT processing_results FROM request_feedback WHERE processing_results IS NOT NULL ORDER BY processing_results');
    $values = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<pre>";
    print_r($values);
    echo "</pre>";
    
    echo "<h2>John Smith (ID: 2) feedback values:</h2>";
    $stmt = $pdo->prepare('
        SELECT sr.id, rf.rating, rf.processing_results 
        FROM service_requests sr 
        LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id 
        WHERE sr.assigned_to = 2 AND sr.status = "resolved"
        LIMIT 5
    ');
    $stmt->execute();
    $feedbacks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Request ID</th><th>Rating</th><th>Processing Results</th></tr>";
    foreach ($feedbacks as $feedback) {
        echo "<tr>";
        echo "<td>{$feedback['id']}</td>";
        echo "<td>{$feedback['rating']}</td>";
        echo "<td>{$feedback['processing_results']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
