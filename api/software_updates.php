<?php
// API for software update notifications
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

require_once '../config/database.php';
require_once '../config/session.php';
require_once '../api/notifications.php';

// Start session
startSession();

// Check if user is authenticated and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['error' => 'Admin access required']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    // Create software update notification for all users
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!$data || !isset($data['title']) || !isset($data['message'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Title and message are required']);
        exit;
    }
    
    try {
        $pdo = getDatabaseConnection();
        
        // Get all users
        $stmt = $pdo->prepare("SELECT id FROM users");
        $stmt->execute();
        $all_users = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Create notification for all users
        $title = $data['title'];
        $message = $data['message'];
        $type = $data['type'] ?? 'info';
        
        notifyUsers($pdo, $all_users, $title, $message, $type, null, 'software_update');
        
        echo json_encode([
            'success' => true,
            'message' => 'Software update notification sent to all users',
            'users_notified' => count($all_users)
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to create notifications: ' . $e->getMessage()]);
    }
    
} elseif ($method == 'GET') {
    // Get software update notifications
    try {
        $pdo = getDatabaseConnection();
        
        $stmt = $pdo->prepare("
            SELECT * FROM notifications 
            WHERE related_type = 'software_update' 
            ORDER BY created_at DESC 
            LIMIT 10
        ");
        $stmt->execute();
        $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode([
            'success' => true,
            'data' => $notifications
        ]);
        
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to get notifications: ' . $e->getMessage()]);
    }
}
?>
