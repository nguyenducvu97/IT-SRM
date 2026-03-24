<?php
// Debug KPI data - check actual database values
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>KPI Debug - Check Service Requests Data</h2>";

// Check a few sample requests
$query = "SELECT id, title, status, assigned_to, created_at, updated_at, resolved_at 
          FROM service_requests 
          WHERE assigned_to IS NOT NULL 
          ORDER BY created_at DESC 
          LIMIT 10";

$stmt = $db->prepare($query);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Sample Service Requests:</h3>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Assigned To</th><th>Created</th><th>Updated</th><th>Resolved</th><th>Time Diff</th></tr>";

foreach ($requests as $req) {
    $time_diff = '';
    if ($req['resolved_at']) {
        $created = new DateTime($req['created_at']);
        $resolved = new DateTime($req['resolved_at']);
        $diff = $created->diff($resolved);
        $time_diff = $diff->h . 'h ' . $diff->i . 'm';
    }
    
    echo "<tr>";
    echo "<td>{$req['id']}</td>";
    echo "<td>" . substr($req['title'], 0, 30) . "...</td>";
    echo "<td>{$req['status']}</td>";
    echo "<td>{$req['assigned_to']}</td>";
    echo "<td>{$req['created_at']}</td>";
    echo "<td>{$req['updated_at']}</td>";
    echo "<td>{$req['resolved_at']}</td>";
    echo "<td>{$time_diff}</td>";
    echo "</tr>";
}
echo "</table>";

// Check status distribution
$status_query = "SELECT status, COUNT(*) as count FROM service_requests WHERE assigned_to IS NOT NULL GROUP BY status";
$status_stmt = $db->prepare($status_query);
$status_stmt->execute();
$statuses = $status_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Status Distribution:</h3>";
foreach ($statuses as $status) {
    echo "<p>{$status['status']}: {$status['count']} requests</p>";
}

// Check resolved_at values
$resolved_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN resolved_at IS NOT NULL THEN 1 ELSE 0 END) as has_resolved_at,
    SUM(CASE WHEN resolved_at IS NULL THEN 1 ELSE 0 END) as missing_resolved_at
    FROM service_requests 
    WHERE status = 'resolved'";

$resolved_stmt = $db->prepare($resolved_query);
$resolved_stmt->execute();
$resolved_stats = $resolved_stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Resolved Requests - resolved_at Analysis:</h3>";
echo "<p>Total resolved: {$resolved_stats['total']}</p>";
echo "<p>Has resolved_at: {$resolved_stats['has_resolved_at']}</p>";
echo "<p>Missing resolved_at: {$resolved_stats['missing_resolved_at']}</p>";

?>
