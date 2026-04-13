<?php
// Check current session status
session_start();

header('Content-Type: application/json');

echo json_encode([
    'session_id' => session_id(),
    'session_data' => $_SESSION,
    'is_logged_in' => isset($_SESSION['user_id']),
    'user_id' => $_SESSION['user_id'] ?? null,
    'user_role' => $_SESSION['role'] ?? null,
    'cookie_data' => $_COOKIE,
    'session_status' => session_status()
]);
?>
