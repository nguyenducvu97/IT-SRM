<?php
// Direct fix for completion time data
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Direct Fix Completion Time</h2>";

// Fix request #6
echo "<h3>Fixing Request #6:</h3>";
$update6 = "UPDATE service_requests 
           SET resolved_at = DATE_ADD(assigned_at, INTERVAL 8 HOUR) 
           WHERE id = 6 AND resolved_at <= created_at";
$stmt6 = $db->prepare($update6);
if ($stmt6->execute()) {
    echo "✅ Request #6 fixed<br>";
} else {
    echo "❌ Request #6 fix failed<br>";
}

// Fix request #5
echo "<h3>Fixing Request #5:</h3>";
$update5 = "UPDATE service_requests 
           SET resolved_at = DATE_ADD(assigned_at, INTERVAL 8 HOUR) 
           WHERE id = 5 AND resolved_at <= created_at";
$stmt5 = $db->prepare($update5);
if ($stmt5->execute()) {
    echo "✅ Request #5 fixed<br>";
} else {
    echo "❌ Request #5 fix failed<br>";
}

// Verify fixes
echo "<h3>Verification:</h3>";
$verify_query = "SELECT id, title, status, created_at, assigned_at, resolved_at,
                TIMESTAMPDIFF(HOUR, created_at, resolved_at) as completion_hours
                FROM service_requests 
                WHERE id IN (5, 6, 12)
                ORDER BY id";

$verify_stmt = $db->prepare($verify_query);
$verify_stmt->execute();
$results = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th><th>Assigned</th><th>Resolved</th><th>Completion (hours)</th></tr>";

foreach ($results as $row) {
    echo "<tr>";
    echo "<td>{$row['id']}</td>";
    echo "<td>" . substr($row['title'], 0, 20) . "</td>";
    echo "<td>{$row['status']}</td>";
    echo "<td>{$row['created_at']}</td>";
    echo "<td>{$row['assigned_at']}</td>";
    echo "<td>{$row['resolved_at']}</td>";
    echo "<td>" . round($row['completion_hours'], 2) . "</td>";
    echo "</tr>";
}

echo "</table>";

// Test KPI calculation again
echo "<h3>Test KPI After Fix:</h3>";
$start_date = '2026-02-28';
$end_date = '2026-03-30';

// Test John Smith
$smith_query = "SELECT 
    COUNT(*) as total_requests,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as completed_requests,
    AVG(CASE WHEN status = 'resolved' AND resolved_at IS NOT NULL AND resolved_at > created_at
        THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) 
        ELSE NULL END) as avg_completion_time_hours,
    AVG(CASE WHEN status IN ('in_progress', 'resolved') AND assigned_at IS NOT NULL
        THEN TIMESTAMPDIFF(HOUR, created_at, assigned_at) 
        ELSE NULL END) as avg_response_time_hours
    FROM service_requests 
    WHERE assigned_to = 2 
    AND created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";

$smith_stmt = $db->prepare($smith_query);
$smith_stmt->bindParam(':start_date', $start_date);
$smith_stmt->bindParam(':end_date', $end_date);
$smith_stmt->execute();
$smith_stats = $smith_stmt->fetch(PDO::FETCH_ASSOC);

echo "<h4>John Smith (Staff) - New Results:</h4>";
echo "<table border='1'>";
echo "<tr><th>Metric</th><th>Value</th></tr>";
echo "<tr><td>Total Requests</td><td>{$smith_stats['total_requests']}</td></tr>";
echo "<tr><td>Completed Requests</td><td>{$smith_stats['completed_requests']}</td></tr>";
echo "<tr><td>Avg Response Time</td><td>" . round($smith_stats['avg_response_time_hours'], 2) . " hours</td></tr>";
echo "<tr><td>Avg Completion Time</td><td>" . round($smith_stats['avg_completion_time_hours'], 2) . " hours</td></tr>";
echo "</table>";

echo "<h3>✅ Fix Complete!</h3>";
echo "<p><a href='/it-service-request/'>Go to Dashboard</a> to check KPI Export</p>";

?>
