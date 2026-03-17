<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare('SELECT id, filename, original_name, file_size FROM attachments WHERE service_request_id = 38');
$stmt->execute();
$attachments = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($attachments as $att) {
    echo "ID: {$att['id']}, File: {$att['filename']}, Original: {$att['original_name']}, Size: {$att['file_size']}\n";
}
?>
