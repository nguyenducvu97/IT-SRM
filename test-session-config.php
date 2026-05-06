<?php
// Test session configuration and cookie settings
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Session Configuration Debug</h1>";

echo "<h2>PHP Session Settings</h2>";
echo "Session save_path: " . session_save_path() . "<br>";
echo "Session name: " . session_name() . "<br>";
echo "Session cookie lifetime: " . ini_get('session.cookie_lifetime') . "<br>";
echo "Session cookie path: " . ini_get('session.cookie_path') . "<br>";
echo "Session cookie domain: " . ini_get('session.cookie_domain') . "<br>";
echo "Session cookie secure: " . (ini_get('session.cookie_secure') ? 'Yes' : 'No') . "<br>";
echo "Session cookie_httponly: " . (ini_get('session.cookie_httponly') ? 'Yes' : 'No') . "<br>";
echo "Session use_cookies: " . (ini_get('session.use_cookies') ? 'Yes' : 'No') . "<br>";
echo "Session use_only_cookies: " . (ini_get('session.use_only_cookies') ? 'Yes' : 'No') . "<br>";
echo "Session gc_probability: " . ini_get('session.gc_probability') . "<br>";
echo "Session gc_divisor: " . ini_get('session.gc_divisor') . "<br>";
echo "Session gc_maxlifetime: " . ini_get('session.gc_maxlifetime') . "<br>";

echo "<h2>Current Session Status</h2>";
echo "Session status: " . session_status() . " (1=disabled, 2=no active, 3=active)<br>";
echo "Session ID: " . session_id() . "<br>";

echo "<h2>Test Session Start</h2>";
try {
    require_once 'config/session.php';
    startSession();
    echo "✅ Session started<br>";
    echo "Session ID after start: " . session_id() . "<br>";
    echo "Session data: " . json_encode($_SESSION) . "<br>";
    
    // Write test data
    $_SESSION['test'] = 'test_value_' . time();
    echo "✅ Test data written to session<br>";
    
    // Read back
    echo "Session data after write: " . json_encode($_SESSION) . "<br>";
    
} catch (Exception $e) {
    echo "❌ Session error: " . $e->getMessage() . "<br>";
}

echo "<h2>Cookie Information</h2>";
echo "Current cookies: " . json_encode($_COOKIE) . "<br>";

echo "<h2>Database Session Check</h2>";
try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    $current_session_id = session_id();
    $stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->execute([$current_session_id]);
    $session_row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session_row) {
        echo "✅ Session found in database<br>";
        echo "Session ID: " . $session_row['id'] . "<br>";
        echo "Session timestamp: " . date('Y-m-d H:i:s', $session_row['timestamp']) . "<br>";
        echo "Session data (raw): " . htmlspecialchars($session_row['data']) . "<br>";
        
        // Try to decode
        $decoded = session_decode($session_row['data']);
        echo "Session decode result: " . ($decoded ? 'Success' : 'Failed') . "<br>";
    } else {
        echo "❌ Session NOT found in database<br>";
        echo "This indicates the database session handler is not writing properly<br>";
    }
    
    // Count all sessions
    $stmt = $db->query("SELECT COUNT(*) as count FROM sessions");
    $count = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Total sessions in database: " . $count['count'] . "<br>";
    
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "<br>";
}

echo "<h2>Recommendations</h2>";
echo "<ul>";
echo "<li>If session NOT found in database: Database session handler write() method is failing</li>";
echo "<li>If cookie_lifetime is 0: Cookie is session cookie (deleted when browser closes)</li>";
echo "<li>If gc_maxlifetime is too short: Sessions are being garbage collected too early</li>";
echo "</ul>";

echo "<p><a href='test-auth-logout.php'>Back to Auth Test</a></p>";
?>
