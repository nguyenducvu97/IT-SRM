<?php
require_once 'lib/PHPMailerEmailHelper.php';

$emailHelper = new PHPMailerEmailHelper();

// Test data
$test_data = [
    'id' => 999,
    'title' => 'Test PHPMailer',
    'status' => 'in_progress',
    'description' => 'Mô tả test PHPMailer',
    'requester_email' => 'nguyenducvu561@gmail.com',
    'requester_name' => 'Vu nguyen duc'
];

echo "Testing PHPMailer sendStatusUpdateNotification...\n";
$result = $emailHelper->sendStatusUpdateNotification($test_data, 'Nguyễn văn tín');

if ($result) {
    echo "✅ PHPMailer email sent successfully!\n";
} else {
    echo "❌ PHPMailer email failed!\n";
}
?>
