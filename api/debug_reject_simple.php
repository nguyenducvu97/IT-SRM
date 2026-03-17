<?php
// Simple test for reject_requests.php
header('Content-Type: application/json');

try {
    require_once '../config/database.php';
    require_once '../config/session.php';
    
    startSession();
    
    $database = new Database();
    $db = $database->getConnection();
    
    // Test simple query
    $stmt = $db->prepare("SELECT COUNT(*) as count FROM reject_requests");
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'count' => $result['count']
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
?>
