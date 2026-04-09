<?php
// Check recent logs for notification debug
echo "<h1>Recent Notification Debug Logs</h1>";

$logFile = 'logs/api_errors.log';
if (file_exists($logFile)) {
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    
    echo "<h2>Last 100 lines with notification keywords</h2>";
    
    $relevantLines = [];
    foreach ($lines as $line) {
        if (strpos($line, 'DEBUG') !== false || 
            strpos($line, 'notification') !== false || 
            strpos($line, 'Starting') !== false || 
            strpos($line, 'Creating') !== false || 
            strpos($line, 'Calling') !== false || 
            strpos($line, 'Result') !== false ||
            strpos($line, 'QUICK FIX') !== false) {
            $relevantLines[] = $line;
        }
    }
    
    $recentLines = array_slice($relevantLines, -50);
    
    echo "<pre style='background: #f0f0f0; padding: 10px; max-height: 500px; overflow-y: auto; font-size: 12px;'>";
    foreach ($recentLines as $line) {
        if (!empty(trim($line))) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
    
    echo "<h2>Last 20 lines overall</h2>";
    $lastLines = array_slice($lines, -20);
    echo "<pre style='background: #f5f5f5; padding: 10px; max-height: 300px; overflow-y: auto; font-size: 12px;'>";
    foreach ($lastLines as $line) {
        if (!empty(trim($line))) {
            echo htmlspecialchars($line) . "\n";
        }
    }
    echo "</pre>";
    
} else {
    echo "<p>Error log file not found</p>";
}
?>
