<?php
echo "<h2>Reset Staff Password</h2>";

require_once __DIR__ . '/config/database.php';
$db = getDatabaseConnection();

try {
    // Reset staff1 password to 'password123'
    $newPasswordHash = password_hash('password123', PASSWORD_DEFAULT);
    
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'staff1'");
    $result = $stmt->execute([$newPasswordHash]);
    
    if ($result) {
        echo "<p style='color: green;'>&#10004; Password for staff1 has been reset to 'password123'</p>";
        
        // Verify the change
        $stmt = $db->prepare("SELECT username, password_hash FROM users WHERE username = 'staff1'");
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user && password_verify('password123', $user['password_hash'])) {
            echo "<p style='color: green;'>&#10004; Password verification successful!</p>";
        } else {
            echo "<p style='color: red;'>&#10027; Password verification failed!</p>";
        }
    } else {
        echo "<p style='color: red;'>&#10027; Failed to reset password</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Test Login Again</h3>";
echo "<p><a href='test-staff-login-accept.php'>Click here to test login and accept request</a></p>";
?>
