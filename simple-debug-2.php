<?php
echo "<h2>SIMPLE DEBUG 2</h2>";

require_once __DIR__ . '/lib/NotificationHelper.php';
$notificationHelper = new NotificationHelper();

echo "<h3>Test Function Directly:</h3>";

// Test with 2 hours ago
$twoHoursAgo = date('Y-m-d H:i:s', time() - 7200);
$result = $notificationHelper->getTimeAgo($twoHoursAgo);

echo "<p><strong>Test (2 hours ago):</strong> {$result}</p>";
echo "<p><strong>Expected:</strong> 2 phút</p>";

if ($result === '2 phút') {
    echo "<p style='color: green;'>✅ Function works correctly</p>";
} else {
    echo "<p style='color: red;'>❌ Function has issue</p>";
}

echo "<h3>Test with 30 minutes ago:</h3>";

$thirtyMinutesAgo = date('Y-m-d H:i:s', time() - 1800);
$result2 = $notificationHelper->getTimeAgo($thirtyMinutesAgo);

echo "<p><strong>Test (30 minutes ago):</strong> {$result2}</p>";
echo "<p><strong>Expected:</strong> 30 phút</p>";

if ($result2 === '30 phút') {
    echo "<p style='color: green;'>✅ Function works correctly</p>";
} else {
    echo "<p style='color: red;'>❌ Function has issue</p>";
}

echo "<h3>Check Function Source:</h3>";

$source = file_get_contents(__DIR__ . '/lib/NotificationHelper.php');
$startPos = strpos($source, 'private function getTimeAgo');
$endPos = strpos($source, '}', $startPos) + 1;
$functionCode = substr($source, $startPos, $endPos - $startPos);

echo "<pre style='background-color: #f5f5f5; padding: 10px;'>";
echo htmlspecialchars($functionCode);
echo "</pre>";

echo "<h3>Conclusion:</h3>";
echo "<p><strong>If function returns 'Vài giây' for old timestamps:</strong></p>";
echo "<ol>";
echo "<li>1. Function logic is broken</li>";
echo "<li>2. strtotime() not working</li>";
echo "<li>3. time() function issue</li>";
echo "<li>4. Timezone problem</li>";
echo "</ol>";
?>
