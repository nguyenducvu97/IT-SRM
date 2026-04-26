<?php
// Test API search_requests.php directly
$_GET['search'] = '130';
$_GET['page'] = '1';
$_GET['limit'] = '10';

// Simulate session
session_start();
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';

// Include the API
include __DIR__ . '/api/search_requests.php';
?>
