<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: http://localhost');
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start session
require_once '../config/session.php';
startSession();

// Check if user is authenticated
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Connect to database using the same configuration as other APIs
    require_once '../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
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
    } else if ($method == 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $_GET['action'] ?? '';
        
        if ($action == 'mark_read') {
            // Mark single notification as read
            $notificationId = $input['notification_id'] ?? null;
            
            if (!$notificationId) {
                http_response_code(400);
                echo json_encode(['error' => 'Notification ID required']);
                exit;
            }
            
            // Verify notification belongs to user
            $stmt = $pdo->prepare("SELECT id FROM notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$notificationId, $userId]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Notification not found or access denied']);
                exit;
            }
            
            // Mark as read
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$notificationId, $userId]);
            
            echo json_encode(['success' => true]);
            
        } else if ($action == 'mark_all_read') {
            // Mark all notifications as read for user
            $stmt = $pdo->prepare("
                UPDATE notifications 
                SET is_read = TRUE, read_at = CURRENT_TIMESTAMP 
                WHERE user_id = ? AND is_read = FALSE
            ");
            $stmt->execute([$userId]);
            
            echo json_encode(['success' => true]);
            
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
    } else if ($method == 'POST') {
        // Create new notification
        $input = json_decode(file_get_contents('php://input'), true);
        
        $targetUserId = $input['user_id'] ?? null;
        $title = $input['title'] ?? '';
        $message = $input['message'] ?? '';
        $type = $input['type'] ?? 'info';
        $relatedId = $input['related_id'] ?? null;
        $relatedType = $input['related_type'] ?? null;
        
        if (!$targetUserId || !$title || !$message) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing required fields']);
            exit;
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO notifications (user_id, title, message, type, related_id, related_type, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$targetUserId, $title, $message, $type, $relatedId, $relatedType]);
        
        echo json_encode(['success' => true, 'id' => $pdo->lastInsertId()]);
        
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
