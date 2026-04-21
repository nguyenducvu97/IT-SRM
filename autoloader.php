<?php
// Simple autoloader for PHPMailer
spl_autoload_register(function ($class) {
    // Map PHPMailer classes
    $class_map = [
        "PHPMailer\PHPMailer\PHPMailer" => __DIR__ . "/vendor/phpmailer/PHPMailer.php",
        "PHPMailer\PHPMailer\SMTP" => __DIR__ . "/vendor/phpmailer/SMTP.php",
        "PHPMailer\PHPMailer\Exception" => __DIR__ . "/vendor/phpmailer/Exception.php"
    ];
    
    if (isset($class_map[$class])) {
        require_once $class_map[$class];
    }
});
?>