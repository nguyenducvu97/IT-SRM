<?php
echo "<h1>Test with Custom Logger</h1>";

// Custom logger function
function custom_log($message) {
    $log_file = __DIR__ . '/logs/notification_debug.log';
    $timestamp = date('Y-m-d H:i:s');
    $log_message = "[{$timestamp}] {$message}\n";
    file_put_contents($log_file, $log_message, FILE_APPEND);
    // Also print to screen
    echo "<p style='color: blue;'>LOG: {$message}</p>";
}

require_once 'config/database.php';
require_once 'lib/ServiceRequestNotificationHelper.php';

try {
    custom_log("=== START TEST ===");
    
    $notificationHelper = new ServiceRequestNotificationHelper();
    custom_log("ServiceRequestNotificationHelper created");
    
    $test_request_id = 777;
    $test_title = "Logger Test Request";
    $test_requester = "Logger Test User";
    $test_category = "Logger Test Category";
    
    custom_log("Calling notifyStaffNewRequest with request_id={$test_request_id}");
    
    $start_time = microtime(true);
    $result = $notificationHelper->notifyStaffNewRequest(
        $test_request_id,
        $test_title,
        $test_requester,
        $test_category
    );
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    custom_log("notifyStaffNewRequest result: " . ($result ? "SUCCESS" : "FAILED") . " ({$execution_time}ms)");
    
    echo "<p>Result: " . ($result ? "✅ SUCCESS" : "❌ FAILED") . " ({$execution_time}ms)</p>";
    
    // Check log file
    echo "<h3>Log File Contents:</h3>";
    $log_file = __DIR__ . '/logs/notification_debug.log';
    if (file_exists($log_file)) {
        $log_contents = file_get_contents($log_file);
        echo "<pre style='background: #f0f0f0; padding: 10px; overflow: auto; max-height: 500px;'>";
        echo htmlspecialchars($log_contents);
        echo "</pre>";
    } else {
        echo "<p style='color: red;'>Log file not found</p>";
    }
    
} catch (Exception $e) {
    custom_log("EXCEPTION: " . $e->getMessage());
    echo "<p style='color: red;'>Exception: " . $e->getMessage() . "</p>";
    echo "<p>File: " . $e->getFile() . "</p>";
    echo "<p>Line: " . $e->getLine() . "</p>";
    echo "<p>Stack trace:</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

custom_log("=== END TEST ===");

echo "<hr>";
echo "<p><a href='index.html'>Back to Main Application</a></p>";
?>
