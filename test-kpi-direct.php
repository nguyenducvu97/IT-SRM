<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include required files
require_once 'config/session.php';
require_once 'config/database.php';

// Start session
startSession();

// Simulate admin login
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';
$_SESSION['full_name'] = 'Administrator';

echo "<h2>KPI Export Direct Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User logged in: " . (isLoggedIn() ? "Yes" : "No") . "</p>";
echo "<p>User role: " . getCurrentUserRole() . "</p>";

// Test database connection
$db = (new Database())->getConnection();
if ($db) {
    echo "<p style='color: green;'>✅ Database connected</p>";
} else {
    echo "<p style='color: red;'>❌ Database connection failed</p>";
    exit;
}

// Test KPI calculation directly
try {
    $start_date = '2026-04-01';
    $end_date = '2026-05-30';
    
    // Get staff users
    $staff_query = "SELECT id, username, full_name, email, department FROM users WHERE role = 'staff'";
    $staff_stmt = $db->prepare($staff_query);
    $staff_stmt->execute();
    $staff_users = $staff_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<p>Found " . count($staff_users) . " staff users</p>";
    
    foreach ($staff_users as $staff) {
        echo "<h3>Staff: " . $staff['username'] . "</h3>";
        
        // Get requests for this staff
        $requests_query = "SELECT COUNT(*) as total_requests,
                           SUM(CASE WHEN status IN ('resolved', 'closed') THEN 1 ELSE 0 END) as completed_requests
                           FROM service_requests 
                           WHERE assigned_to = :staff_id 
                           AND created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";
        
        $requests_stmt = $db->prepare($requests_query);
        $requests_stmt->bindParam(':staff_id', $staff['id']);
        $requests_stmt->bindParam(':start_date', $start_date);
        $requests_stmt->bindParam(':end_date', $end_date);
        $requests_stmt->execute();
        $requests = $requests_stmt->fetch(PDO::FETCH_ASSOC);
        
        echo "<p>Total requests: " . $requests['total_requests'] . "</p>";
        echo "<p>Completed requests: " . $requests['completed_requests'] . "</p>";
    }
    
    echo "<p style='color: green;'>✅ KPI calculation working!</p>";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}
?>
