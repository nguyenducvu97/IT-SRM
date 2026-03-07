<?php
require_once 'lib/PHPMailerEmailHelper.php';

$emailHelper = new PHPMailerEmailHelper();

// Test data
$test_data = [
    'id' => 999,
    'title' => 'Test PHPMailer to Admin',
    'requester_name' => 'Test User',
    'category' => 'Test Category',
    'priority' => 'medium',
    'description' => 'Mô tả test PHPMailer đến admin'
];

echo "Testing PHPMailer sendNewRequestNotification to admin...\n";
$result = $emailHelper->sendNewRequestNotification($test_data);

if ($result) {
    echo "✅ PHPMailer email to admin sent successfully!\n";
} else {
    echo "❌ PHPMailer email to admin failed!\n";
}
?>
