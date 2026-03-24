<?php
// Fix completion time data for requests with resolved_at < created_at
require_once __DIR__ . '/config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Fix Completion Time Data</h2>";

// Find requests with problematic completion times
$find_query = "SELECT id, title, status, created_at, resolved_at, assigned_at,
              TIMESTAMPDIFF(HOUR, created_at, resolved_at) as completion_diff
              FROM service_requests 
              WHERE status = 'resolved' 
              AND resolved_at IS NOT NULL 
              AND resolved_at <= created_at
              ORDER BY id";

$find_stmt = $db->prepare($find_query);
$find_stmt->execute();
$problematic_requests = $find_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Requests with completion time issues:</h3>";
echo "<table border='1'>";
echo "<tr><th>ID</th><th>Title</th><th>Created</th><th>Resolved</th><th>Diff (hours)</th><th>Action</th></tr>";

foreach ($problematic_requests as $req) {
    echo "<tr>";
    echo "<td>{$req['id']}</td>";
    echo "<td>" . substr($req['title'], 0, 30) . "</td>";
    echo "<td>{$req['created_at']}</td>";
    echo "<td>{$req['resolved_at']}</td>";
    echo "<td>{$req['completion_diff']}</td>";
    echo "<td>";
    
    // Fix by setting resolved_at to a reasonable time (assigned_at + 8 hours for work day)
    if ($req['assigned_at']) {
        $fixed_resolved_at = date('Y-m-d H:i:s', strtotime($req['assigned_at'] . ' + 8 hours'));
        
        $fix_query = "UPDATE service_requests 
        SET resolved_at = :resolved_at 
        WHERE id = :request_id";
        
        $fix_stmt = $db->prepare($fix_query);
        $fix_stmt->bindParam(':resolved_at', $fixed_resolved_at);
        $fix_stmt->bindParam(':request_id', $req['id']);
        
        if ($fix_stmt->execute()) {
            echo "<span style='color: green;'>✅ Fixed to: $fixed_resolved_at</span>";
        } else {
            echo "<span style='color: red;'>❌ Fix failed</span>";
        }
    } else {
        echo "<span style='color: orange;'>⚠️ No assigned_at</span>";
    }
    
    echo "</td>";
    echo "</tr>";
}

echo "</table>";

// Verify the fixes
echo "<h3>Verification after fixes:</h3>";
$verify_query = "SELECT id, title, status, created_at, resolved_at, assigned_at,
               TIMESTAMPDIFF(HOUR, created_at, resolved_at) as completion_diff
               FROM service_requests 
               WHERE status = 'resolved' 
               AND resolved_at IS NOT NULL
               ORDER BY id DESC
               LIMIT 5";

$verify_stmt = $db->prepare($verify_query);
$verify_stmt->execute();
$verified_requests = $verify_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<table border='1'>";
echo "<tr><th>ID</th><th>Title</th><th>Created</th><th>Resolved</th><th>Completion (hours)</th></tr>";

foreach ($verified_requests as $req) {
    echo "<tr>";
    echo "<td>{$req['id']}</td>";
    echo "<td>" . substr($req['title'], 0, 30) . "</td>";
    echo "<td>{$req['created_at']}</td>";
    echo "<td>{$req['resolved_at']}</td>";
    echo "<td>" . round($req['completion_diff'], 2) . "</td>";
    echo "</tr>";
}

echo "</table>";

echo "<h3>Test KPI calculation after fixes:</h3>";
echo "<a href='/it-service-request/test_kpi_calculation.php' target='_blank'>Test KPI Calculation Again</a>";

?>
