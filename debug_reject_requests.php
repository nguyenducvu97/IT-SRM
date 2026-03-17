<?php
// Debug reject_requests.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG REJECT REQUESTS ===\n";

try {
    // Simulate the request
    $_GET['action'] = 'list';
    $_GET['status'] = 'pending';
    
    echo "GET data: " . print_r($_GET, true) . "\n";
    
    // Test database connection
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "Database connection failed\n";
    } else {
        echo "Database connection successful\n";
    }
    
    // Test session
    require_once 'config/session.php';
    startSession();
    echo "Session started: " . session_id() . "\n";
    
    // Test include PHPMailerEmailHelper
    require_once 'lib/PHPMailerEmailHelper.php';
    echo "PHPMailerEmailHelper included successfully\n";
    
    // Test the actual API
    echo "Including reject_requests.php...\n";
    include 'api/reject_requests.php';
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
?>
