<?php
require_once 'config/session.php';

startSession();

echo "<h1>Session Test AFTER Fix</h1>";

// Test session data
$_SESSION['test'] = 'Session working at ' . date('H:i:s');

echo "<h3>Session Info:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Session Data: <pre>" . print_r($_SESSION, true) . "</pre></p>";

echo "<h3>Session File:</h3>";
$sessionFile = session_save_path() . '/sess_' . session_id();
echo "<p>File: $sessionFile</p>";
echo "<p>Exists: " . (file_exists($sessionFile) ? 'YES ✅' : 'NO ❌') . "</p>";

if (file_exists($sessionFile)) {
    $size = filesize($sessionFile);
    $content = file_get_contents($sessionFile);
    echo "<p>Size: $size bytes</p>";
    echo "<p>Content: " . htmlspecialchars($content) . "</p>";
}

echo "<br><a href='session_test_after_fix.php'>Refresh</a>";
?>
