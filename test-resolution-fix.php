<?php
// Test script to verify resolution data display for closed requests
require_once 'config/database.php';
require_once 'config/session.php';

// Start session
session_start();

// Database connection
$database = new Database();
$db = $database->getConnection();

// Test query to get a closed request with resolution data
$query = "SELECT sr.id, sr.title, sr.status, 
                sr.error_description as resolution_error_description,
                sr.error_type as resolution_error_type, sr.replacement_materials as resolution_replacement_materials,
                sr.solution_method as resolution_solution_method, 
                sr.resolved_at as resolution_resolved_at, assigned.full_name as resolver_name,
                res.resolved_by as resolution_resolved_by, res.error_description as res_error_description,
                res.error_type as res_error_type, res.replacement_materials as res_replacement_materials,
                res.solution_method as res_solution_method, res.resolved_at as res_resolved_at,
                resolver.full_name as resolution_resolver_name
         FROM service_requests sr
         LEFT JOIN users assigned ON sr.assigned_to = assigned.id
         LEFT JOIN resolutions res ON sr.id = res.service_request_id
         LEFT JOIN users resolver ON res.resolved_by = resolver.id
         WHERE sr.status = 'closed' AND res.service_request_id IS NOT NULL
         LIMIT 5";

$stmt = $db->prepare($query);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h2>Test Resolution Data for Closed Requests</h2>";

if (empty($requests)) {
    echo "<p>No closed requests with resolution data found.</p>";
} else {
    foreach ($requests as $request) {
        echo "<div style='border: 1px solid #ccc; margin: 10px; padding: 10px;'>";
        echo "<h3>Request #{$request['id']} - {$request['title']}</h3>";
        echo "<p><strong>Status:</strong> {$request['status']}</p>";
        
        // Test the resolution formatting logic
        if (($request['status'] === 'resolved' || $request['status'] === 'closed') && $request['resolution_resolver_name']) {
            $resolution = [
                'resolver_name' => $request['resolution_resolver_name'],
                'error_description' => $request['res_error_description'],
                'error_type' => $request['res_error_type'],
                'replacement_materials' => $request['res_replacement_materials'],
                'solution_method' => $request['res_solution_method'],
                'resolved_at' => $request['res_resolved_at']
            ];
            
            echo "<div style='background: #f0f8ff; padding: 10px; margin: 10px 0;'>";
            echo "<h4>✅ Resolution Data Found:</h4>";
            echo "<p><strong>Resolver:</strong> {$resolution['resolver_name']}</p>";
            echo "<p><strong>Resolved At:</strong> {$resolution['resolved_at']}</p>";
            echo "<p><strong>Error Description:</strong> {$resolution['error_description']}</p>";
            echo "<p><strong>Solution Method:</strong> {$resolution['solution_method']}</p>";
            echo "</div>";
        } else {
            echo "<div style='background: #ffe0e0; padding: 10px; margin: 10px 0;'>";
            echo "<h4>❌ No Resolution Data Found</h4>";
            echo "<p>Status: {$request['status']}</p>";
            echo "<p>Resolver Name: " . ($request['resolution_resolver_name'] ?? 'NULL') . "</p>";
            echo "</div>";
        }
        
        echo "</div>";
    }
}

echo "<hr>";
echo "<h3>Database Check:</h3>";

// Check total closed requests
$count_query = "SELECT COUNT(*) as total FROM service_requests WHERE status = 'closed'";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$total_closed = $count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "<p>Total closed requests: {$total_closed}</p>";

// Check closed requests with resolutions
$res_count_query = "SELECT COUNT(*) as total 
                    FROM service_requests sr 
                    JOIN resolutions res ON sr.id = res.service_request_id 
                    WHERE sr.status = 'closed'";
$res_count_stmt = $db->prepare($res_count_query);
$res_count_stmt->execute();
$total_closed_with_res = $res_count_stmt->fetch(PDO::FETCH_ASSOC)['total'];
echo "<p>Closed requests with resolution data: {$total_closed_with_res}</p>";
?>
