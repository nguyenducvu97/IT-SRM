<?php
require_once 'config/database.php';

try {
    $db = (new Database())->getConnection();
    $query = "SELECT id, title, status, assigned_to FROM service_requests WHERE status = 'open' AND (assigned_to IS NULL OR assigned_to = 0) ORDER BY id DESC LIMIT 5";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h2>Open Requests Available for Testing</h2>";
    if (empty($requests)) {
        echo "<p>No open requests found</p>";
    } else {
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned</th></tr>";
        foreach ($requests as $req) {
            echo "<tr>";
            echo "<td>{$req['id']}</td>";
            echo "<td>" . htmlspecialchars($req['title']) . "</td>";
            echo "<td>{$req['status']}</td>";
            echo "<td>" . ($req['assigned_to'] ? $req['assigned_to'] : 'None') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
    // Also show total counts
    $count_query = "SELECT status, COUNT(*) as count FROM service_requests GROUP BY status";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $counts = $count_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Request Status Summary</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Status</th><th>Count</th></tr>";
    foreach ($counts as $count) {
        echo "<tr>";
        echo "<td>{$count['status']}</td>";
        echo "<td>{$count['count']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}
?>
