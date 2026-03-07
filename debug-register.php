<?php
// Debug registration
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set headers
header("Content-Type: application/json; charset=UTF-8");

// Get POST data
$data = json_decode(file_get_contents("php://input"));

echo "Received data: " . json_encode($data) . "\n";
echo "Action: " . ($data->action ?? 'null') . "\n";

// Include required files
require_once 'config/database.php';
require_once 'config/session.php';

echo "Files included\n";

// Test database connection
$database = new Database();
$db = $database->getConnection();

if ($db === null) {
    echo "Database connection failed\n";
    exit;
}

echo "Database connection successful\n";

// Test registration
if ($data->action == 'register') {
    echo "Processing registration\n";
    
    $username = htmlspecialchars(strip_tags(trim($data->username)));
    $email = htmlspecialchars(strip_tags(trim($data->email)));
    $password = $data->password;
    $full_name = htmlspecialchars(strip_tags(trim($data->full_name)));
    $department = htmlspecialchars(strip_tags(trim($data->department)));
    $phone = htmlspecialchars(strip_tags(trim($data->phone)));
    
    echo "Data sanitized\n";
    
    if (empty($username) || empty($email) || empty($password) || empty($full_name) || empty($department)) {
        echo "Validation failed\n";
        exit;
    }
    
    echo "Validation passed\n";
    
    // Check if username already exists
    $query = "SELECT id FROM users WHERE username = :username";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        echo "Username exists\n";
        exit;
    }
    
    echo "Username available\n";
    
    // Create new user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);
    $query = "INSERT INTO users (username, email, password_hash, full_name, department, phone, role, created_at) 
              VALUES (:username, :email, :password_hash, :full_name, :department, :phone, 'user', NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password_hash', $password_hash);
    $stmt->bindParam(':full_name', $full_name);
    $stmt->bindParam(':department', $department);
    $stmt->bindParam(':phone', $phone);
    
    if ($stmt->execute()) {
        echo "User created successfully\n";
    } else {
        echo "User creation failed\n";
        print_r($stmt->errorInfo());
    }
}

echo "Script completed\n";
?>
