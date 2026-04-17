<?php
// Simple check for closed requests
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "<h2>Check Closed Requests</h2>";

// Check all requests with their status
$query = "SELECT id, title, status FROM service_requests ORDER BY id DESC LIMIT 10";
$stmt = $db->prepare($query);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>Recent Requests:</h3>";
foreach ($requests as $request) {
    $status_color = $request['status'] === 'closed' ? 'red' : 'green';
    echo "<p><strong>#{$request['id']}</strong> - {$request['title']} - <span style='color: {$status_color}'>{$request['status']}</span></p>";
}

// Check specifically for closed requests
$closed_query = "SELECT COUNT(*) as total FROM service_requests WHERE status = 'closed'";
$closed_stmt = $db->prepare($closed_query);
$closed_stmt->execute();
$total_closed = $closed_stmt->fetch(PDO::FETCH_ASSOC)['total'];

echo "<h3>Status Summary:</h3>";
echo "<p>Total closed requests: <strong>{$total_closed}</strong></p>";

// Check all statuses
$status_query = "SELECT status, COUNT(*) as count FROM service_requests GROUP BY status";
$status_stmt = $db->prepare($status_query);
$status_stmt->execute();
$statuses = $status_stmt->fetchAll(PDO::FETCH_ASSOC);

echo "<p>Status breakdown:</p>";
foreach ($statuses as $status) {
    echo "<p>- {$status['status']}: {$status['count']}</p>";
}
?>
