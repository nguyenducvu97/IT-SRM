<?php
require_once 'config/session.php';
require_once 'config/database.php';

startSession();

// Mock login for testing
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';
    $_SESSION['full_name'] = 'Test Admin';
}

$database = new Database();
$db = $database->getConnection();

echo "<h2>Staff Assignments Test</h2>";

// Check all staff
echo "<h3>All Staff Users</h3>";
$stmt = $db->prepare("SELECT id, username, full_name FROM users WHERE role IN ('admin', 'staff')");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

// Check requests with assignments
echo "<h3>Service Requests with Assignments</h3>";
$stmt = $db->prepare("SELECT sr.id, sr.title, sr.assigned_to, u.username as assigned_username, sr.status, sr.created_at, sr.resolved_at, sr.estimated_completion 
                     FROM service_requests sr 
                     LEFT JOIN users u ON sr.assigned_to = u.id 
                     WHERE sr.assigned_to IS NOT NULL 
                     ORDER BY sr.created_at DESC LIMIT 10");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

// Check feedback with assignments
echo "<h3>Request Feedback with Staff Assignments</h3>";
$stmt = $db->prepare("SELECT rf.*, sr.title, sr.assigned_to, u.username as assigned_username 
                     FROM request_feedback rf 
                     JOIN service_requests sr ON rf.service_request_id = sr.id 
                     LEFT JOIN users u ON sr.assigned_to = u.id 
                     ORDER BY rf.created_at DESC LIMIT 10");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

// Test with different staff IDs
echo "<h3>Test KPI for Different Staff IDs</h3>";
$staff_ids = [1, 2, 3, 4];
$start_date = '2024-01-01';
$end_date = '2026-12-31';

foreach ($staff_ids as $staff_id) {
    echo "<h4>Staff ID: $staff_id</h4>";
    
    // Check requests
    $stmt = $db->prepare("SELECT COUNT(*) as total_requests FROM service_requests WHERE assigned_to = :staff_id");
    $stmt->bindParam(':staff_id', $staff_id);
    $stmt->execute();
    $requests = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Requests: " . $requests['total_requests'] . "<br>";
    
    // Check feedback
    $stmt = $db->prepare("SELECT COUNT(*) as total_feedback, AVG(rating) as avg_rating 
                         FROM request_feedback rf 
                         JOIN service_requests sr ON rf.service_request_id = sr.id 
                         WHERE sr.assigned_to = :staff_id");
    $stmt->bindParam(':staff_id', $staff_id);
    $stmt->execute();
    $feedback = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total Feedback: " . $feedback['total_feedback'] . ", Avg Rating: " . $feedback['avg_rating'] . "<br>";
    
    echo "<hr>";
}
?>
