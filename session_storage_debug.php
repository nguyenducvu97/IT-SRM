<?php
echo "<h1>Session Storage Debug</h1>";

echo "<h3>PHP Session Configuration:</h3>";
echo "<p>Session Save Path: " . session_save_path() . "</p>";
echo "<p>Session Save Path Writable: " . (is_writable(session_save_path()) ? 'YES ✅' : 'NO ❌') . "</p>";

echo "<h3>Session Files:</h3>";
$files = glob(session_save_path() . '/sess_*');
echo "<p>Session files count: " . count($files) . "</p>";

if (count($files) > 0) {
    echo "<table border='1'>";
    echo "<tr><th>File</th><th>Size</th><th>Modified</th><th>Content Preview</th></tr>";
    
    foreach ($files as $file) {
        $size = filesize($file);
        $time = filemtime($file);
        $content = file_get_contents($file);
        $preview = substr($content, 0, 100);
        
        echo "<tr>";
        echo "<td>" . basename($file) . "</td>";
        echo "<td>{$size} bytes</td>";
        echo "<td>" . date('H:i:s', $time) . "</td>";
        echo "<td>" . htmlspecialchars($preview) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test session creation
echo "<h3>Test Session Creation:</h3>";
session_start([
    'cookie_lifetime' => 86400,
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
    'cookie_path' => '/',
    'cookie_domain' => '',
    'use_strict_mode' => true,
    'use_cookies' => true,
    'use_only_cookies' => true
]);

$_SESSION['test'] = 'Hello World at ' . date('H:i:s');

echo "<p>Current Session ID: " . session_id() . "</p>";
echo "<p>Session Data: <pre>" . print_r($_SESSION, true) . "</pre></p>";

echo "<p>Session File: " . session_save_path() . '/sess_' . session_id() . "</p>";
echo "<p>Session File Exists: " . (file_exists(session_save_path() . '/sess_' . session_id()) ? 'YES ✅' : 'NO ❌') . "</p>";

if (file_exists(session_save_path() . '/sess_' . session_id())) {
    $fileContent = file_get_contents(session_save_path() . '/sess_' . session_id());
    echo "<p>Session File Content: " . htmlspecialchars($fileContent) . "</p>";
}

echo "<br><a href='session_storage_debug.php'>Refresh</a>";
?>
