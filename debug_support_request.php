<?php
// Debug support request submission
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG SUPPORT REQUEST SUBMISSION ===\n";

try {
    // Simulate the data that would be sent
    $_POST = [
        'action' => 'create',
        'service_request_id' => '71',
        'support_type' => 'equipment',
        'support_details' => 'Test support details',
        'support_reason' => 'Test support reason'
    ];
    
    $_FILES = [
        'attachments' => [
            'name' => ['test.pdf'],
            'type' => ['application/pdf'],
            'tmp_name' => ['C:\xampp\tmp\php1234.tmp'],
            'error' => [0],
            'size' => [1234]
        ]
    ];
    
    echo "POST data: " . print_r($_POST, true) . "\n";
    echo "FILES data: " . print_r($_FILES, true) . "\n";
    
    // Test database connection
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "Database connection failed\n";
    } else {
        echo "Database connection successful\n";
    }
    
    // Test support_requests.php inclusion
    echo "Including support_requests.php...\n";
    include 'api/support_requests.php';
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
} catch (Error $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo "TRACE: " . $e->getTraceAsString() . "\n";
}
?>
