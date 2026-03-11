<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
session_start();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Connect to database
    $pdo = new PDO("mysql:host=localhost;dbname=it_service_request", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    if ($method == 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action == 'count') {
            // Get unread notification count
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = FALSE");
            $stmt->execute([$userId]);
            $count = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['count' => (int)$count['count']]);
            
        } else {
            // Get notifications list
            $stmt = $pdo->prepare("
                SELECT id, title, message, type, related_id, related_type, 
                       is_read, created_at, read_at
                FROM notifications 
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 20
            ");
            $stmt->execute([$userId]);
            $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Format notifications
            $formattedNotifications = [];
            foreach ($notifications as $notif) {
                $formattedNotifications[] = [
                    'id' => $notif['id'],
                    'title' => $notif['title'],
                    'message' => $notif['message'],
                    'type' => $notif['type'],
                    'related_id' => $notif['related_id'],
                    'related_type' => $notif['related_type'],
                    'is_read' => (bool)$notif['is_read'],
                    'created_at' => $notif['created_at'],
                    'read_at' => $notif['read_at'],
                    'time_ago' => 'Vừa xong'
                ];
            }
            
            echo json_encode($formattedNotifications);
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'General error: ' . $e->getMessage()]);
}
?>
