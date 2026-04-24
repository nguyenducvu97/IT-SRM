<?php
// Database Session Management for IT Service Request System

class DatabaseSessionHandler {
    private $db;
    private static $instance = null;
    
    public function __construct() {
        if (self::$instance === null) {
            require_once 'database.php';
            $database = new Database();
            $this->db = $database->getConnection();
            self::$instance = $this->db;
        } else {
            $this->db = self::$instance;
        }
    }
    
    private function createSessionTable() {
        $sql = "CREATE TABLE IF NOT EXISTS sessions (
            id VARCHAR(128) PRIMARY KEY,
            data TEXT NOT NULL,
            timestamp INT NOT NULL,
            INDEX idx_timestamp (timestamp),
            INDEX idx_id (id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->exec($sql);
    }
    
    public function open($savePath, $sessionName) {
        return true;
    }
    
    public function close() {
        return true;
    }
    
    private static $readStmt = null;
    
    public function read($sessionId) {
        $start_time = microtime(true);
        
        // Create table if not exists (first access)
        $this->createSessionTable();
        
        // Use cached prepared statement
        if (self::$readStmt === null) {
            self::$readStmt = $this->db->prepare("SELECT data FROM sessions WHERE id = ?");
        }
        
        self::$readStmt->execute([$sessionId]);
        $result = self::$readStmt->fetch(PDO::FETCH_ASSOC);
        
        $read_time = round((microtime(true) - $start_time) * 1000, 2);
        if ($read_time > 5) { // Lower threshold to 5ms
            error_log("PERF: Session read took {$read_time}ms for ID: {$sessionId}");
        }
        
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

// Function to start session with database handler
function startSession() {
    if (session_status() == PHP_SESSION_NONE) {
        // Set database session handler
        $handler = new DatabaseSessionHandler();
        session_set_save_handler(
            array($handler, 'open'),
            array($handler, 'close'),
            array($handler, 'read'),
            array($handler, 'write'),
            array($handler, 'destroy'),
            array($handler, 'gc')
        );
    }
    
    // Start session
    session_start();
    
    if (session_status() == PHP_SESSION_ACTIVE) {
        error_log("Database session started - ID: " . session_id());
    }
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Function to get current user data
function getCurrentUser() {
    if (isLoggedIn()) {
        return [
            'id' => $_SESSION['user_id'],
            'username' => $_SESSION['username'] ?? '',
            'full_name' => $_SESSION['full_name'] ?? '',
            'email' => $_SESSION['email'] ?? '',
            'role' => $_SESSION['role'] ?? ''
        ];
    }
    return null;
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? null;
}

// Function to get current user role
function getCurrentUserRole() {
    return $_SESSION['role'] ?? null;
}

// Function to check if user has specific role
function hasRole($role) {
    return getCurrentUserRole() === $role;
}

// Function to check if user is admin
function isAdmin() {
    return hasRole('admin');
}

// Function to check if user is staff
function isStaff() {
    return hasRole('staff');
}

// Function to check if user is regular user
function isUser() {
    return hasRole('user');
}

// Function to require login
function requireLogin() {
    if (!isLoggedIn()) {
        header('Content-Type: application/json');
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Authentication required']);
        exit();
    }
}

// Function to require specific role
function requireRole($role) {
    requireLogin();
    if (!hasRole($role)) {
        header('Content-Type: application/json');
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Access denied']);
        exit();
    }
}

// Function to destroy session (logout)
function destroySession() {
    session_destroy();
    $_SESSION = array();
}

// Function to regenerate session ID
function regenerateSession() {
    session_regenerate_id(true);
}
?>
