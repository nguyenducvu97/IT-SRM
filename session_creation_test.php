<?php
require_once 'config/session.php';

startSession();

echo "<h1>Session Creation Test</h1>";

echo "<h3>Current Session Info:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Cookies received: " . json_encode($_COOKIE) . "</p>";

// Test creating session data
$_SESSION['test'] = 'Working at ' . date('H:i:s');
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'testuser';

echo "<h3>Session Data Set:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

// Check session file
$sessionFile = session_save_path() . '/sess_' . session_id();
echo "<h3>Session File Check:</h3>";
echo "<p>File: $sessionFile</p>";
echo "<p>Exists: " . (file_exists($sessionFile) ? 'YES ✅' : 'NO ❌') . "</p>";

if (file_exists($sessionFile)) {
    $size = filesize($sessionFile);
    $content = file_get_contents($sessionFile);
    echo "<p>Size: $size bytes</p>";
    echo "<p>Content: " . htmlspecialchars($content) . "</p>";
}

echo "<br><a href='session_creation_test.php'>Refresh</a>";
?>
