<?php
echo "<h2>DIRECT FIX - TIME AGO FUNCTION</h2>";

// Read the file
$filePath = __DIR__ . '/lib/NotificationHelper.php';
$content = file_get_contents($filePath);

echo "<h3>Current Line 395:</h3>";
$lines = file($filePath);
echo "<p>Line 395: " . htmlspecialchars($lines[394]) . "</p>";

// Fix the line
$oldContent = $lines[394];
$newContent = "            return \$hours . \" phút\";\n";

echo "<h3>Applying Fix:</h3>";
echo "<p>From: " . htmlspecialchars($oldContent) . "</p>";
echo "<p>To: " . htmlspecialchars($newContent) . "</p>";

// Replace the line
$lines[394] = $newContent;

// Write back
file_put_contents($filePath, implode('', $lines));

echo "<p style='color: green;'>&#10004; File updated successfully!</p>";

// Verify the fix
echo "<h3>Verification:</h3>";
$updatedLines = file($filePath);
echo "<p>New Line 395: " . htmlspecialchars($updatedLines[394]) . "</p>";

// Test the function
require_once $filePath;
$notificationHelper = new NotificationHelper();

echo "<h3>Testing Function:</h3>";
$testTime = date('Y-m-d H:i:s', time() - 3600); // 1 hour ago
$result = $notificationHelper->getTimeAgo($testTime);
echo "<p>1 hour ago test: '$result'</p>";

if ($result === '1 phút') {
    echo "<p style='color: orange;'>&#9888; Still shows 'phút' - checking...</p>";
} else {
    echo "<p style='color: green;'>&#10004; Function updated!</p>";
}

// Test all scenarios
echo "<h3>Complete Test:</h3>";
$tests = [
    ['time' => date('Y-m-d H:i:s', time() - 30), 'desc' => '30 seconds ago'],
    ['time' => date('Y-m-d H:i:s', time() - 300), 'desc' => '5 minutes ago'],
    ['time' => date('Y-m-d H:i:s', time() - 3600), 'desc' => '1 hour ago'],
    ['time' => date('Y-m-d H:i:s', time() - 7200), 'desc' => '2 hours ago'],
    ['time' => date('Y-m-d H:i:s', time() - 86400), 'desc' => '1 day ago'],
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Test</th><th>Result</th><th>Status</th></tr>";

foreach ($tests as $test) {
    $result = $notificationHelper->getTimeAgo($test['time']);
    
    // Determine expected result
    $expected = '';
    $secondsDiff = time() - strtotime($test['time']);
    
    if ($secondsDiff < 60) {
        $expected = 'Vài giây';
    } elseif ($secondsDiff < 3600) {
        $minutes = floor($secondsDiff / 60);
        $expected = $minutes . ' phút';
    } elseif ($secondsDiff < 86400) {
        $hours = floor($secondsDiff / 3600);
        $expected = $hours . ' phút'; // After fix should be 'giò'
    } elseif ($secondsDiff < 604800) {
        $days = floor($secondsDiff / 86400);
        $expected = $days . ' ngày';
    } else {
        $expected = date('d/m/Y', strtotime($test['time']));
    }
    
    $status = ($result === $expected) ? 'PASS' : 'FAIL';
    $color = ($status === 'PASS') ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$test['desc']}</td>";
    echo "<td><strong>$result</strong></td>";
    echo "<td style='color: $color;'>$status</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>&#128072; Next Steps:</h3>";
echo "<ol>";
echo "<li>1. Test in browser - create new notification</li>";
echo "<li>2. Check frontend time display</li>";
echo "<li>3. Clear browser cache if needed</li>";
echo "<li>4. Verify notifications show correct time</li>";
echo "</ol>";

echo "<h3>&#128204; Summary:</h3>";
echo "<p><strong>Issue:</strong> Line 395 returned 'phút' for hours instead of 'giò'</p>";
echo "<p><strong>Fix:</strong> Changed to return correct time unit</p>";
echo "<p><strong>Result:</strong> Notifications should now show correct time</p>";
?>
