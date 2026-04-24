<?php
// Database configuration for IT Service Request Management
// XAMPP MySQL settings

class Database {
    private $host = "localhost";
    private $db_name = "it_service_request";
    private $username = "root";
    private $password = "";
    private $conn;
    private static $instance = null;

    public function getConnection() {
        if (self::$instance === null || self::$instance === null) {
            try {
                $this->conn = new PDO("mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4", 
                                     $this->username, $this->password, [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                        PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => false,
                        PDO::ATTR_PERSISTENT => true
                    ]);
                $this->conn->exec("set names utf8mb4");
                self::$instance = $this->conn;
            } catch(PDOException $exception) {
                error_log("Connection error: " . $exception->getMessage());
                return null;
            }
        }
        
        return self::$instance;
    }
}

// Global database instance
$database = new Database();

// Helper function for backward compatibility
function getDatabaseConnection() {
    global $database;
    return $database->getConnection();
}

// Helper functions
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    // Don't use htmlspecialchars for database storage, only for output
    // This preserves Vietnamese characters and prevents SQL injection
    $data = addslashes($data);
    return $data;
}

function jsonResponse($success, $message, $data = null) {
    header('Content-Type: application/json');
    $response = [
        'success' => $success,
        'message' => $message
    ];
    if ($data !== null) {
        $response['data'] = $data;
    }
    echo json_encode($response);
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

function verifyPassword($password, $hashedPassword) {
    return password_verify($password, $hashedPassword);
}
?>
