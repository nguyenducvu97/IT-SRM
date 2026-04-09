<?php
echo "TEST FUNCTION ONLY\n";

require_once __DIR__ . '/lib/NotificationHelper.php';
$notificationHelper = new NotificationHelper();

echo "Current time: " . date('Y-m-d H:i:s') . "\n";
echo "Current timestamp: " . time() . "\n\n";

// Test 1 hour ago
$oneHourAgo = date('Y-m-d H:i:s', time() - 3600);
echo "Test time (1 hour ago): $oneHourAgo\n";

$result = $notificationHelper->getTimeAgo($oneHourAgo);
echo "Result: '$result'\n\n";

// Test 5 minutes ago
$fiveMinutesAgo = date('Y-m-d H:i:s', time() - 300);
echo "Test time (5 minutes ago): $fiveMinutesAgo\n";

$result2 = $notificationHelper->getTimeAgo($fiveMinutesAgo);
echo "Result: '$result2'\n\n";

// Test 30 seconds ago
$thirtySecondsAgo = date('Y-m-d H:i:s', time() - 30);
echo "Test time (30 seconds ago): $thirtySecondsAgo\n";

$result3 = $notificationHelper->getTimeAgo($thirtySecondsAgo);
echo "Result: '$result3'\n\n";

echo "CONCLUSION:\n";
if ($result === 'Vài giây') {
    echo "PROBLEM: 1 hour ago shows 'Vài giây' - FUNCTION IS BROKEN!\n";
} elseif ($result === '1 phút') {
    echo "SUCCESS: Function works correctly\n";
} else {
    echo "UNEXPECTED: Result is '$result'\n";
}
?>
