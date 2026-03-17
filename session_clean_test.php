<?php
require_once 'config/session.php';

startSession();

echo "<h1>Session Test - Clean Start</h1>";

// Test session data
if (isset($_SESSION['test_value'])) {
    echo "<p style='color: green;'>✅ Session persists: " . $_SESSION['test_value'] . "</p>";
} else {
    $_SESSION['test_value'] = 'Test data at ' . date('H:i:s');
    echo "<p style='color: blue;'>📝 Session data set: " . $_SESSION['test_value'] . "</p>";
}

echo "<h3>Session Info:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";

echo "<h3>Cookies Received:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

// Test login simulation
if (isset($_GET['login'])) {
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'testuser';
    $_SESSION['full_name'] = 'Test User';
    $_SESSION['role'] = 'admin';
    echo "<p style='color: green;'>✅ Login simulated!</p>";
}

if (isset($_GET['logout'])) {
    session_destroy();
    echo "<p style='color: red;'>❌ Session destroyed!</p>";
}

echo "<br><br>";
echo "<a href='session_clean_test.php'>Refresh</a> | ";
echo "<a href='session_clean_test.php?login=1'>Simulate Login</a> | ";
echo "<a href='session_clean_test.php?logout=1'>Logout</a> | ";
echo "<a href='index.html'>Main App</a>";
?>
