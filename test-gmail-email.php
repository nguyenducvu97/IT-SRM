<?php
require_once 'lib/PHPMailerEmailHelper.php';

$emailHelper = new PHPMailerEmailHelper();

echo "Testing email to nguyenducvu561@gmail.com...\n";
$result = $emailHelper->sendEmail(
    'nguyenducvu561@gmail.com',
    'Test User',
    'Test PHPMailer to Gmail',
    'Đây là email test từ PHPMailer đến Gmail lúc ' . date('H:i:s')
);

if ($result) {
    echo "✅ Email to Gmail sent successfully!\n";
} else {
    echo "❌ Email to Gmail failed!\n";
}
?>
