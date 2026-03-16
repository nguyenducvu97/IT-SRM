<?php
require_once 'config/database.php';

$db = getDatabaseConnection();
$result = $db->query('SELECT COUNT(*) as total FROM service_requests');
echo 'Total requests: ' . $result->fetchColumn();
?>
