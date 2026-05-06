<?php
require_once 'config/database.php';
require_once 'config/session.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== STEP BY STEP LOGOUT DEBUG ===\n\n";

// Step 1: Check current session
echo "Step 1: Current session state\n";
startSession();
$session_id = session_id();
echo "Session ID: $session_id\n";
echo "Session data exists: " . (isset($_SESSION['user_id']) ? 'YES' : 'NO') . "\n";

// Step 2: Check database
echo "\nStep 2: Database check\n";
$stmt = $db->prepare("SELECT COUNT(*) as count FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$count = $stmt->fetch();
echo "Sessions in DB: " . $count['count'] . "\n";

// Step 3: Clear session data
echo "\nStep 3: Clear session data\n";
$_SESSION = array();
echo "Session data cleared\n";

// Step 4: Get cookie params BEFORE destroying
echo "\nStep 4: Cookie parameters\n";
$cookie_params = session_get_cookie_params();
echo "Cookie path: " . $cookie_params['path'] . "\n";
echo "Cookie domain: " . $cookie_params['domain'] . "\n";
echo "Cookie secure: " . ($cookie_params['secure'] ? 'true' : 'false') . "\n";
echo "Cookie httponly: " . ($cookie_params['httponly'] ? 'true' : 'false') . "\n";

// Step 5: Delete session cookie
echo "\nStep 5: Delete session cookie\n";
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000,
        $cookie_params['path'],
        $cookie_params['domain'],
        $cookie_params['secure'],
        $cookie_params['httponly']
    );
    unset($_COOKIE[session_name()]);
    echo "Cookie deleted\n";
} else {
    echo "No cookie to delete\n";
}

// Step 6: Destroy session
echo "\nStep 6: Destroy session\n";
session_destroy();
echo "Session destroyed\n";

// Step 7: Delete from database
echo "\nStep 7: Delete from database\n";
$stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
$result = $stmt->execute([$session_id]);
echo "Database delete result: " . ($result ? 'SUCCESS' : 'FAILED') . "\n";

// Step 8: Delete session file
echo "\nStep 8: Delete session file\n";
$session_file = ini_get('session.save_path') . '/sess_' . $session_id;
if (file_exists($session_file)) {
    unlink($session_file);
    echo "Session file deleted: $session_file\n";
} else {
    echo "No session file to delete\n";
}

// Step 9: Verify deletion
echo "\nStep 9: Verify deletion\n";
$stmt = $db->prepare("SELECT COUNT(*) as count FROM sessions WHERE id = ?");
$stmt->execute([$session_id]);
$count = $stmt->fetch();
echo "Sessions in DB after delete: " . $count['count'] . "\n";

// Step 10: Test new session
echo "\nStep 10: Test new session\n";
session_start();
$new_session_id = session_id();
echo "New session ID: $new_session_id\n";
echo "Old session ID: $session_id\n";
echo "Session data: " . json_encode($_SESSION) . "\n";

echo "\n=== COMPLETE ===\n";
?>
