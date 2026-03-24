<?php
// Debug KPI query
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>KPI Query Debug</h2>";

// Test date range
$start_date = '2026-02-28';
$end_date = '2026-03-30';

echo "Date Range: $start_date to $end_date<br>";

// Test staff query
$staff_query = "SELECT id, username, email, full_name, department 
               FROM users 
               WHERE role IN ('admin', 'staff') 
               ORDER BY full_name";
$staff_stmt = $db->prepare($staff_query);
$staff_stmt->execute();
$staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Staff List:</h3>";
foreach ($staff_list as $staff) {
    echo "- ID: " . $staff['id'] . ", Name: " . $staff['full_name'] . ", Role: " . $staff['department'] . "<br>";
    
    // Test stats query for each staff
    $stats_query = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as completed_requests,
        SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_requests,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_requests,
        AVG(CASE WHEN status = 'resolved' AND resolved_at IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) 
            ELSE NULL END) as avg_completion_time_hours,
        AVG(CASE WHEN status IN ('in_progress', 'resolved') AND updated_at IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) 
            ELSE NULL END) as avg_response_time_hours
        FROM service_requests 
        WHERE assigned_to = :staff_id 
        AND created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
    
    $stats_stmt = $db->prepare($stats_query);
    $stats_stmt->bindParam(':staff_id', $staff['id']);
    $stats_stmt->bindParam(':start_date', $start_date);
    $stats_stmt->bindParam(':end_date', $end_date);
    $stats_stmt->execute();
    $stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "  Stats: " . json_encode($stats) . "<br>";
}

// Test direct query for admin (ID=1)
echo "<h3>Direct Test for Admin (ID=1):</h3>";
$direct_query = "SELECT 
    sr.id, sr.status, sr.created_at, sr.updated_at, sr.resolved_at, sr.assigned_to,
    TIMESTAMPDIFF(HOUR, sr.created_at, sr.updated_at) as response_time,
    TIMESTAMPDIFF(HOUR, sr.created_at, sr.resolved_at) as completion_time
FROM service_requests sr 
WHERE sr.assigned_to = 1 
AND sr.created_at BETWEEN '2026-02-28' AND '2026-03-30 23:59:59'
ORDER BY sr.id DESC";

$direct_stmt = $db->prepare($direct_query);
$direct_stmt->execute();
$direct_results = $direct_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Status</th><th>Created</th><th>Updated</th><th>Resolved</th><th>Response</th><th>Completion</th></tr>";

foreach ($direct_results as $row) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['status'] . "</td>";
    echo "<td>" . $row['created_at'] . "</td>";
    echo "<td>" . $row['updated_at'] . "</td>";
    echo "<td>" . ($row['resolved_at'] ?? 'NULL') . "</td>";
    echo "<td>" . $row['response_time'] . "</td>";
    echo "<td>" . ($row['completion_time'] ?? 'NULL') . "</td>";
    echo "</tr>";
}

echo "</table>";

?>
