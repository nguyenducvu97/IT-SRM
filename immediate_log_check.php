<?php
// Immediate log check
header('Content-Type: text/plain');

$log_file = ini_get('error_log');
echo "=== IMMEDIATE LOG CHECK ===\n";
echo "Log file: $log_file\n\n";

if (file_exists($log_file)) {
    // Get last modified time
    $mod_time = filemtime($log_file);
    echo "Last modified: " . date('Y-m-d H:i:s', $mod_time) . "\n";
    echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";
    
    // Read file
    $content = file_get_contents($log_file);
    $lines = explode("\n", $content);
    
    echo "=== LAST 15 LINES ===\n";
    $last_lines = array_slice($lines, -15);
    foreach ($last_lines as $line) {
        if (!empty(trim($line))) {
            echo $line . "\n";
        }
    }
    
    echo "\n=== SEARCH FOR CRITICAL DEBUG ===\n";
    $found_critical = false;
    for ($i = count($lines) - 1; $i >= 0; $i--) {
        $line = $lines[$i];
        if (strpos($line, '=== CRITICAL DEBUG ===') !== false) {
            $found_critical = true;
            // Show this and next 10 lines
            for ($j = $i; $j < min($i + 12, count($lines)); $j++) {
                echo $lines[$j] . "\n";
            }
            break;
        }
    }
    
    if (!$found_critical) {
        echo "No CRITICAL DEBUG found in logs.\n";
        echo "This means either:\n";
        echo "1. Error logging is disabled\n";
        echo "2. File permissions issue\n";
        echo "3. API not being called\n";
        echo "4. Logs are cached\n\n";
        
        // Try to write a test log
        $test_log = "TEST LOG ENTRY " . date('Y-m-d H:i:s');
        $write_result = error_log($test_log);
        echo "Test log write result: " . ($write_result ? 'SUCCESS' : 'FAILED') . "\n";
        
        // Check if test log appears
        sleep(1);
        $new_content = file_get_contents($log_file);
        if (strpos($new_content, $test_log) !== false) {
            echo "Test log successfully written!\n";
        } else {
            echo "Test log NOT found - logging issue detected!\n";
        }
    }
} else {
    echo "Log file does not exist!\n";
    
    // Try to create it
    $test_result = error_log("Creating log file test");
    echo "Log creation test: " . ($test_result ? 'SUCCESS' : 'FAILED') . "\n";
}
?>
