<?php
require_once 'config/database.php';
$database = new Database();
$db = $database->getConnection();

echo "<h2>Request Feedback Table Structure</h2>";
$result = $db->query("DESCRIBE request_feedback");
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h2>Sample Data from request_feedback</h2>";
$result = $db->query("SELECT * FROM request_feedback LIMIT 5");
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}

echo "<h2>Sample Data from service_requests (estimated_completion)</h2>";
$result = $db->query("SELECT id, title, estimated_completion, resolved_at FROM service_requests WHERE estimated_completion IS NOT NULL LIMIT 5");
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    echo "<pre>" . print_r($row, true) . "</pre>";
}
?>
