<?php
require_once 'config/database.php';

try {
    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "=== REJECT REQUEST ATTACHMENTS ===\n";
    $stmt = $db->query('SELECT * FROM reject_request_attachments ORDER BY uploaded_at DESC LIMIT 5');
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($attachments) . "\n";
    foreach ($attachments as $att) {
        echo "ID: {$att['id']}, Filename: {$att['original_name']}, Uploaded: {$att['uploaded_at']}\n";
    }
    
    echo "\n=== REJECT REQUESTS ===\n";
    $stmt = $db->query('SELECT * FROM reject_requests ORDER BY created_at DESC LIMIT 3');
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Count: " . count($requests) . "\n";
    foreach ($requests as $req) {
        echo "ID: {$req['id']}, Request ID: {$req['service_request_id']}, Reason: {$req['reject_reason']}, Attachments: {$req['attachments']}\n";
    }
    
    echo "\n=== UPLOAD DIRECTORY ===\n";
    $uploadDir = 'uploads/reject_requests/';
    if (is_dir($uploadDir)) {
        $files = scandir($uploadDir);
        echo "Files in upload directory: " . (count($files) - 2) . "\n"; // -2 for . and ..
        foreach ($files as $file) {
            if ($file !== '.' && $file !== '..') {
                echo "- $file\n";
            }
        }
    } else {
        echo "Upload directory does not exist\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
