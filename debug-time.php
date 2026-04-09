<?php
echo "DEBUG TIME FUNCTION\n";

require_once __DIR__ . '/lib/NotificationHelper.php';
$notificationHelper = new NotificationHelper();

echo "Current time: " . date('Y-m-d H:i:s') . "\n";
echo "Current timestamp: " . time() . "\n\n";

// Test with a specific old time
$testTime = '2026-04-09 15:00:00'; // Definitely old
echo "Test time: $testTime\n";

$result = $notificationHelper->getTimeAgo($testTime);
echo "Result: '$result'\n\n";

// Test with 2 hours ago
$twoHoursAgo = date('Y-m-d H:i:s', time() - 7200);
echo "Test time (2 hours ago): $twoHoursAgo\n";

$result2 = $notificationHelper->getTimeAgo($twoHoursAgo);
echo "Result: '$result2'\n\n";

// Check function source
echo "FUNCTION SOURCE:\n";
$source = file_get_contents(__DIR__ . '/lib/NotificationHelper.php');
$startPos = strpos($source, 'private function getTimeAgo');
$endPos = strpos($source, '}', $startPos) + 1;
$functionCode = substr($source, $startPos, $endPos - $startPos);
echo $functionCode . "\n";

// Test manual calculation
echo "\nMANUAL CALCULATION:\n";
$manualTime = '2026-04-09 14:00:00';
$timestamp = strtotime($manualTime);
$now = time();
$diff = $now - $timestamp;

echo "Time: $manualTime\n";
echo "Timestamp: $timestamp\n";
echo "Now: $now\n";
echo "Diff: $diff seconds\n";
echo "Diff in hours: " . ($diff / 3600) . "\n";

if ($diff < 60) {
    echo "Should return: Vài giây\n";
} elseif ($diff < 3600) {
    $minutes = floor($diff / 60);
    echo "Should return: $minutes phút\n";
} elseif ($diff < 86400) {
    $hours = floor($diff / 3600);
    echo "Should return: $hours phút\n";
} else {
    echo "Should return: " . date('d/m/Y', $timestamp) . "\n";
}

$actualResult = $notificationHelper->getTimeAgo($manualTime);
echo "Actual result: '$actualResult'\n";
?>
