<?php
// Test getUserNotifications method directly
require_once 'config/database.php';
require_once 'lib/NotificationHelper.php';

// Create notification helper
$notificationHelper = new NotificationHelper();

// Test for staff user (id: 17)
$userId = 17;
echo "Testing getUserNotifications for user ID: $userId\n";

$notifications = $notificationHelper->getUserNotifications($userId, 20, 0);

echo "Result: ";
print_r($notifications);

echo "\nCount: " . count($notifications) . "\n";

// Test getUnreadCount
$unreadCount = $notificationHelper->getUnreadCount($userId);
echo "Unread count: $unreadCount\n";
?>
