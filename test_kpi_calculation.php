<?php
// Test KPI calculations with real data
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Test KPI Calculations</h2>";

// Test date range
$start_date = '2026-02-28';
$end_date = '2026-03-30';

echo "<h3>Date Range: $start_date to $end_date</h3>";

// Get all staff with requests
$staff_query = "SELECT DISTINCT u.id, u.username, u.full_name, u.department
               FROM users u 
               JOIN service_requests sr ON u.id = sr.assigned_to 
               WHERE u.role IN ('admin', 'staff')
               AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
               ORDER BY u.full_name";

$staff_stmt = $db->prepare($staff_query);
$staff_stmt->bindParam(':start_date', $start_date);
$staff_stmt->bindParam(':end_date', $end_date);
$staff_stmt->execute();
$staff_list = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Staff KPI Calculations:</h3>";

foreach ($staff_list as $staff) {
    $staff_id = $staff['id'];
    
    echo "<h4>Staff: {$staff['full_name']} (ID: $staff_id)</h4>";
    
    // Original query (using updated_at)
    $original_query = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as completed_requests,
        AVG(CASE WHEN status = 'resolved' AND resolved_at IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) 
            ELSE NULL END) as avg_completion_time_hours,
        AVG(CASE WHEN status IN ('in_progress', 'resolved') AND updated_at IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, created_at, updated_at) 
            ELSE NULL END) as avg_response_time_hours_old
        FROM service_requests 
        WHERE assigned_to = :staff_id 
        AND created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
    
    $original_stmt = $db->prepare($original_query);
    $original_stmt->bindParam(':staff_id', $staff_id);
    $original_stmt->bindParam(':start_date', $start_date);
    $original_stmt->bindParam(':end_date', $end_date);
    $original_stmt->execute();
    $original_stats = $original_stmt->fetch(PDO::FETCH_ASSOC);
    
    // New query (using assigned_at)
    $new_query = "SELECT 
        COUNT(*) as total_requests,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as completed_requests,
        AVG(CASE WHEN status = 'resolved' AND resolved_at IS NOT NULL 
            THEN TIMESTAMPDIFF(HOUR, created_at, resolved_at) 
            ELSE NULL END) as avg_completion_time_hours,
        AVG(CASE WHEN status IN ('in_progress', 'resolved') AND assigned_at IS NOT NULL
            THEN TIMESTAMPDIFF(HOUR, created_at, assigned_at) 
            ELSE NULL END) as avg_response_time_hours_new
        FROM service_requests 
        WHERE assigned_to = :staff_id 
        AND created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
    
    $new_stmt = $db->prepare($new_query);
    $new_stmt->bindParam(':staff_id', $staff_id);
    $new_stmt->bindParam(':start_date', $start_date);
    $new_stmt->bindParam(':end_date', $end_date);
    $new_stmt->execute();
    $new_stats = $new_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<table border='1'>";
    echo "<tr><th>Metric</th><th>Old (updated_at)</th><th>New (assigned_at)</th><th>Difference</th></tr>";
    echo "<tr><td>Total Requests</td><td>{$original_stats['total_requests']}</td><td>{$new_stats['total_requests']}</td><td>-</td></tr>";
    echo "<tr><td>Completed Requests</td><td>{$original_stats['completed_requests']}</td><td>{$new_stats['completed_requests']}</td><td>-</td></tr>";
    echo "<tr><td>Avg Completion Time</td><td>" . round($original_stats['avg_completion_time_hours'], 2) . "h</td><td>" . round($new_stats['avg_completion_time_hours'], 2) . "h</td><td>-</td></tr>";
    echo "<tr><td>Avg Response Time</td><td>" . round($original_stats['avg_response_time_hours_old'], 2) . "h</td><td>" . round($new_stats['avg_response_time_hours_new'], 2) . "h</td><td>" . round($new_stats['avg_response_time_hours_new'] - $original_stats['avg_response_time_hours_old'], 2) . "h</td></tr>";
    echo "</table>";
    
    // Show individual requests for this staff
    echo "<h5>Individual Requests:</h5>";
    $individual_query = "SELECT 
        id, title, status, created_at, assigned_at, updated_at, resolved_at,
        TIMESTAMPDIFF(HOUR, created_at, assigned_at) as response_time_assigned,
        TIMESTAMPDIFF(HOUR, created_at, updated_at) as response_time_updated,
        TIMESTAMPDIFF(HOUR, created_at, resolved_at) as completion_time
    FROM service_requests 
    WHERE assigned_to = :staff_id 
    AND created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
    ORDER BY id DESC";
    
    $individual_stmt = $db->prepare($individual_query);
    $individual_stmt->bindParam(':staff_id', $staff_id);
    $individual_stmt->bindParam(':start_date', $start_date);
    $individual_stmt->bindParam(':end_date', $end_date);
    $individual_stmt->execute();
    $requests = $individual_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<table border='1' style='font-size: 0.9em;'>";
    echo "<tr><th>ID</th><th>Title</th><th>Status</th><th>Created</th><th>Assigned</th><th>Updated</th><th>Resolved</th><th>Response (A)</th><th>Response (U)</th><th>Completion</th></tr>";
    
    foreach ($requests as $req) {
        echo "<tr>";
        echo "<td>{$req['id']}</td>";
        echo "<td>" . substr($req['title'], 0, 30) . "</td>";
        echo "<td>{$req['status']}</td>";
        echo "<td>{$req['created_at']}</td>";
        echo "<td>" . ($req['assigned_at'] ?? 'NULL') . "</td>";
        echo "<td>" . ($req['updated_at'] ?? 'NULL') . "</td>";
        echo "<td>" . ($req['resolved_at'] ?? 'NULL') . "</td>";
        echo "<td>" . ($req['response_time_assigned'] ?? 'NULL') . "</td>";
        echo "<td>" . ($req['response_time_updated'] ?? 'NULL') . "</td>";
        echo "<td>" . ($req['completion_time'] ?? 'NULL') . "</td>";
        echo "</tr>";
    }
    
    echo "</table><br>";
}

echo "<h3>Summary:</h3>";
echo "<p><strong>Response Time Calculation:</strong></p>";
echo "<ul>";
echo "<li><strong>Old method:</strong> TIMESTAMPDIFF(HOUR, created_at, updated_at)</li>";
echo "<li><strong>New method:</strong> TIMESTAMPDIFF(HOUR, created_at, assigned_at)</li>";
echo "<li><strong>Why change:</strong> assigned_at represents when staff actually accepted the request</li>";
echo "</ul>";

?>
