<?php
require_once 'config/database.php';

// Test database session handler
class DatabaseSessionHandler {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        
        // Create session table if not exists
        $this->createSessionTable();
    }
    
    private function createSessionTable() {
        $sql = "CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT NOT NULL,
            timestamp INT NOT NULL
        )";
        $this->db->exec($sql);
    }
    
    public function open($savePath, $sessionName) {
        return true;
    }
    
    public function close() {
        return true;
    }
    
    public function read($sessionId) {
        $stmt = $this->db->prepare("SELECT data FROM sessions WHERE id = ?");
        $stmt->execute([$sessionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result ? $result['data'] : '';
    }
    
    public function write($sessionId, $data) {
        $timestamp = time();
        $stmt = $this->db->prepare("INSERT INTO sessions (id, data, timestamp) VALUES (?, ?, ?) 
                                     ON DUPLICATE KEY UPDATE data = ?, timestamp = ?");
        return $stmt->execute([$sessionId, $data, $timestamp, $data, $timestamp]);
    }
    
    public function destroy($sessionId) {
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE id = ?");
        return $stmt->execute([$sessionId]);
    }
    
    public function gc($maxLifetime) {
        $old = time() - $maxLifetime;
        $stmt = $this->db->prepare("DELETE FROM sessions WHERE timestamp < ?");
        return $stmt->execute([$old]);
    }
}

// Set database session handler
$handler = new DatabaseSessionHandler();
session_set_save_handler(
    [$handler, 'open'],
    [$handler, 'close'],
    [$handler, 'read'],
    [$handler, 'write'],
    [$handler, 'destroy'],
    [$handler, 'gc']
);

// Start session
session_start();

echo "<h1>Database Session Test</h1>";

echo "<h3>Session Info:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>Cookies received: " . json_encode($_COOKIE) . "</p>";

// Test session data
$_SESSION['test'] = 'DB Session working at ' . date('H:i:s');
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'dbuser';

echo "<h3>Session Data Set:</h3>";
echo "<pre>" . print_r($_SESSION, true) . "</pre>";

// Check database
$stmt = $handler->db->prepare("SELECT * FROM sessions WHERE id = ?");
$stmt->execute([session_id()]);
$sessionRow = $stmt->fetch(PDO::FETCH_ASSOC);

echo "<h3>Database Session Check:</h3>";
if ($sessionRow) {
    echo "<p>Session found in database ✅</p>";
    echo "<p>Data length: " . strlen($sessionRow['data']) . " bytes</p>";
    echo "<p>Raw data: " . htmlspecialchars($sessionRow['data']) . "</p>";
} else {
    echo "<p>Session NOT found in database ❌</p>";
}

echo "<br><a href='session_db_test.php'>Refresh</a>";
?>
