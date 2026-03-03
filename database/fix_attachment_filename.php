<?php
require_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Fixing attachment filename...\n";

// Update the attachment record to match the actual file
$query = "UPDATE attachments SET filename = '699e7987e3888_1771993479.jpg' WHERE id = 1";
$stmt = $db->prepare($query);

if ($stmt->execute()) {
    echo "Attachment filename updated successfully!\n";
} else {
    echo "Failed to update attachment filename.\n";
    echo "Error: " . implode(", ", $stmt->errorInfo()) . "\n";
}

// Verify the update
$query = "SELECT * FROM attachments WHERE id = 1";
$stmt = $db->prepare($query);
$stmt->execute();
$attachment = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\nUpdated attachment record:\n";
echo "- ID: {$attachment['id']}\n";
echo "- Original Name: {$attachment['original_name']}\n";
echo "- Filename: {$attachment['filename']}\n";
echo "- MIME Type: {$attachment['mime_type']}\n";
echo "- File exists: " . (file_exists("../uploads/requests/{$attachment['filename']}") ? "YES" : "NO") . "\n";
?>
