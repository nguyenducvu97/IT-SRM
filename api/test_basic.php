<?php
// Quick test to see if the basic API works
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';

// Start session for authentication
startSession();

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed'
    ]);
    exit();
}

// Simple test query
try {
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM service_requests");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'API working',
        'data' => $result
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Query failed: ' . $e->getMessage()
    ]);
}
?>
