<?php
// Script to check recent error logs
header('Content-Type: text/plain');

echo "=== Recent Error Logs ===\n";

$log_file = ini_get('error_log');
echo "Log file: $log_file\n\n";

if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $recent_logs = substr($logs, -5000); // Last 5000 characters
    
    // Filter for our debug messages
    $lines = explode("\n", $recent_logs);
    $relevant_lines = [];
    
    foreach ($lines as $line) {
        if (strpos($line, 'POST FormData action:') !== false ||
            strpos($line, 'Final action for POST:') !== false ||
            strpos($line, 'Checking if action') !== false ||
            strpos($line, 'POST all data:') !== false) {
            $relevant_lines[] = $line;
        }
    }
    
    if (!empty($relevant_lines)) {
        echo "=== Relevant Debug Logs ===\n";
        foreach ($relevant_lines as $line) {
            echo $line . "\n";
        }
    } else {
        echo "No relevant debug logs found.\n";
        echo "\n=== Last 20 lines ===\n";
        $all_lines = array_slice(explode("\n", $recent_logs), -20);
        foreach ($all_lines as $line) {
            echo $line . "\n";
        }
    }
} else {
    echo "Log file not found.\n";
}
?>
