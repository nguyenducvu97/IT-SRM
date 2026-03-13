<?php
header('Content-Type: application/json');
require_once '../config/database.php';

try {
    $pdo = getDatabaseConnection();
    
    // Get all attachments from database
    $stmt = $pdo->query("SELECT id, filename, original_name FROM attachments");
    $attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $missing_files = [];
    $existing_files = [];
    $upload_dir = '../uploads/requests/';
    
    foreach ($attachments as $attachment) {
        $file_path = 'uploads/requests/' . $attachment['filename'];
        
        if (file_exists($file_path)) {
            $existing_files[] = $attachment;
        } else {
            $missing_files[] = $attachment;
            
            // Remove database record for missing file
            $delete_stmt = $pdo->prepare("DELETE FROM attachments WHERE id = ?");
            $delete_stmt->execute([$attachment['id']]);
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'total_attachments' => count($attachments),
            'existing_files' => count($existing_files),
            'missing_files' => count($missing_files),
            'missing_files_list' => $missing_files,
            'existing_files_list' => $existing_files
        ]
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
