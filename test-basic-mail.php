<?php
// Test basic PHP mail function
$to = 'nguyenducvu561@gmail.com';
$subject = 'Test basic PHP mail';
$message = 'This is a test message';
$headers = 'From: test@localhost' . "\r\n" .
           'Reply-To: test@localhost' . "\r\n" .
           'X-Mailer: PHP/' . phpversion();

if (mail($to, $subject, $message, $headers)) {
    echo 'Basic PHP mail sent successfully!';
} else {
    echo 'Basic PHP mail FAILED!';
    echo 'Error: ' . error_get_last()['message'] ?? 'Unknown error';
}
?>
