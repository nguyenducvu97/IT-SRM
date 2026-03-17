<?php
// Debug session from browser context
header('Content-Type: application/json');

require_once '../config/database.php';
require_once '../config/session.php';

startSession();

echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'cookie_data' => $_COOKIE,
    'user_id' => getCurrentUserId(),
    'user_role' => getCurrentUserRole(),
    'is_logged_in' => isLoggedIn()
]);
?>
