<?php
require_once 'config/database.php';

// Reset admin password to 'admin'
$database = new Database();
$db = $database->getConnection();

$username = 'admin';
$new_password = 'admin';
$new_hash = password_hash($new_password, PASSWORD_DEFAULT);

echo "Resetting admin password...<br>";
echo "Username: $username<br>";
echo "New Password: $new_password<br>";
echo "New Hash: $new_hash<br><br>";

// Update the password
$query = "UPDATE users SET password_hash = :password_hash WHERE username = :username";
$stmt = $db->prepare($query);
$stmt->bindParam(':password_hash', $new_hash);
$stmt->bindParam(':username', $username);

if ($stmt->execute()) {
    echo "✓ Password reset successfully!<br>";
    
    // Verify the update
    $check_query = "SELECT password_hash FROM users WHERE username = :username";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->bindParam(':username', $username);
    $check_stmt->execute();
    $result = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        $verified = password_verify($new_password, $result['password_hash']);
        echo "✓ Password verification: " . ($verified ? "SUCCESS" : "FAILED") . "<br>";
    }
    
} else {
    echo "✗ Failed to reset password<br>";
    echo "Error: " . print_r($stmt->errorInfo(), true);
}
?>
