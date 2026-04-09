<?php
echo "<h2>Check Staff Password</h2>";

require_once __DIR__ . '/config/database.php';
$db = getDatabaseConnection();

try {
    $stmt = $db->prepare("SELECT id, username, full_name, role, password_hash FROM users WHERE role = 'staff'");
    $stmt->execute();
    $staffUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Staff Users in Database</h3>";
    echo "<table border='1' cellpadding='5' style='border-collapse: collapse;'>";
    echo "<tr><th>ID</th><th>Username</th><th>Full Name</th><th>Role</th><th>Password Hash</th></tr>";
    
    foreach ($staffUsers as $staff) {
        echo "<tr>";
        echo "<td>{$staff['id']}</td>";
        echo "<td>{$staff['username']}</td>";
        echo "<td>" . htmlspecialchars($staff['full_name']) . "</td>";
        echo "<td>{$staff['role']}</td>";
        echo "<td><small>" . substr($staff['password_hash'], 0, 20) . "...</small></td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test common passwords
    echo "<h3>Test Common Passwords for staff1</h3>";
    $commonPasswords = ['password', '123456', 'admin', 'staff', 'password123', 'staff123', '123'];
    
    if (!empty($staffUsers)) {
        $staff1 = $staffUsers[0]; // Get first staff user
        echo "<p>Testing passwords for user: <strong>{$staff1['username']}</strong></p>";
        
        foreach ($commonPasswords as $password) {
            if (password_verify($password, $staff1['password_hash'])) {
                echo "<p style='color: green;'>&#10004; <strong>'{$password}'</strong> - CORRECT PASSWORD!</p>";
                break;
            } else {
                echo "<p style='color: red;'>&#10027; '{$password}' - Incorrect</p>";
            }
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>";
}

echo "<h3>Reset Password Option</h3>";
echo "<p>If you want to reset staff1 password to 'password123', run this SQL:</p>";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 3px;'>";
echo "UPDATE users SET password_hash = '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE username = 'staff1';";
echo "</pre>";
echo "<p>This will set password to 'password123'</p>";
?>
