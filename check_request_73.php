<?php
// Check if request 73 exists
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT id, user_id, assigned_to, status FROM service_requests WHERE id = ?");
$stmt->execute([73]);
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($request) {
    echo json_encode([
        'success' => true,
        'request' => $request
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Request 73 not found'
    ]);
    
    // Show all requests for debugging
    $all_stmt = $db->prepare("SELECT id, user_id, title, status FROM service_requests ORDER BY id DESC LIMIT 5");
    $all_stmt->execute();
    $all_requests = $all_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "\nAvailable requests:\n";
    foreach ($all_requests as $req) {
        echo "ID: {$req['id']}, User: {$req['user_id']}, Status: {$req['status']}, Title: {$req['title']}\n";
    }
}
?>
