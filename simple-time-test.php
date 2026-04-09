<?php
echo "<h2>SIMPLE TIME TEST</h2>";

require_once __DIR__ . '/lib/NotificationHelper.php';
$notificationHelper = new NotificationHelper();

echo "<h3>Current Time:</h3>";
echo "<p>PHP time(): " . time() . "</p>";
echo "<p>PHP date(): " . date('Y-m-d H:i:s') . "</p>";

echo "<h3>Test getTimeAgo Function:</h3>";

$tests = [
    ['time' => date('Y-m-d H:i:s'), 'desc' => 'Now'],
    ['time' => date('Y-m-d H:i:s', time() - 30), 'desc' => '30s ago'],
    ['time' => date('Y-m-d H:i:s', time() - 300), 'desc' => '5m ago'],
    ['time' => date('Y-m-d H:i:s', time() - 3600), 'desc' => '1h ago'],
    ['time' => date('Y-m-d H:i:s', time() - 7200), 'desc' => '2h ago'],
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Test</th><th>Time</th><th>Result</th><th>Expected</th></tr>";

foreach ($tests as $test) {
    $result = $notificationHelper->getTimeAgo($test['time']);
    
    // Calculate expected
    $secondsDiff = time() - strtotime($test['time']);
    if ($secondsDiff < 60) {
        $expected = 'Vài giây';
    } elseif ($secondsDiff < 3600) {
        $minutes = floor($secondsDiff / 60);
        $expected = $minutes . ' phút';
    } elseif ($secondsDiff < 86400) {
        $hours = floor($secondsDiff / 3600);
        $expected = $hours . ' phút';
    } else {
        $expected = date('d/m/Y', strtotime($test['time']));
    }
    
    $status = ($result === $expected) ? 'PASS' : 'FAIL';
    $color = ($status === 'PASS') ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$test['desc']}</td>";
    echo "<td>{$test['time']}</td>";
    echo "<td><strong>$result</strong></td>";
    echo "<td>$expected</td>";
    echo "<td style='color: $color;'>$status</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Issue Analysis:</h3>";

// Check if function is working correctly
$oneHourAgo = date('Y-m-d H:i:s', time() - 3600);
$result = $notificationHelper->getTimeAgo($oneHourAgo);

echo "<p><strong>1 hour ago test:</strong> '$result'</p>";

if ($result === 'Vài giây') {
    echo "<p style='color: red;'>&#10027; PROBLEM FOUND: getTimeAgo returns 'Vài giây' for 1 hour ago!</p>";
    echo "<p>This means the function logic is broken.</p>";
    
    // Check the function source
    echo "<h3>Function Source Check:</h3>";
    $source = file_get_contents(__DIR__ . '/lib/NotificationHelper.php');
    
    // Find the getTimeAgo function
    $startPos = strpos($source, 'private function getTimeAgo');
    $endPos = strpos($source, '}', $startPos) + 1;
    $functionCode = substr($source, $startPos, $endPos - $startPos);
    
    echo "<pre style='background-color: #f5f5f5; padding: 10px;'>";
    echo htmlspecialchars($functionCode);
    echo "</pre>";
    
} elseif ($result === '1 phút') {
    echo "<p style='color: green;'>&#10004; Function working correctly!</p>";
    echo "<p>The issue might be elsewhere (frontend, API, or database).</p>";
} else {
    echo "<p style='color: orange;'>&#9888; Unexpected result: '$result'</p>";
}

echo "<h3>Solution:</h3>";
echo "<p><strong>If function returns 'Vài giây' incorrectly:</strong></p>";
echo "<ol>";
echo "<li>1. Check timezone settings</li>";
echo "<li>2. Check strtotime() parsing</li>";
echo "<li>3. Check time() function</li>";
echo "<li>4. Debug the diff calculation</li>";
echo "</ol>";

echo "<p><strong>If function works correctly:</strong></p>";
echo "<ol>";
echo "<li>1. Check API response</li>";
echo "<li>2. Check frontend display</li>";
echo "<li>3. Check browser cache</li>";
echo "<li>4. Check notification creation time</li>";
echo "</ol>";
?>
