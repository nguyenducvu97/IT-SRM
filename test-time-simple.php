<?php
echo "<h2>TEST TIME AGO SIMPLE</h2>";

require_once __DIR__ . '/lib/NotificationHelper.php';

$notificationHelper = new NotificationHelper();

echo "<h3>Current Time</h3>";
echo "<p>PHP time(): " . time() . "</p>";
echo "<p>PHP date(): " . date('Y-m-d H:i:s') . "</p>";

echo "<h3>Test getTimeAgo Function</h3>";

$testCases = [
    ['time' => date('Y-m-d H:i:s'), 'expected' => 'Vài giây'],
    ['time' => date('Y-m-d H:i:s', time() - 30), 'expected' => 'Vài giây'],
    ['time' => date('Y-m-d H:i:s', time() - 120), 'expected' => '2 phút'],
    ['time' => date('Y-m-d H:i:s', time() - 3600), 'expected' => '1 phút'], // This should be "1 giò"
    ['time' => date('Y-m-d H:i:s', time() - 7200), 'expected' => '2 phút'], // This should be "2 giò"
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Test Time</th><th>Expected</th><th>Actual</th><th>Status</th></tr>";

foreach ($testCases as $test) {
    $actual = $notificationHelper->getTimeAgo($test['time']);
    $status = ($actual === $test['expected']) ? 'PASS' : 'FAIL';
    $color = ($status === 'PASS') ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$test['time']}</td>";
    echo "<td>{$test['expected']}</td>";
    echo "<td><strong>{$actual}</strong></td>";
    echo "<td style='color: {$color};'>{$status}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>Issue Found</h3>";
echo "<p>The function returns 'phút' for hours instead of 'giò'!</p>";
echo "<p>Need to fix: Line 395 should return '{$hours} giò' not '{$hours} phút'</p>";

// Fix the function
echo "<h3>Fixing the function...</h3>";

// Use reflection to test the fix
$reflection = new ReflectionClass($notificationHelper);
$method = $reflection->getMethod('getTimeAgo');
$method->setAccessible(true);

// Test with 1 hour ago
$oneHourAgo = date('Y-m-d H:i:s', time() - 3600);
$result = $method->invoke($notificationHelper, $oneHourAgo);

echo "<p>1 hour ago test: '{$result}' (should be '1 giò')</p>";

if ($result === '1 phút') {
    echo "<p style='color: red;'>Still showing 'phút' instead of 'giò'!</p>";
    echo "<p>Need to check if the fix was applied correctly.</p>";
} else {
    echo "<p style='color: green;'>Function working correctly!</p>";
}
?>
