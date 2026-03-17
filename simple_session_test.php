<?php
// Simple session test
require_once 'config/session.php';

startSession();

echo "<h1>Simple Session Test</h1>";

// Test setting session data
if (!isset($_SESSION['test_counter'])) {
    $_SESSION['test_counter'] = 1;
    echo "<p style='color: green;'>First visit - Counter set to 1</p>";
} else {
    $_SESSION['test_counter']++;
    echo "<p style='color: blue;'>Visit #" . $_SESSION['test_counter'] . "</p>";
}

echo "<h3>Session Info:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Status: " . session_status() . "</p>";

echo "<h3>Session Data:</h3>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h3>Cookies:</h3>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h3>Session Cookie Params:</h3>";
echo "<pre>";
print_r(session_get_cookie_params());
echo "</pre>";

echo "<br><a href='simple_session_test.php'>Refresh</a> | <a href='index.html'>Main App</a>";
?>
