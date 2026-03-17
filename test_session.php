<?php
// Test session persistence
session_start();

echo "<h1>Session Test</h1>";

echo "<h2>Current Session Info:</h2>";
echo "Session ID: " . session_id() . "<br>";
echo "Session Status: " . session_status() . "<br>";

echo "<h2>Session Data:</h2>";
echo "<pre>";
print_r($_SESSION);
echo "</pre>";

echo "<h2>Cookie Data:</h2>";
echo "<pre>";
print_r($_COOKIE);
echo "</pre>";

echo "<h2>Session Configuration:</h2>";
echo "Session Save Path: " . session_save_path() . "<br>";
echo "Session Cookie Params: <pre>";
print_r(session_get_cookie_params());
echo "</pre>";

// Test setting session data
if (!isset($_SESSION['test_time'])) {
    $_SESSION['test_time'] = date('Y-m-d H:i:s');
    echo "<p style='color: green;'>Session data set!</p>";
} else {
    echo "<p style='color: blue;'>Session data exists from: " . $_SESSION['test_time'] . "</p>";
}

echo "<br><br>";
echo "<a href='test_session.php'>Refresh this page</a><br>";
echo "<a href='index.html'>Go to main app</a><br>";
?>
