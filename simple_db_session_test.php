<?php
// Simple database session test
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Simple DB Session Test</h1>";

try {
    require_once 'config/database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    echo "<p>✅ Database connected</p>";
    
    // Create sessions table
    $sql = "CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(128) PRIMARY KEY,
        data TEXT NOT NULL,
        timestamp INT NOT NULL
    )";
    $db->exec($sql);
    echo "<p>✅ Sessions table ready</p>";
    
    // Manual session test
    $sessionId = 'test_session_' . time();
    $testData = 'user_id|i:1;username|s:5:"test";';
    $timestamp = time();
    
    // Insert session
    $stmt = $db->prepare("INSERT INTO sessions (id, data, timestamp) VALUES (?, ?, ?)");
    $result = $stmt->execute([$sessionId, $testData, $timestamp]);
    
    if ($result) {
        echo "<p>✅ Session inserted</p>";
        
        // Read session back
        $stmt = $db->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row) {
            echo "<p>✅ Session read back</p>";
            echo "<p>Data: " . htmlspecialchars($row['data']) . "</p>";
            echo "<p>Length: " . strlen($row['data']) . " bytes</p>";
            
            // Test unserializing
            $sessionData = unserialize($row['data']);
            if ($sessionData) {
                echo "<p>✅ Data unserialized</p>";
                echo "<pre>" . print_r($sessionData, true) . "</pre>";
            } else {
                echo "<p>❌ Failed to unserialize</p>";
            }
        } else {
            echo "<p>❌ Failed to read session</p>";
        }
    } else {
        echo "<p>❌ Failed to insert session</p>";
    }
    
} catch (Exception $e) {
    echo "<p>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<br><a href='simple_db_session_test.php'>Test Again</a>";
?>
