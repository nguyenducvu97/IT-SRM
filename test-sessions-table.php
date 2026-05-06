<?php
require_once 'config/database.php';

header('Content-Type: text/plain; charset=utf-8');

echo "=== SESSIONS TABLE CHECK ===\n\n";

// Check if table exists
try {
    $stmt = $db->query("SHOW TABLES LIKE 'sessions'");
    $table = $stmt->fetch();
    
    if ($table) {
        echo "✅ Sessions table exists\n\n";
        
        // Show table structure
        echo "=== TABLE STRUCTURE ===\n";
        $stmt = $db->query("DESCRIBE sessions");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo $row['Field'] . " - " . $row['Type'] . " - " . $row['Null'] . " - " . $row['Key'] . "\n";
        }
        
        echo "\n=== TABLE DATA ===\n";
        $stmt = $db->query("SELECT COUNT(*) as total FROM sessions");
        $count = $stmt->fetch();
        echo "Total sessions: " . $count['total'] . "\n\n";
        
        // Show all sessions
        $stmt = $db->query("SELECT * FROM sessions");
        $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (empty($sessions)) {
            echo "No session data found\n";
        } else {
            foreach ($sessions as $session) {
                echo "ID: " . $session['id'] . "\n";
                echo "Data: " . substr($session['data'], 0, 200) . "...\n";
                echo "Timestamp: " . $session['timestamp'] . "\n";
                echo "---\n";
            }
        }
        
    } else {
        echo "❌ Sessions table does NOT exist\n\n";
        
        // Create table
        echo "=== CREATING SESSIONS TABLE ===\n";
        $sql = "CREATE TABLE sessions (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT NOT NULL,
            timestamp INT(11) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        if ($db->exec($sql)) {
            echo "✅ Sessions table created successfully\n";
        } else {
            echo "❌ Error creating sessions table\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

echo "\n=== TEST SESSION WRITE ===\n";
try {
    startSession();
    $_SESSION['test'] = 'Hello World';
    session_write_close();
    
    echo "Session ID: " . session_id() . "\n";
    echo "Test data written\n";
    
    // Check if it's in database
    $stmt = $db->prepare("SELECT * FROM sessions WHERE id = ?");
    $stmt->execute([session_id()]);
    $session = $stmt->fetch();
    
    if ($session) {
        echo "✅ Session found in database\n";
        echo "Data: " . substr($session['data'], 0, 200) . "\n";
    } else {
        echo "❌ Session NOT found in database\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
