<?php
header('Content-Type: text/plain; charset=utf-8');

echo "=== PHP SESSION CONFIG ===\n\n";

echo "Session save handler: " . ini_get('session.save_handler') . "\n";
echo "Session save path: " . ini_get('session.save_path') . "\n";
echo "Session name: " . ini_get('session.name') . "\n";
echo "Session cookie lifetime: " . ini_get('session.cookie_lifetime') . "\n";
echo "Session GC probability: " . ini_get('session.gc_probability') . "\n";
echo "Session GC divisor: " . ini_get('session.gc_divisor') . "\n";
echo "Session GC maxlifetime: " . ini_get('session.gc_maxlifetime') . "\n";

echo "\n=== CURRENT SESSION INFO ===\n";
session_start();
echo "Session ID: " . session_id() . "\n";
echo "Session status: " . session_status() . "\n"; // 1 = PHP_SESSION_DISABLED, 2 = PHP_SESSION_NONE, 3 = PHP_SESSION_ACTIVE

echo "\n=== CHECK SESSION FILES ===\n";
$session_path = ini_get('session.save_path');
if (is_dir($session_path)) {
    echo "Session directory: $session_path\n";
    $files = glob($session_path . '/sess_*');
    echo "Session files count: " . count($files) . "\n";
    
    foreach ($files as $file) {
        if (strpos($file, session_id()) !== false) {
            echo "Current session file: $file\n";
            echo "File size: " . filesize($file) . " bytes\n";
            echo "File content: " . file_get_contents($file) . "\n";
        }
    }
} else {
    echo "Session directory not found: $session_path\n";
}

echo "\n=== TEST DATABASE SESSION ===\n";
try {
    require_once 'config/database.php';
    require_once 'config/session.php';
    
    startSession();
    $_SESSION['test_db'] = 'Database session test';
    session_write_close();
    
    echo "Database session written\n";
    
    // Check database
    $stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->execute([session_id()]);
    $session = $stmt->fetch();
    
    if ($session) {
        echo "✅ Found session in database\n";
        echo "DB data: " . substr($session['data'], 0, 100) . "\n";
    } else {
        echo "❌ Session NOT found in database\n";
        echo "⚠️  Session might be using file handler instead!\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
