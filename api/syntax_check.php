<?php
// Check syntax of service_requests.php
$output = shell_exec('php -l "C:\xampp\htdocs\it-service-request\api\service_requests.php" 2>&1');
echo $output;
?>
