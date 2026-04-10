<?php
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';

require_once 'api/reject_requests.php';
?>
