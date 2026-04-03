<?php
// Test database session handler
require_once 'config/session.php';
require_once 'config/database.php';

echo "<h2>Database Session Handler Test</h2>";

try {
    $db = getDatabaseConnection();
    echo "✅ Database connection established<br>";
    
    // Check if sessions table exists
    $table_check = $db->query("SHOW TABLES LIKE 'sessions'");
    if ($table_check->rowCount() > 0) {
        echo "✅ Sessions table exists<br>";
        
        // Show table structure
        $structure = $db->query("DESCRIBE sessions");
        echo "<h3>Sessions Table Structure:</h3>";
        echo "<table border='1'>";
        echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th></tr>";
        while ($row = $structure->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
        
        // Count sessions
        $count = $db->query("SELECT COUNT(*) as total FROM sessions");
        $total = $count->fetch(PDO::FETCH_ASSOC)['total'];
        echo "<h3>Active Sessions: $total</h3>";
        
        // Show current sessions
        if ($total > 0) {
            $sessions = $db->query("SELECT id, timestamp FROM sessions ORDER BY timestamp DESC LIMIT 5");
            echo "<h3>Recent Sessions:</h3>";
            echo "<table border='1'>";
            echo "<tr><th>Session ID</th><th>Timestamp</th><th>Age</th></tr>";
            while ($row = $sessions->fetch(PDO::FETCH_ASSOC)) {
                $age = time() - $row['timestamp'];
                echo "<tr>";
                echo "<td>" . substr($row['id'], 0, 20) . "...</td>";
                echo "<td>" . date('Y-m-d H:i:s', $row['timestamp']) . "</td>";
                echo "<td>" . $age . " seconds ago</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
    } else {
        echo "❌ Sessions table does not exist<br>";
        echo "<h3>Creating sessions table...</h3>";
        
        $sql = "CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT NOT NULL,
            timestamp INT NOT NULL,
            INDEX (timestamp)
        )";
        
        if ($db->exec($sql)) {
            echo "✅ Sessions table created successfully<br>";
        } else {
            echo "❌ Error creating sessions table: " . implode(", ", $db->errorInfo()) . "<br>";
        }
    }
    
    // Test session handler
    echo "<h3>Testing Session Handler:</h3>";
    
    // Start session
    startSession();
    echo "✅ Session started - ID: " . session_id() . "<br>";
    
    // Set some test data
    $_SESSION['test'] = 'Hello from session test';
    $_SESSION['timestamp'] = time();
    
    // Force session write
    session_write_close();
    echo "✅ Session data written<br>";
    
    // Read session back
    session_start();
    echo "✅ Session read back<br>";
    echo "<p>Session data: " . json_encode($_SESSION) . "</p>";
    
    // Check if session exists in database
    $session_check = $db->prepare("SELECT data, timestamp FROM sessions WHERE id = ?");
    $session_check->execute([session_id()]);
    $session_data = $session_check->fetch(PDO::FETCH_ASSOC);
    
    if ($session_data) {
        echo "✅ Session found in database<br>";
        echo "<p>Database timestamp: " . date('Y-m-d H:i:s', $session_data['timestamp']) . "</p>";
        echo "<p>Session data size: " . strlen($session_data['data']) . " bytes</p>";
    } else {
        echo "❌ Session not found in database<br>";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "<br>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}
?>
