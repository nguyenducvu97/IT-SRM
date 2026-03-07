<?php
// Test registration endpoint
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header("Content-Type: application/json; charset=UTF-8");

// Include required files
require_once 'config/database.php';

// Test data
$data = [
    'action' => 'register',
    'username' => 'testuser',
    'email' => 'test@example.com',
    'password' => '123456',
    'full_name' => 'Test User',
    'department' => 'IT',
    'phone' => '123456789'
];

echo "Testing registration with data: " . json_encode($data) . "\n";

// Test database connection
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo "Database connection failed\n";
    exit;
}

echo "Database connection successful\n";

// Test if users table exists
try {
    $stmt = $db->query("DESCRIBE users");
    echo "Users table exists\n";
} catch (PDOException $e) {
    echo "Users table error: " . $e->getMessage() . "\n";
}

// Test insert
try {
    $password_hash = password_hash('123456', PASSWORD_DEFAULT);
    $query = "INSERT INTO users (username, email, password_hash, full_name, department, phone, role, created_at) 
              VALUES (:username, :email, :password_hash, :full_name, :department, :phone, 'user', NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $data['username']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':password_hash', $password_hash);
    $stmt->bindParam(':full_name', $data['full_name']);
    $stmt->bindParam(':department', $data['department']);
    $stmt->bindParam(':phone', $data['phone']);
    
    if ($stmt->execute()) {
        echo "User created successfully\n";
    } else {
        echo "User creation failed\n";
    }
} catch (PDOException $e) {
    echo "Insert error: " . $e->getMessage() . "\n";
}
?>
