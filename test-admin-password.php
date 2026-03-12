<?php
require_once 'config/database.php';

// Test password verification
$database = new Database();
$db = $database->getConnection();

// Get admin user
$query = "SELECT username, password_hash FROM users WHERE username = 'admin'";
$stmt = $db->prepare($query);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Admin user found:<br>";
    echo "Username: " . htmlspecialchars($user['username']) . "<br>";
    echo "Password Hash: " . htmlspecialchars($user['password_hash']) . "<br><br>";
    
    // Test different passwords
    $passwords = ['admin', 'password', '123456', 'admin123'];
    
    foreach ($passwords as $password) {
        $verified = password_verify($password, $user['password_hash']);
        echo "Testing password '$password': " . ($verified ? "✓ CORRECT" : "✗ INCORRECT") . "<br>";
    }
    
    // Test creating new hash
    echo "<br>Creating new hash for 'admin':<br>";
    $new_hash = password_hash('admin', PASSWORD_DEFAULT);
    echo "New hash: " . htmlspecialchars($new_hash) . "<br>";
    
    $verified_new = password_verify('admin', $new_hash);
    echo "New hash verification: " . ($verified_new ? "✓ CORRECT" : "✗ INCORRECT") . "<br>";
    
} else {
    echo "Admin user not found!";
}
?>
