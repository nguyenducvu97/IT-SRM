<?php
// Check and fix KPI data
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Fix KPI Data</h2>";

// Check users table for staff
$users_query = "SELECT id, username, role FROM users WHERE role IN ('admin', 'staff')";
$users_stmt = $db->prepare($users_query);
$users_stmt->execute();
$users = $users_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Available Staff:</h3>";
foreach ($users as $user) {
    echo "- ID: " . $user['id'] . ", Username: " . $user['username'] . ", Role: " . $user['role'] . "<br>";
}

// Update some requests to have assigned_to
if (!empty($users)) {
    $staff_id = $users[0]['id'];
    
    echo "<h3>Updating requests to assign staff...</h3>";
    
    // Update requests without assigned_to
    $update_query = "UPDATE service_requests 
    SET assigned_to = :staff_id, updated_at = DATE_ADD(created_at, INTERVAL 2 HOUR)
    WHERE assigned_to IS NULL 
    ORDER BY id DESC 
    LIMIT 3";
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':staff_id', $staff_id);
    $result = $update_stmt->execute();
    
    echo "Updated " . $update_stmt->rowCount() . " requests<br>";
    
    // Update one request to resolved
    $resolve_query = "UPDATE service_requests 
    SET status = 'resolved', resolved_at = DATE_ADD(created_at, INTERVAL 24 HOUR), updated_at = DATE_ADD(created_at, INTERVAL 24 HOUR)
    WHERE assigned_to = :staff_id 
    ORDER BY id DESC 
    LIMIT 1";
    
    $resolve_stmt = $db->prepare($resolve_query);
    $resolve_stmt->bindParam(':staff_id', $staff_id);
    $resolve_stmt->execute();
    
    echo "Resolved " . $resolve_stmt->rowCount() . " request<br>";
    
    // Update one request to in_progress
    $progress_query = "UPDATE service_requests 
    SET status = 'in_progress', updated_at = DATE_ADD(created_at, INTERVAL 4 HOUR)
    WHERE assigned_to = :staff_id AND status = 'open'
    ORDER BY id DESC 
    LIMIT 1";
    
    $progress_stmt = $db->prepare($progress_query);
    $progress_stmt->bindParam(':staff_id', $staff_id);
    $progress_stmt->execute();
    
    echo "Set to in_progress: " . $progress_stmt->rowCount() . " request<br>";
}

// Check updated data
echo "<h3>Updated Requests:</h3>";
$check_query = "SELECT 
    id, 
    status, 
    created_at, 
    updated_at, 
    resolved_at,
    assigned_to,
    TIMESTAMPDIFF(HOUR, created_at, COALESCE(resolved_at, updated_at, NOW())) as time_diff
FROM service_requests 
WHERE assigned_to IS NOT NULL
ORDER BY id DESC 
LIMIT 5";

$check_stmt = $db->prepare($check_query);
$check_stmt->execute();
$requests = $check_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Status</th><th>Created</th><th>Updated</th><th>Resolved</th><th>Assigned To</th><th>Time Diff</th></tr>";

foreach ($requests as $req) {
    echo "<tr>";
    echo "<td>" . $req['id'] . "</td>";
    echo "<td>" . $req['status'] . "</td>";
    echo "<td>" . $req['created_at'] . "</td>";
    echo "<td>" . ($req['updated_at'] ?? 'NULL') . "</td>";
    echo "<td>" . ($req['resolved_at'] ?? 'NULL') . "</td>";
    echo "<td>" . $req['assigned_to'] . "</td>";
    echo "<td>" . $req['time_diff'] . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<p><a href='debug_kpi_time.php'>Test KPI calculation again</a></p>";

?>
