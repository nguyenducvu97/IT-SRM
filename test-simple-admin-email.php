<?php
require_once 'lib/PHPMailerEmailHelper.php';

$emailHelper = new PHPMailerEmailHelper();

echo "Testing simple email to admin...\n";
$result = $emailHelper->sendEmail(
    'ndvu@sgitech.com.vn',
    'Admin',
    'Test Email ' . date('H:i:s'),
    'Đây là email test đơn giản đến admin lúc ' . date('H:i:s')
);

if ($result) {
    echo "✅ Simple email to admin sent successfully!\n";
} else {
    echo "❌ Simple email to admin failed!\n";
}
?>
