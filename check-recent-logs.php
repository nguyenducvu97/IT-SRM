<?php
/**
 * Check recent logs for staff accept request debugging
 */

echo "<h2>🔍 KIỂM TRA LOGS GẦN ĐÂY</h2>";
echo "<style>
    table { border-collapse: collapse; width: 100%; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-family: monospace; font-size: 12px; }
    th { background-color: #f2f2f2; }
    .highlight { background-color: yellow; font-weight: bold; }
    .error { color: red; }
    .success { color: green; }
</style>";

// Check error log file
$logFile = 'C:/xampp/apache/logs/error.log';

if (file_exists($logFile)) {
    echo "<h3>📋 50 lines cuối của error.log:</h3>";
    
    $logs = file_get_contents($logFile);
    $lines = explode("\n", $logs);
    $recentLines = array_slice($lines, -50);
    
    echo "<table>";
    echo "<tr><th>Log Entry</th></tr>";
    
    foreach ($recentLines as $line) {
        if (trim($line)) {
            $highlight = '';
            if (strpos($line, '132') !== false) {
                $highlight = "class='highlight'";
            } elseif (strpos($line, 'STAFF ACCEPT REQUEST') !== false) {
                $highlight = "class='success'";
            } elseif (strpos($line, 'NOTIFICATION DEBUG') !== false) {
                $highlight = "class='success'";
            } elseif (strpos($line, 'ERROR') !== false || strpos($line, 'Failed') !== false) {
                $highlight = "class='error'";
            }
            
            echo "<tr $highlight><td>" . htmlspecialchars($line) . "</td></tr>";
        }
    }
    echo "</table>";
    
    // Search specifically for request 132
    echo "<h3>🔍 Tìm kiếm '132' trong logs:</h3>";
    $found132 = false;
    foreach ($lines as $line) {
        if (strpos($line, '132') !== false) {
            echo "<p class='highlight'>" . htmlspecialchars($line) . "</p>";
            $found132 = true;
        }
    }
    
    if (!$found132) {
        echo "<p class='error'>❌ Không tìm thấy '132' trong logs</p>";
    }
    
    // Search for staff accept request
    echo "<h3>🔍 Tìm kiếm 'STAFF ACCEPT REQUEST' trong logs:</h3>";
    $foundAccept = false;
    foreach ($lines as $line) {
        if (strpos($line, 'STAFF ACCEPT REQUEST') !== false) {
            echo "<p class='success'>" . htmlspecialchars($line) . "</p>";
            $foundAccept = true;
        }
    }
    
    if (!$foundAccept) {
        echo "<p class='error'>❌ Không tìm thấy 'STAFF ACCEPT REQUEST' trong logs</p>";
    }
    
} else {
    echo "<p class='error'>❌ Không tìm thấy file log: $logFile</p>";
}

// Check PHP error log
$phpLogFile = 'C:/xampp/php/logs/php_error_log';

if (file_exists($phpLogFile)) {
    echo "<h3>📋 PHP Error Log (20 lines cuối):</h3>";
    
    $phpLogs = file_get_contents($phpLogFile);
    $phpLines = explode("\n", $phpLogs);
    $recentPhpLines = array_slice($phpLines, -20);
    
    echo "<table>";
    echo "<tr><th>PHP Log Entry</th></tr>";
    
    foreach ($recentPhpLines as $line) {
        if (trim($line)) {
            $highlight = '';
            if (strpos($line, '132') !== false) {
                $highlight = "class='highlight'";
            } elseif (strpos($line, 'NOTIFICATION') !== false) {
                $highlight = "class='success'";
            } elseif (strpos($line, 'ERROR') !== false || strpos($line, 'Fatal') !== false) {
                $highlight = "class='error'";
            }
            
            echo "<tr $highlight><td>" . htmlspecialchars($line) . "</td></tr>";
        }
    }
    echo "</table>";
} else {
    echo "<p class='error'>❌ Không tìm thấy PHP error log</p>";
}

echo "<h3>🔍 Kết luận:</h3>";
echo "<p><strong>Nếu không thấy logs 'STAFF ACCEPT REQUEST' hoặc '132':</strong></p>";
echo "<ul>";
echo "<li>Debug logging chưa được kích hoạt khi staff nhận yêu cầu #132</li>";
echo "<li>Có thể staff nhận yêu cầu qua cách khác (không qua API endpoint)</li>";
echo "<li>Browser đang load version cũ của JavaScript</li>";
echo "<li>Cần clear browser cache và thử lại</li>";
echo "</ul>";
?>
