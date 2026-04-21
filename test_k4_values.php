<?php
// Check all feedback values for John Smith
try {
    $pdo = new PDO('mysql:host=localhost;dbname=it_service_request', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "<h2>All feedback values in database:</h2>";
    $stmt = $pdo->query('SELECT would_recommend, COUNT(*) as count FROM request_feedback GROUP BY would_recommend ORDER BY would_recommend');
    $values = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Would Recommend</th><th>Count</th></tr>";
    foreach ($values as $value) {
        echo "<tr><td>{$value['would_recommend']}</td><td>{$value['count']}</td></tr>";
    }
    echo "</table>";
    
    echo "<h2>Calculate K4 for each value:</h2>";
    echo "<table border='1'>";
    echo "<tr><th>Would Recommend</th><th>K4 Score</th><th>Description</th></tr>";
    
    $test_values = [1, 2, 3, 4, 5];
    foreach ($test_values as $value) {
        $k4_score = 1;
        if ($value === 'yes' || $value === '1' || $value == 1 || $value == 5) {
            $k4_score = 5;
            $desc = "Rất hài lòng (5 điểm)";
        } elseif ($value === 'maybe' || $value === '2' || $value == 2 || $value == 4) {
            $k4_score = 4;
            $desc = "Hài lòng (4 điểm)";
        } elseif ($value == 3) {
            $k4_score = 3;
            $desc = "Bình thường (3 điểm)";
        } elseif ($value === 'no' || $value === '0' || $value == 0) {
            $k4_score = 1;
            $desc = "Rất không hài lòng (1 điểm)";
        } else {
            $desc = "Unknown";
        }
        
        echo "<tr><td>$value</td><td>$k4_score</td><td>$desc</td></tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
?>
