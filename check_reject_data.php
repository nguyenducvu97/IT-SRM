<?php
// Check reject requests and attachments
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "=== REJECT REQUESTS ===\n";
$reject_query = "SELECT * FROM reject_requests ORDER BY created_at DESC LIMIT 5";
$reject_stmt = $db->prepare($reject_query);
$reject_stmt->execute();
$reject_requests = $reject_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($reject_requests)) {
    echo "No reject requests found\n";
} else {
    foreach ($reject_requests as $req) {
        echo "ID: {$req['id']}, Request ID: {$req['service_request_id']}, Status: {$req['status']}, Created: {$req['created_at']}\n";
    }
}

echo "\n=== REJECT REQUEST ATTACHMENTS ===\n";
$attachment_query = "SELECT * FROM reject_request_attachments ORDER BY created_at DESC LIMIT 5";
$attachment_stmt = $db->prepare($attachment_query);
$attachment_stmt->execute();
$attachments = $attachment_stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($attachments)) {
    echo "No reject request attachments found\n";
} else {
    foreach ($attachments as $att) {
        echo "ID: {$att['id']}, Reject ID: {$att['reject_request_id']}, File: {$att['original_name']}, Size: {$att['file_size']}, Created: {$att['created_at']}\n";
    }
}

echo "\n=== UPLOADS DIRECTORY ===\n";
$uploads_dir = __DIR__ . '/uploads/reject_requests/';
if (file_exists($uploads_dir)) {
    $files = scandir($uploads_dir);
    echo "Files in uploads/reject_requests/:\n";
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            $filepath = $uploads_dir . $file;
            if (is_file($filepath)) {
                echo "- $file (" . filesize($filepath) . " bytes)\n";
            }
        }
    }
} else {
    echo "Uploads directory does not exist\n";
}

echo "\n=== RECENT API CALLS ===\n";
// Check if there were any recent reject requests
$recent_query = "SELECT COUNT(*) as count FROM reject_requests WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
$recent_stmt = $db->prepare($recent_query);
$recent_stmt->execute();
$recent_count = $recent_stmt->fetch(PDO::FETCH_ASSOC)['count'];

echo "Reject requests in last hour: $recent_count\n";
?>
