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

echo "<h2>Test Estimated Completion Data</h2>";

// Test query for requests with estimated_completion
$requests_query = "SELECT sr.id, sr.title, sr.estimated_completion, sr.resolved_at, rf.rating, rf.would_recommend
                  FROM service_requests sr
                  LEFT JOIN request_feedback rf ON sr.id = rf.service_request_id
                  WHERE sr.assigned_to = :staff_id 
                  AND sr.created_at BETWEEN :start_date AND CONCAT(:end_date, ' 23:59:59')
                  ORDER BY sr.created_at DESC LIMIT 5";

$requests_stmt = $db->prepare($requests_query);
$requests_stmt->bindParam(':staff_id', $staff_id = 2);
$requests_stmt->bindParam(':start_date', $start_date = '2024-01-01');
$requests_stmt->bindParam(':end_date', $end_date = '2026-12-31');
$requests_stmt->execute();
$requests = $requests_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Sample Requests Data:</h3>";
foreach ($requests as $request) {
    echo "<pre>";
    echo "ID: " . $request['id'] . "\n";
    echo "Title: " . $request['title'] . "\n";
    echo "Estimated Completion: " . ($request['estimated_completion'] ?? 'NULL') . "\n";
    echo "Resolved At: " . ($request['resolved_at'] ?? 'NULL') . "\n";
    echo "Rating: " . ($request['rating'] ?? 'NULL') . "\n";
    echo "Would Recommend: " . ($request['would_recommend'] ?? 'NULL') . "\n";
    echo "</pre><hr>";
}

// Check if there are any requests with estimated_completion
$count_query = "SELECT COUNT(*) as count FROM service_requests WHERE estimated_completion IS NOT NULL";
$count_stmt = $db->prepare($count_query);
$count_stmt->execute();
$count = $count_stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Requests with Estimated Completion: " . $count['count'] . "</h3>";

// Check if there are any feedback records
$feedback_query = "SELECT COUNT(*) as count FROM request_feedback";
$feedback_stmt = $db->prepare($feedback_query);
$feedback_stmt->execute();
$feedback_count = $feedback_stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Total Feedback Records: " . $feedback_count['count'] . "</h3>";
?>
