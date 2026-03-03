<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Testing API response for request with image attachment...\n";

$id = 1;
$query = "SELECT sr.*, c.name as category_name, u.full_name as requester_name, 
                        u.email as requester_email, u.phone as requester_phone,
                        assigned.full_name as assigned_name, assigned.email as assigned_email
                 FROM service_requests sr
                 LEFT JOIN categories c ON sr.category_id = c.id
                 LEFT JOIN users u ON sr.user_id = u.id
                 LEFT JOIN users assigned ON sr.assigned_to = assigned.id
                 WHERE sr.id = :id";

$stmt = $db->prepare($query);
$stmt->bindParam(":id", $id);
$stmt->execute();

if ($stmt->rowCount() > 0) {
    $request = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get attachments for this request
    $attachments_query = "SELECT id, filename, original_name, file_size, mime_type, uploaded_at 
                         FROM attachments 
                         WHERE service_request_id = :id 
                         ORDER BY uploaded_at ASC";
    $attachments_stmt = $db->prepare($attachments_query);
    $attachments_stmt->bindParam(":id", $id);
    $attachments_stmt->execute();
    
    $attachments = $attachments_stmt->fetchAll(PDO::FETCH_ASSOC);
    $request['attachments'] = $attachments;
    
    echo "Request: {$request['title']}\n";
    echo "Attachments: " . count($attachments) . "\n";
    
    foreach ($attachments as $attachment) {
        echo "- {$attachment['original_name']}\n";
        echo "  MIME: {$attachment['mime_type']}\n";
        echo "  Size: {$attachment['file_size']}\n";
        echo "  Path: uploads/requests/{$attachment['filename']}\n";
        echo "  Is Image: " . (strpos($attachment['mime_type'], 'image/') === 0 ? "YES" : "NO") . "\n";
        echo "  File exists: " . (file_exists("../uploads/requests/{$attachment['filename']}") ? "YES" : "NO") . "\n";
    }
    
    // Simulate the JavaScript logic
    echo "\nJavaScript simulation:\n";
    foreach ($attachments as $attachment) {
        $isImage = strpos($attachment['mime_type'], 'image/') === 0;
        echo "Attachment: {$attachment['original_name']} - Is Image: " . ($isImage ? "YES" : "NO") . "\n";
        if ($isImage) {
            echo "  Will show image preview with src: uploads/requests/{$attachment['filename']}\n";
        }
    }
} else {
    echo "Request not found.\n";
}
?>
