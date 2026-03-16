<?php
require_once 'config/database.php';
require_once 'config/session.php';

startSession();
$user_id = getCurrentUserId();
$user_role = getCurrentUserRole();

echo "<h2>Debug Staff Requests</h2>";
echo "<p>Current User: ID=$user_id, Role=$user_role</p>";

$db = getDatabaseConnection();

// Check all requests
$query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                 CASE WHEN sr.assigned_to IS NOT NULL THEN u2.full_name ELSE NULL END as assignee_name
          FROM service_requests sr
          LEFT JOIN categories c ON sr.category_id = c.id
          LEFT JOIN users u ON sr.user_id = u.id
          LEFT JOIN users u2 ON sr.assigned_to = u2.id
          ORDER BY sr.created_at DESC";

$result = $db->query($query);
$requests = $result->fetchAll(PDO::FETCH_ASSOC);

echo "<h3>All Requests in Database:</h3>";
echo "<table border='1' style='border-collapse: collapse; width: 100%'>";
echo "<tr style='background: #f0f0f0;'>
    <th>ID</th>
    <th>Title</th>
    <th>Requester</th>
    <th>Assignee</th>
    <th>Status</th>
    <th>Assigned To</th>
    <th>Can Staff See?</th>
</tr>";

foreach ($requests as $req) {
    $can_see = ($user_role == 'admin') || 
               ($user_role == 'staff' && ($req['user_id'] == $user_id || $req['assigned_to'] == $user_id));
    
    $row_style = $can_see ? '' : "style='background: #ffe6e6;'";
    
    echo "<tr $row_style>
        <td>{$req['id']}</td>
        <td>{$req['title']}</td>
        <td>{$req['requester_name']}</td>
        <td>{$req['assignee_name']}</td>
        <td>{$req['status']}</td>
        <td>{$req['assigned_to']}</td>
        <td>" . ($can_see ? 'YES' : 'NO') . "</td>
    </tr>";
}

echo "</table>";

echo "<h3>What Staff Should See:</h3>";
echo "<p>Staff should see requests where:</p>";
echo "<ul>";
echo "<li>user_id = $user_id (they created the request)</li>";
echo "<li>assigned_to = $user_id (request assigned to them)</li>";
echo "</ul>";

// Test the actual API call
echo "<h3>Testing API List Call:</h3>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost/it-service-request/api/service_requests.php?action=list");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HEADER, false);
curl_setopt($ch, CURLOPT_COOKIE, session_name() . "=" . session_id());
$response = curl_exec($ch);
curl_close($ch);

echo "<h4>API Response:</h4>";
echo "<pre>" . $response . "</pre>";
?>
