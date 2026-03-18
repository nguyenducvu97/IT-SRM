<?php
// Check latest logs for reject request
header('Content-Type: text/plain');

$log_file = ini_get('error_log');
echo "=== Latest Error Logs ===\n";

if (file_exists($log_file)) {
    $logs = file_get_contents($log_file);
    $lines = array_reverse(explode("\n", $logs));
    
    $relevant_lines = [];
    foreach ($lines as $line) {
        if (strpos($line, 'POST FormData action:') !== false ||
            strpos($line, 'ENTERING REJECT_REQUEST BRANCH!') !== false ||
            strpos($line, 'Final action for POST:') !== false ||
            strpos($line, 'Checking if action') !== false) {
            $relevant_lines[] = $line;
        }
        
        // Stop after getting enough lines
        if (count($relevant_lines) >= 10) break;
    }
    
    if (!empty($relevant_lines)) {
        echo "=== Recent Relevant Logs ===\n";
        foreach (array_reverse($relevant_lines) as $line) {
            echo $line . "\n";
        }
    } else {
        echo "No relevant logs found.\n";
    }
    
    echo "\n=== Last 5 Error Lines ===\n";
    $error_lines = array_slice($lines, 0, 5);
    foreach ($error_lines as $line) {
        if (!empty(trim($line))) {
            echo $line . "\n";
        }
    }
} else {
    echo "Log file not found.\n";
}
?>
