<?php
// Force logout endpoint - completely independent
header('Content-Type: application/json');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Start session to destroy it
session_start();

// Get session info before destroying
$session_id = session_id();
$session_data = $_SESSION;

// Complete session destruction
session_unset();
session_destroy();
session_write_close();

// Delete all possible session cookies
$cookies = array('PHPSESSID', session_name());
foreach ($cookies as $cookie) {
    if (isset($_COOKIE[$cookie])) {
        setcookie($cookie, '', time() - 42000, '/');
        setcookie($cookie, '', time() - 42000, '/', '', false, true);
        unset($_COOKIE[$cookie]);
    }
}

// Force delete session file
$session_file = ini_get('session.save_path') . '/sess_' . $session_id;
if (file_exists($session_file)) {
    @unlink($session_file);
}

// Delete from database if available
try {
    require_once 'config/database.php';
    $db = (new Database())->getConnection();
    if ($db) {
        $stmt = $db->prepare("DELETE FROM sessions WHERE id = ?");
        $stmt->execute([$session_id]);
    }
} catch (Exception $e) {
    // Ignore DB errors
}

// Return success
echo json_encode([
    'success' => true,
    'message' => 'Force logout completed',
    'session_id' => $session_id,
    'timestamp' => time()
]);
?>
