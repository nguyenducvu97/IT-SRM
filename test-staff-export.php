<?php
// Simple test for staff export
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/session.php';

// Mock admin session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'Admin';
$_SESSION['role'] = 'admin';

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    die("Database connection failed");
}

// Test getStaffDetailedKPI function
$staff_id = 2;
$start_date = '2026-03-31';
$end_date = '2026-04-29';

echo "Testing getStaffDetailedKPI for staff_id: $staff_id<br>";

try {
    // Include the function
    function getStaffDetailedKPI($db, $staff_id, $start_date, $end_date) {
        error_log("getStaffDetailedKPI called with staff_id: $staff_id, start: $start_date, end: $end_date");
        
        // Get staff details
        $staff_query = "SELECT id, username, email, full_name, department 
                       FROM users WHERE id = :staff_id";
        $staff_stmt = $db->prepare($staff_query);
        $staff_stmt->bindParam(':staff_id', $staff_id);
        $staff_stmt->execute();
        $staff = $staff_stmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Staff query result: " . print_r($staff, true));
        
        if (!$staff) {
            error_log("Staff not found for ID: $staff_id");
            return null;
        }
        
        // Get all requests for this staff
        $requests_query = "SELECT sr.*, c.name as category_name, rf.rating, rf.would_recommend
                          FROM service_requests sr
                          LEFT JOIN categories c ON sr.category_id = c.id
                          LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id
                          WHERE sr.assigned_to = :staff_id 
                          AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
                          ORDER BY sr.created_at DESC";
        
        $requests_stmt = $db->prepare($requests_query);
        $requests_stmt->bindParam(':staff_id', $staff_id);
        $requests_stmt->bindParam(':start_date', $start_date);
        $requests_stmt->bindParam(':end_date', $end_date);
        $requests_stmt->execute();
        $requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);
        
        error_log("Requests query result count: " . count($requests));
        
        $staff_requests = [];
        $k1_scores = [];
        $k2_scores = [];
        $k3_scores = [];
        $k4_scores = [];
        $kpi_scores = [];
        
        foreach ($requests as $request) {
            // Simplified processing for testing
            $staff_requests[] = [
                'id' => $request['id'],
                'title' => $request['title'],
                'category_name' => $request['category_name'],
                'description' => $request['description'],
                'created_at' => $request['created_at'],
                'assigned_at' => $request['assigned_at'],
                'resolved_at' => $request['resolved_at'],
                'estimated_completion' => $request['estimated_completion'],
                'response_time_minutes' => 0,
                'completion_time_hours' => 0,
                'rating' => $request['rating'],
                'would_recommend' => $request['would_recommend'],
                'k1_score' => 1,
                'k2_score' => 1,
                'k3_score' => 1,
                'k4_score' => 1,
                'request_kpi_score' => 1
            ];
        }
        
        $summary = [
            'k1_avg' => 1,
            'k2_avg' => 1,
            'k3_avg' => 1,
            'k4_avg' => 1,
            'kpi_avg' => 1
        ];
        
        return [
            'staff' => $staff,
            'requests' => $staff_requests,
            'summary' => $summary
        ];
    }
    
    $result = getStaffDetailedKPI($db, $staff_id, $start_date, $end_date);
    
    if ($result) {
        echo "<pre>";
        echo "SUCCESS! Result structure:\n";
        print_r($result);
        echo "</pre>";
    } else {
        echo "FAILED! Result is null";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
    echo "<pre>";
    echo $e->getTraceAsString();
    echo "</pre>";
}
?>
