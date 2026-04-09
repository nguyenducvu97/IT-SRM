<?php
require_once 'config/database.php';
$db = getDatabaseConnection();

// Check the latest request
$query = "SELECT id, title, created_at FROM service_requests 
         WHERE user_id = 4 AND title LIKE '%File Upload Test%'
         ORDER BY created_at DESC 
         LIMIT 1";
$stmt = $db->prepare($query);
$stmt->execute();
$request = $stmt->fetch(PDO::FETCH_ASSOC);

if ($request) {
    echo "Latest request: ID {$request['id']} - {$request['title']}\n";
    
    // Check attachments for this request
    $attach_query = "SELECT original_name, filename, file_size, mime_type FROM attachments 
                     WHERE request_id = :request_id";
    $attach_stmt = $db->prepare($attach_query);
    $attach_stmt->bindParam(":request_id", $request['id']);
    $attach_stmt->execute();
    $attachments = $attach_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Attachments found: " . count($attachments) . "\n";
    
    foreach ($attachments as $attachment) {
        echo "- {$attachment['original_name']} ({$attachment['file_size']} bytes)\n";
        echo "  Stored as: {$attachment['filename']}\n";
        echo "  Type: {$attachment['mime_type']}\n";
    }
    
    if (count($attachments) > 0) {
        echo "\nFile upload working!\n";
    } else {
        echo "\nNo attachments found - file upload issue\n";
    }
} else {
    echo "No file upload test request found\n";
}

// Check if uploads directory exists
$uploads_dir = '../uploads/attachments/';
if (is_dir($uploads_dir)) {
    echo "Uploads directory exists\n";
    
    // List subdirectories
    $dirs = glob($uploads_dir . '*', GLOB_ONLYDIR);
    echo "Request directories: " . count($dirs) . "\n";
    
    // Check latest request directory
    if (isset($request) && is_dir($uploads_dir . $request['id'])) {
        echo "Directory for request {$request['id']} exists\n";
        
        $files = glob($uploads_dir . $request['id'] . '/*');
        echo "Files in directory: " . count($files) . "\n";
        
        foreach ($files as $file) {
            echo "- " . basename($file) . "\n";
        }
    }
} else {
    echo "Uploads directory does not exist\n";
}
?>
