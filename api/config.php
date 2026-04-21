<?php
// Database configuration for API
require_once __DIR__ . '/../config/database.php';

function getDB() {
    $database = new Database();
    return $database->getConnection();
}
?>
