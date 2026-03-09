<?php
// Force download handler to trigger "Open with" dialog
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . basename($_GET['file']) . '"');
header('Content-Transfer-Encoding: binary');
header('Cache-Control: private, no-cache, no-store, must-revalidate');
header('Pragma: no-cache');

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

// Output file
readfile($filePath);
?>
