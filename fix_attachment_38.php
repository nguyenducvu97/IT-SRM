<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare('UPDATE attachments SET filename = ? WHERE id = 21');
$stmt->execute(['69b8becd8d2bf_srm.png']);

echo "Updated attachment ID 21 to point to real file: 69b8becd8d2bf_srm.png";
?>
