<?php
// Simple API endpoint for creating requests (no session dependency)
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only handle POST requests
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Only POST method allowed'
    ]);
    exit();
}

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid JSON input'
        ]);
        exit();
    }
    
    // Get request data
    $title = isset($input['title']) ? trim($input['title']) : '';
    $description = isset($input['description']) ? trim($input['description']) : '';
    $category_id = isset($input['category_id']) ? (int)$input['category_id'] : 0;
    $priority = isset($input['priority']) ? $input['priority'] : 'medium';
    
    // Validation
    if (empty($title) || empty($description) || $category_id <= 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Title, description, and category are required'
        ]);
        exit();
    }
    
    // Database connection
    require_once __DIR__ . '/../config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if (!$db) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
        exit();
    }
    
    // Insert request
    $query = "INSERT INTO service_requests 
              (user_id, category_id, title, description, priority, status, created_at, updated_at)
              VALUES (?, ?, ?, ?, ?, 'open', NOW(), NOW())";
    
    $stmt = $db->prepare($query);
    $result = $stmt->execute([4, $category_id, $title, $description, $priority]); // Hardcoded user_id = 4 for testing
    
    if ($result) {
        $request_id = $db->lastInsertId();
        
        // Send email notification
        try {
            require_once __DIR__ . '/../lib/EmailHelper.php';
            $emailHelper = new EmailHelper();
            
            $email_result = $emailHelper->sendNewRequestNotification([
                'id' => $request_id,
                'title' => $title,
                'requester_name' => 'Test User',
                'category' => 'Test Category',
                'priority' => $priority,
                'description' => $description
            ]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Request created successfully with email notification',
                'data' => [
                    'id' => $request_id,
                    'title' => $title,
                    'category_id' => $category_id,
                    'priority' => $priority,
                    'email_sent' => $email_result
                ]
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => true,
                'message' => 'Request created successfully but email failed: ' . $e->getMessage(),
                'data' => [
                    'id' => $request_id,
                    'title' => $title,
                    'category_id' => $category_id,
                    'priority' => $priority,
                    'email_sent' => false,
                    'email_error' => $e->getMessage()
                ]
            ]);
        }
        
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to create request'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
