<?php
header('Content-Type: application/json');

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Test database connection
    require_once 'api/config.php';
    $db = getDB();
    
    echo json_encode([
        'success' => true,
        'message' => 'Database connection successful',
        'database' => 'Connected'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed',
        'error' => $e->getMessage()
    ]);
}
?>
