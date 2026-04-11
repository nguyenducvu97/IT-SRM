<?php
echo "=== CHECK PHP ERRORS ===" . PHP_EOL;

// Check PHP error log location
echo "PHP error_log setting: " . ini_get('error_log') . PHP_EOL;
echo "PHP log_errors setting: " . (ini_get('log_errors') ? 'ON' : 'OFF') . PHP_EOL;

// Try to create a test error to verify logging
error_log("Test error log entry from check-php-errors.php");

// Check if we can find our test entry in the logs
$php_error_log = ini_get('error_log');
if ($php_error_log && file_exists($php_error_log)) {
    $logs = file_get_contents($php_error_log);
    if (strpos($logs, 'check-php-errors.php') !== false) {
        echo "Found our test entry in PHP error log" . PHP_EOL;
        
        // Get recent logs
        $recent_logs = substr($logs, -1000);
        echo "Recent PHP error logs:" . PHP_EOL;
        echo $recent_logs;
    } else {
        echo "Test entry not found in PHP error log" . PHP_EOL;
    }
} else {
    echo "PHP error log file not found or not configured" . PHP_EOL;
}

echo PHP_EOL . "=== CHECK COMPLETE ===" . PHP_EOL;
?>
