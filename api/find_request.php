<?php
// Test with known existing request
header('Content-Type: application/json');

require_once 'config/database.php';
require_once 'config/session.php';

startSession();

$_SESSION['user_id'] = 17;
$_SESSION['username'] = 'nvnam';
$_SESSION['full_name'] = 'Nguyễn Văn Tín';
$_SESSION['role'] = 'staff';

$database = new Database();
$db = $database->getConnection();

// Get any existing request
$stmt = $db->prepare("SELECT id, user_id FROM service_requests LIMIT 1");
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($request) {
    $request_id = $request['id'];
    echo json_encode([
        'success' => true,
        'message' => 'Found request ID: ' . $request_id,
        'request_user_id' => $request['user_id'],
        'current_user_id' => $_SESSION['user_id'],
        'can_reject' => $request['user_id'] != $_SESSION['user_id']
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'No requests found in database'
    ]);
}
?>
