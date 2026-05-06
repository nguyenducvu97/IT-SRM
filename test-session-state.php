<?php
require_once 'config/database.php';
require_once 'config/session.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== SESSION STATE CHECK ===\n\n";

// Start session
startSession();

echo "Current Session ID: " . session_id() . "\n";
echo "Cookie PHPSESSID: " . (isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : 'NOT SET') . "\n";
echo "Session Data: " . json_encode($_SESSION) . "\n\n";

// Check database
echo "=== DATABASE SESSIONS ===\n";
try {
    $stmt = $db->query("SELECT id, data, timestamp FROM sessions ORDER BY timestamp DESC LIMIT 5");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sessions)) {
        echo "No sessions in database\n";
    } else {
        foreach ($sessions as $session) {
            echo "ID: " . $session['id'] . "\n";
            echo "Timestamp: " . $session['timestamp'] . "\n";
            echo "Data: " . substr($session['data'], 0, 100) . "...\n";
            echo "---\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST LOGOUT ===\n";

// Simulate logout
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time() - 42000, '/');
    unset($_COOKIE[session_name()]);
}
session_destroy();

echo "After logout:\n";
echo "Session ID: " . session_id() . "\n";
echo "Cookie PHPSESSID: " . (isset($_COOKIE['PHPSESSID']) ? $_COOKIE['PHPSESSID'] : 'NOT SET') . "\n";
echo "Session Data: " . json_encode($_SESSION) . "\n\n";

// Check database again
echo "=== DATABASE AFTER LOGOUT ===\n";
try {
    $stmt = $db->query("SELECT id, data, timestamp FROM sessions ORDER BY timestamp DESC LIMIT 5");
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($sessions)) {
        echo "No sessions in database\n";
    } else {
        foreach ($sessions as $session) {
            echo "ID: " . $session['id'] . "\n";
            echo "Timestamp: " . $session['timestamp'] . "\n";
            echo "Data: " . substr($session['data'], 0, 100) . "...\n";
            echo "---\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== API CHECK SESSION ===\n";
echo "Test this URL: http://localhost/it-service-request/api/auth.php?action=check_session\n";
?>
