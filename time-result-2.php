<?php
echo "START\n";
require_once __DIR__ . '/lib/NotificationHelper.php';
$notificationHelper = new NotificationHelper();

$testTime = '2026-04-09 14:00:00'; // 2+ hours ago
$result = $notificationHelper->getTimeAgo($testTime);

echo "RESULT: $result\n";
echo "END\n";
?>
