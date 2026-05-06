<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: text/plain; charset=utf-8');

echo "=== SESSION HANDLER TEST ===\n\n";

try {
    require_once 'config/database.php';
    echo "✅ Database config loaded\n";
    
    $database = new Database();
    $db = $database->getConnection();
    
    if ($db === null) {
        echo "❌ Database connection failed\n";
        exit;
    } else {
        echo "✅ Database connected\n";
    }
    
    require_once 'config/session.php';
    echo "✅ Session config loaded\n";
    
    echo "Session status before startSession(): " . session_status() . "\n"; // 1=disabled, 2=none, 3=active
    
    startSession();
    
    echo "Session status after startSession(): " . session_status() . "\n";
    echo "Session save handler after startSession(): " . ini_get('session.save_handler') . "\n";
    echo "Session ID: " . session_id() . "\n";
    
    // Test write
    $_SESSION['test'] = 'Hello from handler test';
    session_write_close();
    
    echo "✅ Session data written\n";
    
    // Check if it's in database
    $stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->execute([session_id()]);
    $session = $stmt->fetch();
    
    if ($session) {
        echo "✅ Session found in database\n";
        echo "DB data: " . substr($session['data'], 0, 100) . "\n";
    } else {
        echo "❌ Session NOT found in database\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
?>
