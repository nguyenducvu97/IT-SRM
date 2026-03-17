<?php
// Check PHP session configuration
echo "<h1>PHP Session Configuration Check</h1>";

echo "<h3>Basic Info:</h3>";
echo "<p>PHP Version: " . phpversion() . "</p>";
echo "<p>Session Support: " . (extension_loaded('session') ? 'Yes' : 'No') . "</p>";

echo "<h3>Session Configuration:</h3>";
echo "<table border='1'>";
echo "<tr><th>Setting</th><th>Value</th></tr>";

$session_settings = [
    'session.save_handler' => ini_get('session.save_handler'),
    'session.save_path' => ini_get('session.save_path'),
    'session.name' => ini_get('session.name'),
    'session.cookie_lifetime' => ini_get('session.cookie_lifetime'),
    'session.cookie_path' => ini_get('session.cookie_path'),
    'session.cookie_domain' => ini_get('session.cookie_domain'),
    'session.cookie_secure' => ini_get('session.cookie_secure'),
    'session.cookie_httponly' => ini_get('session.cookie_httponly'),
    'session.cookie_samesite' => ini_get('session.cookie_samesite'),
    'session.use_cookies' => ini_get('session.use_cookies'),
    'session.use_only_cookies' => ini_get('session.use_only_cookies'),
    'session.use_strict_mode' => ini_get('session.use_strict_mode'),
    'session.gc_probability' => ini_get('session.gc_probability'),
    'session.gc_divisor' => ini_get('session.gc_divisor'),
    'session.gc_maxlifetime' => ini_get('session.gc_maxlifetime')
];

foreach ($session_settings as $key => $value) {
    echo "<tr><td>$key</td><td>$value</td></tr>";
}
echo "</table>";

echo "<h3>Session Save Path:</h3>";
$save_path = ini_get('session.save_path');
echo "<p>Path: $save_path</p>";

if (is_dir($save_path)) {
    echo "<p style='color: green;'>✅ Directory exists</p>";
    echo "<p>Writable: " . (is_writable($save_path) ? 'Yes ✅' : 'No ❌') . "</p>";
    
    // List session files
    $files = glob($save_path . '/sess_*');
    echo "<p>Session files count: " . count($files) . "</p>";
    
    if (count($files) > 0) {
        echo "<h4>Recent session files:</h4>";
        foreach (array_slice($files, 0, 5) as $file) {
            $size = filesize($file);
            $time = filemtime($file);
            echo "<p>" . basename($file) . " - " . date('Y-m-d H:i:s', $time) . " - {$size} bytes</p>";
        }
    }
} else {
    echo "<p style='color: red;'>❌ Directory does not exist!</p>";
}

echo "<br><a href='index.html'>Back to App</a>";
?>
