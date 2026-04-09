<?php
require_once 'config/database.php';
$db = getDatabaseConnection();

// Check attachments for request 45
$stmt = $db->prepare('SELECT COUNT(*) as count FROM attachments WHERE request_id = 45');
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);

echo "Attachments for request 45: " . $result['count'] . "\n";

if ($result['count'] > 0) {
    $attach_stmt = $db->prepare('SELECT original_name, filename, file_size FROM attachments WHERE request_id = 45');
    $attach_stmt->execute();
    $attachments = $attach_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($attachments as $attachment) {
        echo "- {$attachment['original_name']} ({$attachment['file_size']} bytes)\n";
    }
} else {
    echo "No attachments found\n";
}
?>
