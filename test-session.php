<?php
// Test session management
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "Testing session configuration...<br>";

// Test session config include
try {
    require_once 'config/session.php';
    echo "✓ Session config loaded<br>";
} catch (Exception $e) {
    echo "✗ Session config error: " . $e->getMessage() . "<br>";
}

// Test startSession function
try {
    startSession();
    echo "✓ Session started<br>";
} catch (Exception $e) {
    echo "✗ Session start error: " . $e->getMessage() . "<br>";
}

// Set test session data
$_SESSION['user_id'] = 1;
$_SESSION['role'] = 'admin';
$_SESSION['username'] = 'admin';

echo "Session data set: " . json_encode($_SESSION) . "<br>";

// Test session persistence
session_write_close();
session_start();

echo "Session after restart: " . json_encode($_SESSION) . "<br>";

// Test if user_id exists
if (isset($_SESSION['user_id'])) {
    echo "✓ User ID exists in session: " . $_SESSION['user_id'] . "<br>";
} else {
    echo "✗ User ID not found in session<br>";
}
?>
