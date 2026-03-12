<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

// Direct SQL execution with proper escaping
$sql = "UPDATE users SET password_hash = ? WHERE username = ?";
$stmt = $db->prepare($sql);

$new_hash = '$2y$10$RY0cMisfl3fedWTdYwH7auLviTAwA2RwddqKpw3QUiUcRRSBsRJ76';
$username = 'admin';

if ($stmt->execute([$new_hash, $username])) {
    echo "✓ Password updated successfully!<br>";
    
    // Test verification
    $test = $db->prepare("SELECT password_hash FROM users WHERE username = ?");
    $test->execute([$username]);
    $result = $test->fetch(PDO::FETCH_ASSOC);
    
    if ($result && password_verify('admin', $result['password_hash'])) {
        echo "✓ Password verification: SUCCESS<br>";
        echo "Login credentials:<br>";
        echo "Username: admin<br>";
        echo "Password: admin<br>";
    } else {
        echo "✗ Password verification: FAILED<br>";
    }
} else {
    echo "✗ Update failed: " . print_r($stmt->errorInfo(), true) . "<br>";
}
?>
