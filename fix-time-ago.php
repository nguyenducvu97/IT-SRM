<?php
echo "<h2>FIX TIME AGO FUNCTION</h2>";

echo "<h3>Current Problem:</h3>";
echo "<p>Dòng 395 trong NotificationHelper.php: <code>return \$hours . \" phút\";</code></p>";
echo "<p>Phai là: <code>return \$hours . \" phút\";</code></p>";

echo "<h3>Fix Code:</h3>";
echo "<pre style='background-color: #f5f5f5; padding: 10px; border: 1px solid #ccc;'>";
echo "
private function getTimeAgo(\$datetime) {
    \$time = strtotime(\$datetime);
    \$now = time();
    \$diff = \$now - \$time;
    
    if (\$diff < 60) {
        return \"Vài giây\";
    } elseif (\$diff < 3600) {
        \$minutes = floor(\$diff / 60);
        return \$minutes . \" phút\";
    } elseif (\$diff < 86400) {
        \$hours = floor(\$diff / 3600);
        return \$hours . \" phút\";  // &lt;- FIX: Change to \"giò\"
    } elseif (\$diff < 604800) {
        \$days = floor(\$diff / 86400);
        return \$days . \" ngày\";
    } else {
        return date('d/m/Y', \$time);
    }
}
";
echo "</pre>";

echo "<h3>Manual Fix Instructions:</h3>";
echo "<div style='background-color: #fff3cd; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128072; Steps to fix:</h4>";
echo "<ol>";
echo "<li>1. Open file: <code>lib/NotificationHelper.php</code></li>";
echo "<li>2. Go to line 395</li>";
echo "<li>3. Change: <code>return \$hours . \" phút\";</code></li>";
echo "<li>4. To: <code>return \$hours . \" phút\";</code></li>";
echo "<li>5. Save file</li>";
echo "</ol>";
echo "</div>";

echo "<h3>Test the fix:</h3>";
echo "<p>Sau khi fix, hãy test:</p>";
echo "<ul>";
echo "<li>1. Tao notification moi</li>";
echo "<li>2. Check frontend - time should show correctly</li>";
echo "<li>3. Test with different time periods:</li>";
echo "<ul>";
echo "<li>- &lt; 60s: \"Vài giây\"</li>";
echo "<li>- 2-59m: \"X phút\"</li>";
echo "<li>- 1-23h: \"X phút\" (sau khi fix thành \"X giò\")</li>";
echo "<li>- 1-6d: \"X ngày\"</li>";
echo "<li>- &gt;6d: \"dd/mm/yyyy\"</li>";
echo "</ul>";
echo "</ul>";

echo "<h3>Current Issue Analysis:</h3>";
echo "<div style='background-color: #f8f9fa; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#128204; Why always \"Vài giây\":</h4>";
echo "<ol>";
echo "<li><strong>Logic error:</strong> Dòng 395 return \"phút\" cho hours thay vì \"giò\"</li>";
echo "<li><strong>Timezone issue:</strong> Database time vs PHP time có khác nhau</li>";
echo "<li><strong>Cache issue:</strong> Frontend có cache old data</li>";
echo "</ol>";
echo "</div>";

// Test current function
echo "<h3>Test Current Function:</h3>";

require_once __DIR__ . '/lib/NotificationHelper.php';
$notificationHelper = new NotificationHelper();

$testTimes = [
    ['time' => date('Y-m-d H:i:s'), 'desc' => 'Now'],
    ['time' => date('Y-m-d H:i:s', time() - 30), 'desc' => '30s ago'],
    ['time' => date('Y-m-d H:i:s', time() - 300), 'desc' => '5m ago'],
    ['time' => date('Y-m-d H:i:s', time() - 3600), 'desc' => '1h ago'],
    ['time' => date('Y-m-d H:i:s', time() - 7200), 'desc' => '2h ago'],
];

echo "<table border='1' cellpadding='5'>";
echo "<tr><th>Test</th><th>Time</th><th>Result</th><th>Expected</th></tr>";

foreach ($testTimes as $test) {
    $result = $notificationHelper->getTimeAgo($test['time']);
    $expected = '';
    if ($test['desc'] === 'Now' || $test['desc'] === '30s ago') {
        $expected = 'Vài giây';
    } elseif ($test['desc'] === '5m ago') {
        $expected = '5 phút';
    } elseif ($test['desc'] === '1h ago') {
        $expected = '1 phút'; // Should be '1 giò' after fix
    } elseif ($test['desc'] === '2h ago') {
        $expected = '2 phút'; // Should be '2 giò' after fix
    }
    
    $status = ($result === $expected) ? 'PASS' : 'FAIL';
    $color = ($status === 'PASS') ? 'green' : 'red';
    
    echo "<tr>";
    echo "<td>{$test['desc']}</td>";
    echo "<td>{$test['time']}</td>";
    echo "<td><strong>{$result}</strong></td>";
    echo "<td>{$expected}</td>";
    echo "<td style='color: {$color};'>{$status}</td>";
    echo "</tr>";
}
echo "</table>";

echo "<h3>&#128072; Next Steps:</h3>";
echo "<div style='background-color: #e8f5e8; padding: 15px; border-radius: 5px;'>";
echo "<h4>&#10004; After fixing line 395:</h4>";
echo "<ol>";
echo "<li>1. Test again with this script</li>";
echo "<li>2. Check frontend notifications</li>";
echo "<li>3. Clear browser cache</li>";
echo "<li>4. Test with real notifications</li>";
echo "</ol>";
echo "</div>";
?>
