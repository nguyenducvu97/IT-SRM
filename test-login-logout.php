<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'config/session.php';
require_once 'config/database.php';

startSession();

$action = $_GET['action'] ?? 'status';

echo "<h2>Login/Logout Test</h2>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Current action: $action</p>";

switch ($action) {
    case 'login':
        // Simulate login
        $_SESSION['user_id'] = 1;
        $_SESSION['username'] = 'admin';
        $_SESSION['role'] = 'admin';
        $_SESSION['full_name'] = 'Administrator';
        
        echo "<p style='color: green;'>✅ Logged in as admin</p>";
        echo "<p><a href='?action=logout'>Logout</a> | <a href='?action=status'>Check Status</a></p>";
        break;
        
    case 'logout':
        destroySession();
        echo "<p style='color: orange;'>🔓 Logged out</p>";
        echo "<p><a href='?action=login'>Login</a> | <a href='?action=status'>Check Status</a></p>";
        break;
        
    case 'status':
    default:
        if (isLoggedIn()) {
            $user = getCurrentUser();
            echo "<p style='color: green;'>✅ Currently logged in</p>";
            echo "<pre>" . print_r($user, true) . "</pre>";
            echo "<p><a href='?action=logout'>Logout</a></p>";
        } else {
            echo "<p style='color: red;'>❌ Not logged in</p>";
            echo "<p><a href='?action=login'>Login</a></p>";
        }
        break;
}

// Check session data in database
echo "<h3>Session Data in Database:</h3>";
try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("SELECT data FROM sessions WHERE id = ?");
    $stmt->execute([session_id()]);
    $session_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($session_data) {
        echo "<pre>" . print_r(unserialize($session_data['data']), true) . "</pre>";
    } else {
        echo "<p>No session data found in database</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<p><a href='test-login-logout.php'>Refresh (F5)</a></p>";
?>
