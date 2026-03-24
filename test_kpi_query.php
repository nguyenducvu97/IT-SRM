<?php
// Simple test for KPI export API
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>KPI Export API Test</h2>";

try {
    require_once __DIR__ . '/../config/database.php';
    require_once __DIR__ . '/../config/session.php';
    
    echo "✅ Files loaded successfully<br>";
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "❌ Database connection failed<br>";
        exit;
    }
    
    echo "✅ Database connected<br>";
    
    // Test simple query
    $query = "SELECT COUNT(*) as total FROM users WHERE role = 'staff'";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "✅ Query executed successfully<br>";
    echo "Total staff: " . $result['total'] . "<br>";
    
    // Test the problematic query
    echo "<h3>Testing KPI Query:</h3>";
    
    $staff_id = 2; // Test with staff ID 2
    $start_date = '2026-02-28';
    $end_date = '2026-03-30';
    
    $stats_query = "SELECT 
        sr.id,
        sr.status,
        sr.created_at,
        sr.updated_at,
        sr.resolved_at,
        TIMESTAMPDIFF(HOUR, sr.created_at, sr.updated_at) as total_hours,
        CASE 
            WHEN sr.resolved_at IS NOT NULL THEN TIMESTAMPDIFF(HOUR, sr.created_at, sr.resolved_at)
            WHEN sr.status = 'resolved' THEN TIMESTAMPDIFF(HOUR, sr.created_at, sr.updated_at)
            ELSE NULL 
        END as completion_time_hours,
        CASE 
            WHEN sr.status IN ('in_progress', 'resolved') AND TIMESTAMPDIFF(HOUR, sr.created_at, sr.updated_at) > 0
            THEN TIMESTAMPDIFF(HOUR, sr.created_at, sr.updated_at)
            WHEN sr.status IN ('in_progress', 'resolved') THEN 0.5
            ELSE NULL 
        END as response_time_hours
        FROM service_requests sr
        WHERE sr.assigned_to = :staff_id 
        AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
    
    echo "Query: " . htmlspecialchars($stats_query) . "<br><br>";
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->bindParam(':staff_id', $staff_id);
    $stats_stmt->bindParam(':start_date', $start_date);
    $stats_stmt->bindParam(':end_date', $end_date);
    
    if ($stats_stmt->execute()) {
        echo "✅ KPI Query executed successfully<br>";
        $all_requests = $stats_stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "Found " . count($all_requests) . " requests<br>";
        
        if (!empty($all_requests)) {
            echo "<table border='1'>";
            echo "<tr><th>ID</th><th>Status</th><th>Total Hours</th><th>Completion</th><th>Response</th></tr>";
            foreach ($all_requests as $req) {
                echo "<tr>";
                echo "<td>{$req['id']}</td>";
                echo "<td>{$req['status']}</td>";
                echo "<td>{$req['total_hours']}</td>";
                echo "<td>{$req['completion_time_hours']}</td>";
                echo "<td>{$req['response_time_hours']}</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
    } else {
        echo "❌ KPI Query failed<br>";
        echo "Error: " . print_r($stats_stmt->errorInfo(), true) . "<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "<br>";
    echo "Stack trace: <pre>" . $e->getTraceAsString() . "</pre>";
}

?>
