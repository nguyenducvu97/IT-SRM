<?php
require_once 'config/database.php';
require_once 'config/session.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== LOGOUT TEST ===\n\n";

// Start session
startSession();
$session_id = session_id();

echo "Before logout:\n";
echo "Session ID: " . $session_id . "\n";
echo "Session data: " . json_encode($_SESSION) . "\n";

// Check database
$stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$session = $stmt->fetch();

if ($session) {
    echo "✅ Session found in database\n";
    echo "DB data length: " . strlen($session['data']) . " bytes\n";
} else {
    echo "❌ Session NOT found in database\n";
}

// Check session file
$session_file = ini_get('session.save_path') . '/sess_' . $session_id;
echo "Session file: " . $session_file . "\n";
echo "Session file exists: " . (file_exists($session_file) ? 'YES' : 'NO') . "\n";
if (file_exists($session_file)) {
    echo "Session file size: " . filesize($session_file) . " bytes\n";
}

echo "\n=== PERFORMING LOGOUT ===\n";

// Simulate logout logic from auth.php
$_SESSION = array();

// Delete session cookie
if (isset($_COOKIE[session_name()])) {
    $cookie_params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $cookie_params['path'],
        $cookie_params['domain'],
        $cookie_params['secure'],
        $cookie_params['httponly']
    );
    unset($_COOKIE[session_name()]);
    echo "✅ Session cookie deleted\n";
}

// Destroy session
session_destroy();
echo "✅ Session destroyed\n";

// Force delete session from database
try {
    $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
    $stmt->execute([$session_id]);
    echo "✅ Deleted session from database: " . $session_id . "\n";
} catch (Exception $e) {
    echo "❌ Error deleting session from DB: " . $e->getMessage() . "\n";
}

// Force delete session file
if (file_exists($session_file)) {
    unlink($session_file);
    echo "✅ Deleted session file: " . $session_file . "\n";
}

echo "\n=== AFTER LOGOUT ===\n";

// Start new session to test
session_start();
$new_session_id = session_id();

echo "New Session ID: " . $new_session_id . "\n";
echo "Old session ID: " . $session_id . "\n";
echo "Session data: " . json_encode($_SESSION) . "\n";

// Check database again
$stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$old_session = $stmt->fetch();

if ($old_session) {
    echo "❌ OLD SESSION STILL EXISTS IN DATABASE!\n";
} else {
    echo "✅ Old session deleted from database\n";
}

// Check if new session exists
$stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([$new_session_id]);
$new_session = $stmt->fetch();

if ($new_session) {
    echo "✅ New session exists in database\n";
} else {
    echo "❌ New session NOT found in database\n";
}

echo "\n=== TEST API CHECK_SESSION ===\n";
echo "Test URL: http://localhost/it-service-request/api/auth.php?action=check_session\n";
?>
