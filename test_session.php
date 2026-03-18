<?php
require_once 'config/session.php';
require_once 'config/database.php';

session_start();

echo "=== SESSION DEBUG ===\n";
echo "Session ID: " . session_id() . "\n";
echo "Session Status: " . (session_status() ? 'Active' : 'Inactive') . "\n";
echo "Session Data: ";
var_export($_SESSION, true);
echo "\n";

echo "\n=== COOKIE DEBUG ===\n";
echo "Cookies: ";
var_export($_COOKIE, true);
echo "\n";

echo "\n=== AUTH DEBUG ===\n";
if (isset($_SESSION['user_id'])) {
    echo "User ID: " . $_SESSION['user_id'] . "\n";
    echo "Username: " . ($_SESSION['username'] ?? 'Not set') . "\n";
    echo "Role: " . ($_SESSION['role'] ?? 'Not set') . "\n";
} else {
    echo "No user session found!\n";
}
?>
