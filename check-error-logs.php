<?php
echo "=== CHECK ERROR LOGS ===" . PHP_EOL;

$error_log_path = 'C:/xampp/apache/logs/error.log';
if (file_exists($error_log_path)) {
    $logs = file_get_contents($error_log_path);
    $recent_logs = substr($logs, -2000); // Last 2000 characters
    
    echo "Recent error logs:" . PHP_EOL;
    echo $recent_logs;
} else {
    echo "Error log file not found at: {$error_log_path}" . PHP_EOL;
}

echo PHP_EOL . "=== CHECK COMPLETE ===" . PHP_EOL;
?>
