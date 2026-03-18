<?php
// Test reject request endpoint
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Try different include paths
$include_paths = [
    '../config/database.php',
    'config/database.php',
    __DIR__ . '/../config/database.php',
    'C:\xampp\htdocs\it-service-request\config\database.php'
];

$db = null;
foreach ($include_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        $database = new Database();
        $db = $database->getConnection();
        break;
    }
}

if (!$db) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Try session include
$session_paths = [
    '../config/session.php',
    'config/session.php',
    __DIR__ . '/../config/session.php',
    'C:\xampp\htdocs\it-service-request\config\session.php'
];

foreach ($session_paths as $path) {
    if (file_exists($path)) {
        require_once $path;
        startSession();
        break;
    }
}

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = getCurrentUserId();
$user_role = getCurrentUserRole();

// Only staff can reject requests
if ($user_role != 'staff') {
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
    
    // Get action and data
    if (strpos($content_type, 'multipart/form-data') !== false) {
        // Handle FormData (file upload)
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $request_id = isset($_POST['request_id']) ? (int)$_POST['request_id'] : 0;
        $reject_reason = isset($_POST['reject_reason']) ? trim($_POST['reject_reason']) : '';
        $reject_details = isset($_POST['reject_details']) ? trim($_POST['reject_details']) : '';
    } else {
        // Handle JSON or regular form POST
        $input = json_decode(file_get_contents('php://input'), true);
        
        // If JSON parsing failed, try to use POST data (regular form)
        if ($input === null && !empty($_POST)) {
            $input = $_POST;
        }
        
        $action = isset($input['action']) ? $input['action'] : '';
        $request_id = isset($input['request_id']) ? (int)$input['request_id'] : 0;
        $reject_reason = isset($input['reject_reason']) ? trim($input['reject_reason']) : '';
        $reject_details = isset($input['reject_details']) ? trim($input['reject_details']) : '';
    }
    
    if ($action !== 'reject_request') {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
        exit;
    }
    
    if ($request_id <= 0 || empty($reject_reason)) {
        echo json_encode(['success' => false, 'message' => 'Request ID and reject reason are required']);
        exit;
    }
    
    try {
        // Check if request exists
        $check_query = "SELECT id, user_id FROM service_requests WHERE id = ?";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([$request_id]);
        
        if ($check_stmt->rowCount() == 0) {
            echo json_encode(['success' => false, 'message' => 'Request not found']);
            exit;
        }
        
        $request_data = $check_stmt->fetch(PDO::FETCH_ASSOC);
        
        // Staff cannot reject their own request
        if ($request_data['user_id'] == $user_id) {
            echo json_encode(['success' => false, 'message' => 'You cannot reject your own request']);
            exit;
        }
        
        // Check if reject request already exists
        $existing_query = "SELECT id FROM reject_requests WHERE service_request_id = ? AND status = 'pending'";
        $existing_stmt = $db->prepare($existing_query);
        $existing_stmt->execute([$request_id]);
        
        if ($existing_stmt->rowCount() > 0) {
            echo json_encode(['success' => false, 'message' => 'Reject request already exists']);
            exit;
        }
        
        // Create reject request
        $insert_query = "INSERT INTO reject_requests 
                         (service_request_id, rejected_by, reject_reason, reject_details, status, created_at) 
                         VALUES (?, ?, ?, ?, 'pending', NOW())";
        $insert_stmt = $db->prepare($insert_query);
        
        if ($insert_stmt->execute([$request_id, $user_id, $reject_reason, $reject_details])) {
            $reject_id = $db->lastInsertId();
            
            // Handle file uploads
            $uploaded_files = [];
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $uploads_dir = __DIR__ . '/../uploads/reject_requests/';
                if (!file_exists($uploads_dir)) {
                    mkdir($uploads_dir, 0755, true);
                }
                
                $files = $_FILES['attachments'];
                foreach ($files['name'] as $key => $name) {
                    if ($files['error'][$key] === UPLOAD_ERR_OK) {
                        $tmp_name = $files['tmp_name'][$key];
                        $file_extension = pathinfo($name, PATHINFO_EXTENSION);
                        $new_filename = uniqid() . '_' . $name;
                        $file_path = $uploads_dir . $new_filename;
                        
                        if (move_uploaded_file($tmp_name, $file_path)) {
                            // Save to database - use correct column names
                            $attachment_stmt = $db->prepare("
                                INSERT INTO reject_request_attachments 
                                (reject_request_id, original_name, filename, file_size, mime_type, created_at)
                                VALUES (?, ?, ?, ?, ?, NOW())
                            ");
                            
                            $attachment_stmt->execute([
                                $reject_id,
                                $name,
                                $new_filename,
                                $files['size'][$key],
                                $files['type'][$key]
                            ]);
                            
                            $uploaded_files[] = [
                                'original_name' => $name,
                                'filename' => $new_filename,
                                'size' => $files['size'][$key]
                            ];
                        }
                    }
                }
            }
            
            // Send notifications to admins
            try {
                require_once __DIR__ . '/../lib/NotificationHelper.php';
                $notificationHelper = new NotificationHelper($db);
                
                $admin_stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin'");
                $admin_stmt->execute();
                $admins = $admin_stmt->fetchAll(PDO::FETCH_COLUMN);
                
                if (!empty($admins)) {
                    $title = "Yêu cầu từ chối #" . $request_id;
                    $message = "Yêu cầu #" . $request_id . " đã được gửi yêu cầu từ chối và đang chờ duyệt";
                    
                    foreach ($admins as $admin_id) {
                        $notificationHelper->createNotification($admin_id, $title, $message, 'warning', $request_id, 'reject_request', false);
                    }
                }
            } catch (Exception $e) {
                // Continue even if notification fails
            }
            
            $response_data = [
                'success' => true,
                'message' => 'Reject request submitted successfully',
                'reject_id' => $reject_id
            ];
            
            if (!empty($uploaded_files)) {
                $response_data['uploaded_files'] = $uploaded_files;
                $response_data['message'] .= ' with ' . count($uploaded_files) . ' file(s) attached';
            }
            
            echo json_encode($response_data);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to create reject request']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}
?>
