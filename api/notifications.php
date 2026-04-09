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

// Start session
require_once __DIR__ . '/../config/session.php';
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
    // Connect to database using same configuration as other APIs
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $pdo = $database->getConnection();
    
    // Use advanced notification helper
    require_once __DIR__ . '/../lib/NotificationHelper.php';
    $notificationHelper = new NotificationHelper();
    
    if ($method == 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action == 'count') {
            // Get unread notification count using helper
            $count = $notificationHelper->getUnreadCount($userId);
            echo json_encode(['count' => $count]);
            
        } else {
            // Get notifications list using helper
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
            $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
            
            $notifications = $notificationHelper->getUserNotifications($userId, $limit, $offset);
            
            // Format notifications for frontend
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
                    'time_ago' => $notif['time_ago'] ?? 'Vừa xong'
                ];
            }
            
            echo json_encode($formattedNotifications);
        }
    } else if ($method == 'PUT') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $_GET['action'] ?? '';
        
        if ($action == 'mark_read') {
            // Mark single notification as read using helper
            $notificationId = $input['notification_id'] ?? null;
            
            if (!$notificationId) {
                http_response_code(400);
                echo json_encode(['error' => 'Notification ID required']);
                exit;
            }
            
            $result = $notificationHelper->markAsRead($notificationId, $userId);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark notification as read']);
            }
            
        } else if ($action == 'mark_all_read') {
            // Mark all notifications as read using helper
            $result = $notificationHelper->markAllAsRead($userId);
            
            if ($result) {
                echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark all notifications as read']);
            }
        } else {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
        }
    } else if ($method == 'POST') {
        // Create new notification using helper
        $input = json_decode(file_get_contents('php://input'), true);
        
        if (!$input) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            exit;
        }
        
        $notificationUserId = $input['user_id'] ?? $userId;
        $title = $input['title'] ?? '';
        $message = $input['message'] ?? '';
        $type = $input['type'] ?? 'info';
        $relatedId = $input['related_id'] ?? null;
        $relatedType = $input['related_type'] ?? null;
        
        if (empty($title) || empty($message)) {
            http_response_code(400);
            echo json_encode(['error' => 'Title and message are required']);
            exit;
        }
        
        $result = $notificationHelper->createNotification(
            $notificationUserId, 
            $title, 
            $message, 
            $type, 
            $relatedId, 
            $relatedType, 
            true // Send email
        );
        
        if ($result) {
            echo json_encode([
                'success' => true, 
                'message' => 'Notification created successfully',
                'data' => ['id' => $pdo->lastInsertId()]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create notification']);
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
