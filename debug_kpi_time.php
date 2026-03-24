<?php
// Debug KPI time calculations
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>KPI Time Calculation Debug</h2>";

// Check sample data
$query = "SELECT 
    id, 
    status, 
    created_at, 
    updated_at, 
    resolved_at,
    assigned_to,
    TIMESTAMPDIFF(HOUR, created_at, COALESCE(resolved_at, updated_at, NOW())) as time_diff,
    CASE WHEN status = 'resolved' AND resolved_at IS NOT NULL 
        THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) 
        ELSE NULL END as completion_time,
    CASE WHEN status IN ('in_progress', 'resolved') AND updated_at > created_at
        THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) 
        ELSE NULL END as response_time
FROM service_requests 
ORDER BY id DESC 
LIMIT 5";

$stmt = $db->prepare($query);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Status</th><th>Created</th><th>Updated</th><th>Resolved</th><th>Assigned To</th><th>Time Diff</th><th>Completion</th><th>Response</th></tr>";

foreach ($requests as $req) {
    echo "<tr>";
    echo "<td>" . $req['id'] . "</td>";
    echo "<td>" . $req['status'] . "</td>";
    echo "<td>" . $req['created_at'] . "</td>";
    echo "<td>" . ($req['updated_at'] ?? 'NULL') . "</td>";
    echo "<td>" . ($req['resolved_at'] ?? 'NULL') . "</td>";
    echo "<td>" . $req['assigned_to'] . "</td>";
    echo "<td>" . $req['time_diff'] . "</td>";
    echo "<td>" . ($req['completion_time'] ?? 'NULL') . "</td>";
    echo "<td>" . ($req['response_time'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test current KPI calculation
echo "<h2>Current KPI Calculation Test</h2>";

$stats_query = "SELECT 
    COUNT(*) as total_requests,
    COUNT(CASE WHEN status = 'resolved' THEN 1 END) as completed_requests,
    AVG(CASE WHEN status = 'resolved' AND resolved_at IS NOT NULL 
        THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) 
        ELSE NULL END) as avg_completion_time_hours,
    AVG(CASE WHEN status IN ('in_progress', 'resolved') AND updated_at > created_at
        THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) 
        ELSE NULL END) as avg_response_time_hours
    FROM service_requests 
    WHERE assigned_to IS NOT NULL";

$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute();
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>";
print_r($stats);
echo "</pre>";

?>
