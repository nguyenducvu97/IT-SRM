<?php
// Read the last 100 lines of api_errors.log
$logFile = 'logs/api_errors.log';
$lines = file($logFile);
$lastLines = array_slice($lines, -100);

echo '<h1>Last 100 lines of api_errors.log</h1>';
echo '<pre>';
foreach ($lastLines as $line) {
    echo htmlspecialchars($line);
}
echo '</pre>';
?>
