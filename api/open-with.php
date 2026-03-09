<?php
// Alternative method to trigger "Open with" dialog
header('Content-Type: application/unknown');
header('Content-Disposition: inline; filename="' . basename($_GET['file']) . '"');
header('Content-Transfer-Encoding: binary');

// Get file path
$fileName = $_GET['file'] ?? '';
$uploadsDir = __DIR__ . '/../uploads/requests/';
$filePath = $uploadsDir . $fileName;

// Security check
$realBasePath = realpath($uploadsDir);
$realFilePath = realpath($filePath);

if ($realFilePath === false || strpos($realFilePath, $realBasePath) !== 0) {
    http_response_code(403);
    echo 'Access denied';
    exit;
}

if (!file_exists($filePath)) {
    http_response_code(404);
    echo 'File not found';
    exit;
}

// Get file info
$fileSize = filesize($filePath);
$mimeType = mime_content_type($filePath);

// Set proper content type but use inline disposition to trigger "Open with"
header('Content-Type: ' . $mimeType);
header('Content-Disposition: inline; filename="' . basename($filePath) . '"');
header('Content-Length: ' . $fileSize);

// Clear output buffer
if (ob_get_level()) {
    ob_end_clean();
}

// Output file
readfile($filePath);
?>
