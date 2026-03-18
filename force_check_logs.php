<?php
// Force refresh and check all recent logs
header('Content-Type: text/plain');

$log_file = ini_get('error_log');
echo "=== ALL RECENT LOGS (Last 20 lines) ===\n";

if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $lines = array_reverse(explode("\n", $logs));
    
    // Show last 20 lines
    for ($i = 0; $i < min(20, count($lines)); $i++) {
        $line = $lines[$i];
        if (!empty(trim($line))) {
            echo "[$i] " . $line . "\n";
        }
    }
    
    echo "\n=== SEARCH FOR DEBUG MESSAGES ===\n";
    $all_content = file_get_contents($log_file);
    
    if (strpos($all_content, 'Final action for POST') !== false) {
        echo "Found 'Final action for POST' in logs\n";
    } else {
        echo "NOT found 'Final action for POST' in logs\n";
    }
    
    if (strpos($all_content, 'Action length') !== false) {
        echo "Found 'Action length' in logs\n";
    } else {
        echo "NOT found 'Action length' trong logs\n";
    }
    
    if (strpos($all_content, 'ENTERING REJECT_REQUEST') !== false) {
        echo "Found 'ENTERING REJECT_REQUEST' in logs\n";
    } else {
        echo "NOT found 'ENTERING REJECT_REQUEST' in logs\n";
    }
} else {
    echo "Log file not found: $log_file\n";
}
?>
