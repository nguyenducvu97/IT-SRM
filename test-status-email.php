<?php
require_once 'lib/ImprovedEmailHelper.php';

$emailHelper = new ImprovedEmailHelper();

// Test data giống như request #15
$test_data = [
    'id' => 15,
    'title' => 'Test yêu cầu',
    'status' => 'in_progress',
    'description' => 'Mô tả test',
    'requester_email' => 'nguyenducvu561@gmail.com',
    'requester_name' => 'Vu nguyen duc'
];

echo "Testing sendStatusUpdateNotification...\n";
$result = $emailHelper->sendStatusUpdateNotification($test_data, 'Nguyễn văn tín');

if ($result) {
    echo "Email sent successfully!\n";
} else {
    echo "Failed to send email!\n";
}
?>
