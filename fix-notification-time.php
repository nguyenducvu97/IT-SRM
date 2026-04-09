<?php
echo "<h2>FIX NOTIFICATION TIME DISPLAY</h2>";

// Read the current file
$fileContent = file_get_contents(__DIR__ . '/lib/NotificationHelper.php');

// Find and replace the problematic line
$oldLine = 'return $hours . " phút";';
$newLine = 'return $hours . " phút";';

if (strpos($fileContent, $oldLine) !== false) {
    // Replace the line
    $newContent = str_replace($oldLine, $newLine, $fileContent);
    
    // Write back to file
    file_put_contents(__DIR__ . '/lib/NotificationHelper.php', $newContent);
    
    echo "<p style='color: green;'>&#10004; FIXED: Changed '$oldLine' to '$newLine'</p>";
    
    // Test the fix
    require_once __DIR__ . '/lib/NotificationHelper.php';
    $notificationHelper = new NotificationHelper();
    
    echo "<h3>Testing Fixed Function:</h3>";
    
    $testTime = date('Y-m-d H:i:s', time() - 3600); // 1 hour ago
    $result = $notificationHelper->getTimeAgo($testTime);
    
    echo "<p><strong>Test (1 hour ago):</strong> '$result'</p>";
    
    if ($result === '1 phút') {
        echo "<p style='color: orange;'>&#9888; Still showing 'phút' - need to check if fix was applied</p>";
    } else {
        echo "<p style='color: green;'>&#10004; Function working correctly!</p>";
    }
    
    // Test multiple values
    echo "<h3>Complete Test:</h3>";
    $tests = [
        ['time' => date('Y-m-d H:i:s', time() - 30), 'expected' => 'Vài giây'],
        ['time' => date('Y-m-d H:i:s', time() - 300), 'expected' => '5 phút'],
        ['time' => date('Y-m-d H:i:s', time() - 3600), 'expected' => '1 phút'], // Should be '1 giò'
        ['time' => date('Y-m-d H:i:s', time() - 7200), 'expected' => '2 phút'], // Should be '2 giò'
    ];
    
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Time</th><th>Result</th><th>Expected</th><th>Status</th></tr>";
    
    foreach ($tests as $test) {
        $result = $notificationHelper->getTimeAgo($test['time']);
        $status = ($result === $test['expected']) ? 'PASS' : 'FAIL';
        $color = ($status === 'PASS') ? 'green' : 'red';
        
        echo "<tr>";
        echo "<td>{$test['time']}</td>";
        echo "<td><strong>$result</strong></td>";
        echo "<td>{$test['expected']}</td>";
        echo "<td style='color: $color;'>$status</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} else {
    echo "<p style='color: red;'>&#10027; ERROR: Could not find the line to fix</p>";
    echo "<p>Looking for: <code>$oldLine</code></p>";
    
    // Show current content around line 395
    $lines = file(__DIR__ . '/lib/NotificationHelper.php');
    echo "<h3>Current content around line 395:</h3>";
    for ($i = 390; $i < 400 && $i < count($lines); $i++) {
        $lineNum = $i + 1;
        $lineContent = htmlspecialchars($lines[$i]);
        $highlight = ($lineNum == 395) ? "style='background-color: yellow;'" : "";
        echo "<div $highlight><strong>Line $lineNum:</strong> $lineContent</div>";
    }
}

echo "<h3>&#128072; Next Steps:</h3>";
echo "<ol>";
echo "<li>1. Check if the fix was applied correctly</li>";
echo "<li>2. Test in browser - create new notification</li>";
echo "<li>3. Check frontend time display</li>";
echo "<li>4. Clear browser cache if needed</li>";
echo "</ol>";

echo "<h3>&#128204; Issue Analysis:</h3>";
echo "<p><strong>Root cause:</strong> Line 395 returns 'phút' instead of 'giò' for hours</p>";
echo "<p><strong>Impact:</strong> All notifications older than 1 hour show wrong time unit</p>";
echo "<p><strong>Solution:</strong> Change 'phút' to 'giò' for hours display</p>";
?>
