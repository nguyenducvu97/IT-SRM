<?php
// Simple test to check database connection
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Content-Type: application/json; charset=UTF-8");

try {
    require_once 'config/database.php';
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed'
        ]);
    } else {
        echo json_encode([
            'success' => true,
            'message' => 'Database connection successful'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Exception: ' . $e->getMessage()
    ]);
}
?>
