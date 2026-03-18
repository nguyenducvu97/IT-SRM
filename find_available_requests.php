<?php
// Find requests without reject requests
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== REQUESTS WITHOUT REJECT REQUESTS ===\n";
$query = "SELECT sr.id, sr.title, sr.status, sr.user_id
          FROM service_requests sr
          LEFT JOIN reject_requests rr ON sr.id = rr.service_request_id AND rr.status = 'pending'
          WHERE rr.id IS NULL 
          AND sr.user_id != 17
          ORDER BY sr.id DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($requests)) {
    echo "No available requests found\n";
} else {
    foreach ($requests as $req) {
        echo "ID: {$req['id']}, Title: {$req['title']}, Status: {$req['status']}, User: {$req['user_id']}\n";
    }
}
?>
