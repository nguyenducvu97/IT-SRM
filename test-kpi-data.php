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

echo "<h2>KPI Data Test</h2>";

// Test 1: Check service_requests table for estimated_completion
echo "<h3>Service Requests (estimated_completion)</h3>";
$stmt = $db->prepare("SELECT id, title, estimated_completion, resolved_at FROM service_requests WHERE estimated_completion IS NOT NULL LIMIT 5");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

// Test 2: Check request_feedback table
echo "<h3>Request Feedback Data</h3>";
$stmt = $db->prepare("SELECT rf.*, sr.title as request_title FROM request_feedback rf JOIN service_requests sr ON rf.service_request_id = sr.id LIMIT 5");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

// Test 3: Test aggregate query
echo "<h3>Test Aggregate Query</h3>";
$staff_id = 1;
$start_date = '2024-01-01';
$end_date = '2024-12-31';

$feedback_query = "SELECT 
    COUNT(rf.id) as total_feedback,
    AVG(rf.rating) as avg_rating,
    SUM(CASE WHEN rf.rating >= 4 THEN 1 ELSE 0 END) as positive_feedback,
    SUM(CASE WHEN rf.rating <= 2 THEN 1 ELSE 0 END) as negative_feedback,
    SUM(CASE 
        WHEN rf.would_recommend = 'yes' OR rf.would_recommend = '1' OR rf.would_recommend = 1 THEN 1 
        WHEN rf.would_recommend = 'maybe' OR rf.would_recommend = '2' OR rf.would_recommend = 2 THEN 0.5 
        ELSE 0 
    END) as would_recommend_count,
    COUNT(DISTINCT rf.service_request_id) as rated_requests,
    GROUP_CONCAT(DISTINCT rf.would_recommend) as would_recommend_values
    FROM request_feedback rf
    JOIN service_requests sr ON rf.service_request_id = sr.id
    WHERE sr.assigned_to = :staff_id
    AND sr.status IN ('resolved', 'closed')
    AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";

$stmt = $db->prepare($feedback_query);
$stmt->bindParam(':staff_id', $staff_id);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$feedback = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>" . print_r($feedback, true) . "</pre>";

// Test 4: Test K2 calculation
echo "<h3>Test K2 Calculation</h3>";
$delta_t_query = "SELECT AVG(
    CASE 
        WHEN sr.estimated_completion IS NOT NULL AND sr.resolved_at IS NOT NULL
        THEN TIMESTAMPDIFF(HOUR, sr.estimated_completion, sr.resolved_at)
        ELSE NULL
    END
) as avg_delta_t
FROM service_requests sr
WHERE sr.assigned_to = :staff_id
AND sr.status IN ('resolved', 'closed')
AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')";

$stmt = $db->prepare($delta_t_query);
$stmt->bindParam(':staff_id', $staff_id);
$stmt->bindParam(':start_date', $start_date);
$stmt->bindParam(':end_date', $end_date);
$stmt->execute();
$delta_t_result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<pre>" . print_r($delta_t_result, true) . "</pre>";
?>
