<?php
// Manual password reset with direct database access
try {
    $pdo = new PDO('mysql:host=localhost;dbname=it_service_request', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected to database successfully<br>";
    
    // Create new hash
    $new_password = 'admin';
    $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    echo "New password: $new_password<br>";
    echo "New hash: $new_hash<br><br>";
    
    // Update using prepared statement
    $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE username = ?");
    
    if ($stmt->execute([$new_hash, 'admin'])) {
        echo "✓ Password updated successfully!<br>";
        
        // Verify the update
        $check = $pdo->prepare("SELECT password_hash FROM users WHERE username = ?");
        $check->execute(['admin']);
        $result = $check->fetch(PDO::FETCH_ASSOC);
        
        if ($result && password_verify($new_password, $result['password_hash'])) {
            echo "✓ Password verification: SUCCESS<br>";
            echo "<br><strong>Login Credentials:</strong><br>";
            echo "Username: admin<br>";
            echo "Password: admin<br>";
        } else {
            echo "✗ Password verification: FAILED<br>";
        }
    } else {
        echo "✗ Password update failed<br>";
        echo "Error: " . print_r($stmt->errorInfo(), true) . "<br>";
    }
    
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage() . "<br>";
}
?>
