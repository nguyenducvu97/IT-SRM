<?php
// File download handler for uploads
header('Content-Type: application/json; charset=UTF-8');

if (!isset($_GET['file'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'File parameter required']);
    exit;
}

$fileName = $_GET['file'];
$uploadsDir = __DIR__ . '/uploads/requests/';
$filePath = $uploadsDir . $fileName;

// Security: Only allow files from uploads directory
$realBasePath = realpath($uploadsDir);
$realFilePath = realpath($filePath);

if ($realFilePath === false || strpos($realFilePath, $realBasePath) !== 0) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied']);
    exit;
}

if (!file_exists($filePath)) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

if (!is_readable($filePath)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'File not readable']);
    exit;
}

// Get file info
$fileSize = filesize($filePath);
$fileType = mime_content_type($filePath);
$displayName = basename($filePath);

// Set appropriate content type based on file extension
$extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
switch ($extension) {
    case 'docx':
        $fileType = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
        break;
    case 'xlsx':
        $fileType = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
        break;
    case 'pptx':
        $fileType = 'application/vnd.openxmlformats-officedocument.presentationml.presentation';
        break;
    case 'pdf':
        $fileType = 'application/pdf';
        break;
    case 'jpg':
    case 'jpeg':
        $fileType = 'image/jpeg';
        break;
    case 'png':
        $fileType = 'image/png';
        break;
    case 'txt':
        $fileType = 'text/plain';
        break;
}

// Set headers for file download
header('Content-Type: ' . $fileType);
header('Content-Disposition: inline; filename="' . urlencode($displayName) . '"');
header('Content-Length: ' . $fileSize);
header('Cache-Control: public, max-age=3600');
header('Pragma: public');

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Output file
readfile($filePath);
exit;
?>
