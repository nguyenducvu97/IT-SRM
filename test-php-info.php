<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP SESSION CONFIGURATION ===\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Session Support: " . (extension_loaded('session') ? 'YES' : 'NO') . "\n";

echo "\nSession Settings:\n";
echo "session.save_handler: " . ini_get('session.save_handler') . "\n";
echo "session.save_path: " . ini_get('session.save_path') . "\n";
echo "session.name: " . ini_get('session.name') . "\n";
echo "session.cookie_lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "session.cookie_path: " . ini_get('session.cookie_path') . "\n";
echo "session.cookie_domain: " . ini_get('session.cookie_domain') . "\n";
echo "session.cookie_secure: " . ini_get('session.cookie_secure') . "\n";
echo "session.cookie_httponly: " . ini_get('session.cookie_httponly') . "\n";
echo "session.use_cookies: " . ini_get('session.use_cookies') . "\n";
echo "session.use_only_cookies: " . ini_get('session.use_only_cookies') . "\n";
echo "session.gc_probability: " . ini_get('session.gc_probability') . "\n";
echo "session.gc_divisor: " . ini_get('session.gc_divisor') . "\n";
echo "session.gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";

echo "\nTesting session functions:\n";
echo "session_id() exists: " . (function_exists('session_id') ? 'YES' : 'NO') . "\n";
echo "session_start() exists: " . (function_exists('session_start') ? 'YES' : 'NO') . "\n";
echo "session_destroy() exists: " . (function_exists('session_destroy') ? 'YES' : 'NO') . "\n";
echo "session_unset() exists: " . (function_exists('session_unset') ? 'YES' : 'NO') . "\n";

echo "\nCurrent session status:\n";
echo "session_status(): " . session_status() . " (";
switch(session_status()) {
    case 0: echo "PHP_SESSION_DISABLED"; break;
    case 1: echo "PHP_SESSION_NONE"; break;
    case 2: echo "PHP_SESSION_ACTIVE"; break;
}
echo ")\n";

if (session_status() == PHP_SESSION_NONE) {
    echo "\nStarting test session...\n";
    session_start();
    echo "New session ID: " . session_id() . "\n";
    $_SESSION['test'] = 'Hello';
    echo "Session data set\n";
}

echo "\nSession data: " . json_encode($_SESSION) . "\n";

echo "\n=== SOLUTION TEST ===\n";
echo "Testing complete session reset...\n";

// Complete session reset
session_unset();
session_destroy();
session_write_close();

// Clear cookie
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
    unset($_COOKIE[session_name()]);
}

// Start new session
session_id(uniqid());
session_start();
echo "New session after reset: " . session_id() . "\n";
echo "New session data: " . json_encode($_SESSION) . "\n";
?>
