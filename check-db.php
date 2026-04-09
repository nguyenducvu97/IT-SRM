<?php
require_once 'config/database.php';
$db = getDatabaseConnection();
$query = "SELECT id, title, created_at FROM service_requests WHERE user_id = 4 AND title LIKE '%Multipart Test Request%' ORDER BY created_at DESC LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    echo "Found request: ID " . $result['id'] . " - " . $result['title'] . " at " . $result['created_at'];
} else {
    echo "No request found";
}
?>
