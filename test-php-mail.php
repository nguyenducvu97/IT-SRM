<?php
// Test PHP mail sau khi cấu hình SMTP
$to = 'nguyenducvu561@gmail.com';
$subject = 'Test XAMPP SMTP';
$message = 'Đây là email test sau khi cấu hình SMTP trong XAMPP';
$headers = 'From: ndvu@sgitech.com.vn' . "\r\n" .
           'Reply-To: ndvu@sgitech.com.vn' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo '✅ Email sent successfully!';
} else {
    echo '❌ Email failed to send!';
    echo 'Error: ' . error_get_last()['message'] ?? 'Unknown error';
}
?>
