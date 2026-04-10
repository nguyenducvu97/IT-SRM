<?php
// IT Service Request Reject Request Attachment API
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Origin: http://localhost");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
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

// Check if user is logged in - allow viewing without authentication for preview
// But require authentication for downloads
$action = $_GET['action'] ?? 'download';
if ($action !== 'view' && !isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized - Please login first']);
    exit;
}

// Get file name from request
$fileName = $_GET['file'] ?? '';
if (empty($fileName)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File name is required']);
    exit;
}

// Security: Validate file name to prevent path traversal
if (strpos($fileName, '..') !== false || strpos($fileName, '/') !== false || strpos($fileName, '\\') !== false) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid file name']);
    exit;
}

// Build file path
$uploadsDir = __DIR__ . '/../uploads/reject_requests/';
$filePath = $uploadsDir . $fileName;

// Security check - ensure file is within uploads directory
$realBasePath = realpath($uploadsDir);
$realFilePath = realpath($filePath);

if ($realFilePath === false || strpos($realFilePath, $realBasePath) !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied - File path validation failed']);
    exit;
}

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

// For downloads, verify attachment exists in database and user has access
if ($action === 'download') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db === null) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        
        // Check if attachment exists and get reject request ID
        $query = "SELECT rra.reject_request_id, rr.rejected_by 
                  FROM reject_request_attachments rra 
                  JOIN reject_requests rr ON rra.reject_request_id = rr.id 
                  WHERE rra.filename = :filename";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':filename', $fileName);
        $stmt->execute();
        
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$attachment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Attachment not found in database']);
            exit;
        }
        
        // Check user permissions (admin, staff, or owner)
        $user_role = getCurrentUserRole();
        $user_id = getCurrentUserId();
        
        if ($user_role !== 'admin' && $user_role !== 'staff' && $attachment['rejected_by'] != $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Access denied - You do not have permission to download this file']);
            exit;
        }
        
    } catch (Exception $e) {
        error_log("Database error in reject_request_attachment.php: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
        exit;
    }
}

// Get file info
$fileSize = filesize($filePath);
$fileInfo = finfo_open(FILEINFO_MIME_TYPE);
$mimeType = finfo_file($fileInfo, $filePath);
finfo_close($fileInfo);

// Debug logging
error_log("=== REJECT ATTACHMENT DEBUG ===");
error_log("File name: " . $fileName);
error_log("File path: " . $filePath);
error_log("File exists: " . (file_exists($filePath) ? 'YES' : 'NO'));
error_log("File size: " . $fileSize);
error_log("Detected MIME type: " . $mimeType);
error_log("File extension: " . pathinfo($fileName, PATHINFO_EXTENSION));

// Special handling for image files - validate they are actually images
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']) && strpos($mimeType, 'image/') !== 0) {
    error_log("WARNING: File has image extension but MIME type is not image: " . $mimeType);
    
    // For view action, return error instead of corrupted content
    $action = $_GET['action'] ?? 'download';
    if ($action === 'view') {
        http_response_code(422);
        echo json_encode([
            'success' => false, 
            'message' => 'File is corrupted or not a valid image file',
            'detected_type' => $mimeType,
            'expected_type' => 'image/*'
        ]);
        exit;
    }
}

// Set appropriate headers for file download
header('Content-Type: ' . $mimeType);
header('Content-Length: ' . $fileSize);
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// For download vs view based on request parameter
$action = $_GET['action'] ?? 'download';
if ($action === 'view') {
    header('Content-Disposition: inline; filename="' . basename($fileName) . '"');
} else {
    header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
}

// Output file content
readfile($filePath);
exit;
?>
