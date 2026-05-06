<?php
require_once 'config/database.php';
require_once 'config/session.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== FORCE LOGOUT ===\n\n";

// Get current session ID
$old_session_id = session_id();
echo "Current session ID: $old_session_id\n";

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
    $old_session_id = session_id();
    echo "Started new session: $old_session_id\n";
}

// Clear all session data
$_SESSION = array();
echo "Cleared session data\n";

// Unset session cookie completely
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
    setcookie(session_name(), '', time() - 42000, '/', '', false, true);
    unset($_COOKIE[session_name()]);
    echo "Deleted session cookie\n";
}

// Destroy session
session_destroy();
echo "Destroyed session\n";

// Force garbage collection
session_gc(ini_get('session.gc_maxlifetime'));
echo "Ran garbage collection\n";

// Delete from database with multiple attempts
try {
    $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
    $stmt->execute([$old_session_id]);
    echo "Deleted from database\n";
} catch (Exception $e) {
    echo "DB delete error: " . $e->getMessage() . "\n";
}

// Delete session file
$session_file = ini_get('session.save_path') . '/sess_' . $old_session_id;
if (file_exists($session_file)) {
    unlink($session_file);
    echo "Deleted session file\n";
}

// Clear all possible session files
$session_path = ini_get('session.save_path');
if (is_dir($session_path)) {
    $files = glob($session_path . '/sess_*');
    foreach ($files as $file) {
        if (strpos($file, $old_session_id) !== false) {
            unlink($file);
            echo "Deleted session file: $file\n";
        }
    }
}

echo "\n=== TESTING NEW SESSION ===\n";

// Start completely fresh session
session_id(''); // Generate new session ID
session_start();
$new_session_id = session_id();

echo "New session ID: $new_session_id\n";
echo "Old session ID: $old_session_id\n";
echo "Session data: " . json_encode($_SESSION) . "\n";
echo "Session status: " . session_status() . "\n";

echo "\n=== DONE ===\n";
echo "Now test: http://localhost/it-service-request/api/auth.php?action=check_session\n";
?>
