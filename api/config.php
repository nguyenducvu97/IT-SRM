<?php
// Set timezone to Vietnam (UTC+7)
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Database configuration for API
require_once __DIR__ . '/../config/database.php';

function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>
