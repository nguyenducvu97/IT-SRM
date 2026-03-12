<?php
// Test password verification directly
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Get current hash
$stmt = $db->prepare("SELECT password_hash FROM users WHERE username = ?");
$stmt->execute(['admin']);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "Current hash: " . $user['password_hash'] . "<br>";
    
    // Test various passwords
    $passwords = ['admin', 'password', '123456', 'admin123'];
    
    foreach ($passwords as $pwd) {
        $result = password_verify($pwd, $user['password_hash']);
        echo "Password '$pwd': " . ($result ? "✓ MATCH" : "✗ NO MATCH") . "<br>";
    }
    
    // Create new hash for 'admin'
    echo "<br>Creating new hash for 'admin':<br>";
    $new_hash = password_hash('admin', PASSWORD_DEFAULT);
    echo "New hash: " . $new_hash . "<br>";
    
    // Test new hash
    $test_new = password_verify('admin', $new_hash);
    echo "New hash test: " . ($test_new ? "✓ WORKS" : "✗ BROKEN") . "<br>";
    
    // Update with new hash
    echo "<br>Updating database with new hash...<br>";
    $update = $db->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    if ($update->execute([$new_hash, 'admin'])) {
        echo "✓ Update successful<br>";
        
        // Verify update
        $verify = $db->prepare("SELECT password_hash FROM users WHERE username = ?");
        $verify->execute(['admin']);
        $result = $verify->fetch(PDO::FETCH_ASSOC);
        
        if ($result && password_verify('admin', $result['password_hash'])) {
            echo "✓ Verification successful - you can now login with 'admin'/'admin'<br>";
        } else {
            echo "✗ Verification failed<br>";
        }
    } else {
        echo "✗ Update failed<br>";
    }
}
?>
