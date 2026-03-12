<?php
require_once 'config/database.php';

// Fix admin password
$database = new Database();
$db = $database->getConnection();

$new_password = 'admin';
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "Fixing admin password...<br>";
echo "New password: $new_password<br>";
echo "New hash: $new_hash<br><br>";

// Use prepared statement to avoid SQL injection issues
$query = "UPDATE users SET password_hash = :password_hash WHERE username = :username";
$stmt = $db->prepare($query);
$stmt->bindParam(':password_hash', $new_hash);
$stmt->bindParam(':username', 'admin');

if ($stmt->execute()) {
    echo "✓ Password updated successfully!<br>";
    
    // Verify
    $check = $db->prepare("SELECT password_hash FROM users WHERE username = :username");
    $check->bindParam(':username', 'admin');
    $check->execute();
    $result = $check->fetch(PDO::FETCH_ASSOC);
    
    if ($result && password_verify($new_password, $result['password_hash'])) {
        echo "✓ Password verification: SUCCESS<br>";
        echo "You can now login with:<br>";
        echo "Username: admin<br>";
        echo "Password: admin<br>";
    } else {
        echo "✗ Password verification: FAILED<br>";
    }
} else {
    echo "✗ Password update failed<br>";
    echo "Error: " . print_r($stmt->errorInfo(), true) . "<br>";
}
?>
