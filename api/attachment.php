<?php
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

// Decode URL-encoded file name
$fileName = urldecode($fileName);

// Security: Validate file name to prevent path traversal
// Allow Unicode characters but reject actual path traversal attempts
// Check for dangerous patterns
$dangerousPatterns = [
    '/\.\.[\/\\\\]/',     // Directory traversal (../ or ..\)
    '/[\/\\\\]\.\./',     // Directory traversal (./.. or \..)
    '/^[\/\\\\]/',        // Starts with slash or backslash
    '/[\/\\\\]$/',        // Ends with slash or backslash  
    '/[A-Za-z]:[\/\\\\]/', // Windows drive letters
    '/\0/',              // Null bytes
];

foreach ($dangerousPatterns as $pattern) {
    if (preg_match($pattern, $fileName)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Invalid file name']);
        exit;
    }
}

// Build file path - check both requests and completed directories
$requestsDir = __DIR__ . '/../uploads/requests/';
$completedDir = __DIR__ . '/../uploads/completed/';

// Try requests directory first
$filePath = $requestsDir . $fileName;
if (!file_exists($filePath)) {
    // Try completed directory
    $filePath = $completedDir . $fileName;
}

// Security check - ensure file is within uploads directory
$realRequestsPath = realpath($requestsDir);
$realCompletedPath = realpath($completedDir);
$realFilePath = realpath($filePath);

if ($realFilePath === false || 
    (strpos($realFilePath, $realRequestsPath) !== 0 && 
     strpos($realFilePath, $realCompletedPath) !== 0)) {
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

// For downloads, verify the attachment exists in database and user has access
if ($action === 'download') {
    try {
        $database = new Database();
        $db = $database->getConnection();
        
        if ($db === null) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Database connection failed']);
            exit;
        }
        
        // Check if attachment exists and get service request ID
        // First try regular attachments table
        $query = "SELECT a.service_request_id, sr.user_id 
                  FROM attachments a 
                  JOIN service_requests sr ON a.service_request_id = sr.id 
                  WHERE a.filename = :filename";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':filename', $fileName);
        $stmt->execute();
        
        $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // If not found in attachments, try complete_request_attachments (resolution attachments)
        if (!$attachment) {
            $query = "SELECT cra.service_request_id, sr.user_id 
                      FROM complete_request_attachments cra 
                      JOIN service_requests sr ON cra.service_request_id = sr.id 
                      WHERE cra.filename = :filename";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':filename', $fileName);
            $stmt->execute();
            
            $attachment = $stmt->fetch(PDO::FETCH_ASSOC);
        }
        
        if (!$attachment) {
            // If still not found, check if file exists in complete_request_attachments without joining
            $resolutionQuery = "SELECT COUNT(*) as count FROM complete_request_attachments WHERE filename = :filename";
            $resolutionStmt = $db->prepare($resolutionQuery);
            $resolutionStmt->bindParam(':filename', $fileName);
            $resolutionStmt->execute();
            $resolutionResult = $resolutionStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($resolutionResult['count'] > 0) {
                // File exists in complete_request_attachments but no user_id info
                // Create a dummy attachment with null user_id for permission check
                $attachment = ['user_id' => null, 'service_request_id' => null];
            } else {
                http_response_code(404);
                echo json_encode(['success' => false, 'message' => 'Attachment not found in database']);
                exit;
            }
        }
        
        // Check user permissions (admin, staff, or owner)
        $user_role = getCurrentUserRole();
        $user_id = getCurrentUserId();
        
        error_log("=== PERMISSION DEBUG ===");
        error_log("User role: " . $user_role);
        error_log("User ID: " . $user_id);
        
        // For resolution attachments (from complete_request_attachments), allow admin and staff
        // For regular attachments, check ownership
        $isResolutionAttachment = false;
        
        // Check if this is a resolution attachment by looking for it in complete_request_attachments
        $resolutionQuery = "SELECT COUNT(*) as count FROM complete_request_attachments WHERE filename = :filename";
        $resolutionStmt = $db->prepare($resolutionQuery);
        $resolutionStmt->bindParam(':filename', $fileName);
        $resolutionStmt->execute();
        $resolutionResult = $resolutionStmt->fetch(PDO::FETCH_ASSOC);
        $isResolutionAttachment = $resolutionResult['count'] > 0;
        
        error_log("Is resolution attachment: " . ($isResolutionAttachment ? 'YES' : 'NO'));
        error_log("Resolution count: " . $resolutionResult['count']);
        
        if ($isResolutionAttachment) {
            // Resolution attachments: allow admin and staff
            error_log("Checking resolution attachment permissions...");
            if ($user_role !== 'admin' && $user_role !== 'staff') {
                error_log("Permission DENIED - User role: " . $user_role);
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied - Only admin and staff can view resolution attachments']);
                exit;
            }
            error_log("Permission GRANTED for resolution attachment");
        } else {
            // Regular attachments: check ownership
            error_log("Checking regular attachment permissions...");
            error_log("Attachment user_id: " . ($attachment['user_id'] ?? 'NULL'));
            if ($user_role !== 'admin' && $user_role !== 'staff' && $attachment['user_id'] != $user_id) {
                error_log("Permission DENIED - Not owner, not admin, not staff");
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Access denied - You do not have permission to download this file']);
                exit;
            }
            error_log("Permission GRANTED for regular attachment");
        }
        
    } catch (Exception $e) {
        error_log("Database error in attachment.php: " . $e->getMessage());
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
error_log("=== ATTACHMENT DEBUG ===");
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
