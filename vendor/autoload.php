<?php
// Simple autoloader for PHPMailer
spl_autoload_register(function ($class) {
    $prefix = 'PHPMailer\\PHPMailer\\';
    $base_dir = __DIR__ . '/phpmailer/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . $relative_class . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load PHPMailer classes
require_once __DIR__ . '/phpmailer/phpmailer.php';

// For backward compatibility
if (!class_exists('PHPMailer\PHPMailer\PHPMailer')) {
    class_alias('PHPMailer', 'PHPMailer\PHPMailer\PHPMailer');
}

if (!class_exists('SMTP')) {
    class_alias('SMTP', 'PHPMailer\PHPMailer\SMTP');
}

if (!class_exists('phpmailerException')) {
    class_alias('phpmailerException', 'PHPMailer\PHPMailer\Exception');
}
?>
