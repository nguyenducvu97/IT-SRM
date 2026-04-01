<?php
/**
 * API for uploading reject request attachments
 */
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../config/database.php';
require_once '../config/session.php';

// Start session for authentication
startSession();

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Only staff and admin can upload files
$user_role = getCurrentUserRole();

if (!in_array($user_role, ['staff', 'admin'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Database connection failed']);
        exit;
    }
    
    // Create uploads directory if it doesn't exist
    $uploadsDir = __DIR__ . '/../uploads/reject_requests/';
    if (!file_exists($uploadsDir)) {
        mkdir($uploadsDir, 0777, true);
    }
    
    $uploadedFiles = [];
    
    // Handle file uploads
    if (isset($_FILES['attachments']) && is_array($_FILES['attachments']['name'])) {
        $fileCount = count($_FILES['attachments']['name']);
        
        for ($i = 0; $i < $fileCount; $i++) {
            $fileError = $_FILES['attachments']['error'][$i];
            
            if ($fileError === UPLOAD_ERR_OK) {
                $fileName = $_FILES['attachments']['name'][$i];
                $fileTmpName = $_FILES['attachments']['tmp_name'][$i];
                $fileSize = $_FILES['attachments']['size'][$i];
                $fileType = $_FILES['attachments']['type'][$i];
                
                // Check if file actually exists
                if (!file_exists($fileTmpName)) {
                    continue;
                }
                
                // Generate unique filename
                $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
                $uniqueFileName = uniqid() . '_' . time() . '.' . $fileExtension;
                $uploadPath = $uploadsDir . $uniqueFileName;
                
                // Validate file (basic checks)
                $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'text/plain'];
                $maxFileSize = 10 * 1024 * 1024; // 10MB
                
                if (!in_array($fileType, $allowedTypes)) {
                    continue;
                }
                
                if ($fileSize > $maxFileSize) {
                    continue;
                }
                
                // Move file to uploads directory
                if (move_uploaded_file($fileTmpName, $uploadPath)) {
                    // Save to database
                    $stmt = $db->prepare("
                        INSERT INTO reject_request_attachments (filename, original_name, file_size, mime_type, uploaded_at)
                        VALUES (?, ?, ?, ?, NOW())
                    ");
                    
                    $result = $stmt->execute([
                        $uniqueFileName,
                        $fileName,
                        $fileSize,
                        $fileType
                    ]);
                    
                    if ($result) {
                        $attachmentId = $db->lastInsertId();
                        $uploadedFiles[] = [
                            'id' => $attachmentId,
                            'filename' => $uniqueFileName,
                            'original_name' => $fileName,
                            'mime_type' => $fileType,
                            'file_size' => $fileSize
                        ];
                    }
                }
            }
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Files uploaded successfully',
        'attachments' => $uploadedFiles
    ]);
    
} catch (Exception $e) {
    error_log("Error uploading reject request attachments: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Internal server error']);
}
?>
