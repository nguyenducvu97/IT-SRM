<?php
echo "FINAL TIME TEST\n";

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
    echo "This explains why notifications always show 'Vài giây'\n";
} elseif ($result === '1 phút') {
    echo "SUCCESS: Function works correctly\n";
    echo "The issue is elsewhere (API, frontend, or database)\n";
} else {
    echo "UNEXPECTED: Result is '$result'\n";
}

// Debug the function internals
echo "\nDEBUG FUNCTION INTERNALS:\n";
$testTime = '2026-04-09 15:36:41'; // 1 hour ago
echo "Test time: $testTime\n";
$timestamp = strtotime($testTime);
$now = time();
$diff = $now - $timestamp;
echo "Timestamp: $timestamp\n";
echo "Now: $now\n";
echo "Diff: $diff seconds\n";

if ($diff < 60) {
    echo "Logic: Should return 'Vài giây'\n";
} elseif ($diff < 3600) {
    echo "Logic: Should return minutes\n";
} elseif ($diff < 86400) {
    echo "Logic: Should return hours\n";
} else {
    echo "Logic: Should return date\n";
}
?>
